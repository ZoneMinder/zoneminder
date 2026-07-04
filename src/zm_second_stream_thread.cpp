#include "zm_second_stream_thread.h"

#include "zm_ffmpeg.h"
#include "zm_ffmpeg_input.h"
#include "zm_monitor.h"
#include "zm_signal.h"
#include "zm_utils.h"
#include "url.hpp"

#include <algorithm>

namespace {
constexpr int kMinBackoffSeconds = 1;
constexpr int kMaxBackoffSeconds = 30;
}  // namespace

SecondStreamThread::SecondStreamThread(Monitor *monitor) :
  monitor_(monitor),
  terminate_(false),
  input_(nullptr),
  convert_context_(nullptr),
  have_image_(false),
  sequence_(0) {
  thread_ = std::thread(&SecondStreamThread::Run, this);
}

SecondStreamThread::~SecondStreamThread() {
  Stop();
  if (thread_.joinable()) thread_.join();
  CloseInput();
}

void SecondStreamThread::Start() {
  Stop();  // Signal any running thread to terminate first
  if (thread_.joinable()) thread_.join();
  terminate_ = false;
  thread_ = std::thread(&SecondStreamThread::Run, this);
}

void SecondStreamThread::Stop() {
  terminate_ = true;
}

void SecondStreamThread::Join() {
  if (thread_.joinable()) thread_.join();
}

bool SecondStreamThread::PeekLatest(uint64_t &sequence, FPSeconds &age) {
  std::lock_guard<std::mutex> lck(mutex_);
  if (!have_image_) return false;
  sequence = sequence_;
  age = std::chrono::duration_cast<FPSeconds>(std::chrono::system_clock::now() - capture_time_);
  return true;
}

bool SecondStreamThread::GetLatestImage(Image &dest, uint64_t &sequence, FPSeconds &age) {
  std::lock_guard<std::mutex> lck(mutex_);
  if (!have_image_) return false;
  dest.Assign(latest_image_);
  sequence = sequence_;
  age = std::chrono::duration_cast<FPSeconds>(std::chrono::system_clock::now() - capture_time_);
  return true;
}

// Build the credential-injected substream URL, mirroring the primary open in
// FfmpegCamera so the sidecar authenticates the same way.
static std::string BuildSecondUrl(const std::string &second_path,
                                  const std::string &user,
                                  const std::string &pass) {
  std::string url_string = second_path;
  if (!user.empty()) {
    try {
      Url url(second_path);
      if (url.user_info().empty()) {
        url.user_info(user + ":" + pass);
        url_string = url.str();
      }
    } catch (const Url::parse_error &e) {
      Debug(1, "Could not parse secondary path as URL: %s", e.what());
    }
  }
  return url_string;
}

bool SecondStreamThread::OpenInput() {
  const std::string url = BuildSecondUrl(monitor_->second_path, monitor_->user, monitor_->pass);

  // Replicate the primary stream's ffmpeg input options so, in particular, an
  // rtsp_transport=tcp primary does not end up pulling the substream over UDP.
  AVDictionary *opts = nullptr;
  if (!monitor_->options.empty()) {
    if (av_dict_parse_string(&opts, monitor_->options.c_str(), "=", ",", 0) < 0) {
      Warning("Monitor %d: could not parse ffmpeg options for substream '%s'",
              monitor_->id, monitor_->options.c_str());
    }
  }
  if (StringToUpper(url.substr(0, 4)) == "RTSP") {
    // Bound socket reads so a dead substream is detected (and the thread can be
    // joined on shutdown) instead of blocking forever in av_read_frame.  The
    // RTSP option was renamed stimeout -> timeout in ffmpeg 5.0; set both so the
    // bound applies regardless of the linked ffmpeg version.
    av_dict_set(&opts, "timeout", "5000000", AV_DICT_DONT_OVERWRITE);   // ffmpeg >= 5.0, microseconds
    av_dict_set(&opts, "stimeout", "5000000", AV_DICT_DONT_OVERWRITE);  // ffmpeg < 5.0, microseconds
    const std::string &method = monitor_->method;
    if (method == "rtpMulti") {
      av_dict_set(&opts, "rtsp_transport", "udp_multicast", 0);
    } else if (method == "rtpRtsp") {
      av_dict_set(&opts, "rtsp_transport", "tcp", 0);
    } else if (method == "rtpRtspHttp") {
      av_dict_set(&opts, "rtsp_transport", "http", 0);
    } else if (method == "rtpUni") {
      av_dict_set(&opts, "rtsp_transport", "udp", 0);
    }
  }

  input_ = new FFmpeg_Input();
  // The D1 substream is cheap to decode on the CPU, and we cannot consume
  // hardware-format frames here.  Forcing software decode also avoids contending
  // with the primary stream over the VAAPI device (/dev/dri/renderD128).
  input_->set_no_hwaccel(true);
  int ret = input_->Open(url.c_str(), &opts);
  av_dict_free(&opts);

  if (ret <= 0) {
    Warning("Monitor %d: failed to open secondary analysis stream", monitor_->id);
    CloseInput();
    return false;
  }
  if (input_->get_video_stream_id() < 0) {
    Warning("Monitor %d: no video stream in secondary analysis input", monitor_->id);
    CloseInput();
    return false;
  }
  Debug(1, "Monitor %d: opened secondary analysis stream (video stream %d)",
        monitor_->id, input_->get_video_stream_id());
  return true;
}

void SecondStreamThread::CloseInput() {
  if (input_) {
    delete input_;  // FFmpeg_Input dtor closes and frees everything it allocated
    input_ = nullptr;
  }
  if (convert_context_) {
    sws_freeContext(convert_context_);
    convert_context_ = nullptr;
  }
}

bool SecondStreamThread::ProduceImage(AVFrame *frame) {
  // We decode the substream in software (set_no_hwaccel), so frames are CPU
  // buffers.  Guard anyway: a hardware-format frame has a GPU surface in data[0],
  // not pixels, and must never be treated as a Y plane / swscale input.
  if (frame->hw_frames_ctx) {
    Error("Monitor %d: unexpected hardware substream frame (format %d); skipping",
          monitor_->id, frame->format);
    return false;
  }

  // The mailbox stores the image at the substream's NATIVE resolution.  The
  // consumer (Monitor::getMotionSourceImage) upscales to camera/zone dimensions
  // only when it actually scores a frame, so the expensive upscale runs at the
  // analysis rate (AnalysisFPSLimit) rather than the substream decode rate.
  Image produced;

  if (monitor_->analysis_image == Monitor::ANALYSISIMAGE_YCHANNEL) {
    // Mirror ZMPacket::get_y_image(): Y lives in data[0] of a planar YUV frame.
    const AVPixFmtDescriptor *desc = av_pix_fmt_desc_get(static_cast<AVPixelFormat>(frame->format));
    if (!desc) {
      Error("Monitor %d: no pixel format descriptor for substream format %d",
            monitor_->id, frame->format);
      return false;
    }
    if (desc->flags & AV_PIX_FMT_FLAG_RGB) {
      Error("Monitor %d: cannot get Y image from RGB substream format %s", monitor_->id, desc->name);
      return false;
    }
    if (!(desc->flags & AV_PIX_FMT_FLAG_PLANAR)) {
      Error("Monitor %d: cannot get Y image from non-planar substream format %s", monitor_->id, desc->name);
      return false;
    }

    // Copy the Y plane into a single-channel image.  Use the align-32 Image
    // constructor (buffer=nullptr, allocation=0) because ZM's Image::Scale and
    // all swscale paths assume an align-32 row layout; a width-packed image
    // (linesize == width) is misread at FFALIGN(width,32) when width is not a
    // multiple of 32 (e.g. 720), shearing the image into bands.  av_image_copy_plane
    // bridges the decoder's stride (frame->linesize[0]) to the image's stride.
    produced = Image(frame->width, frame->height, 1, ZM_SUBPIX_ORDER_NONE, nullptr, 0UL, 0);
    av_image_copy_plane(
        produced.Buffer(), produced.LineSize(),
        frame->data[0], frame->linesize[0],
        frame->width, frame->height);
  } else {
    // Full colour: convert pixel format only (still at native substream dims).
    // Align-32 layout (see the YChannel branch) so the downstream Scale is not
    // misread for widths that are not a multiple of 32.
    produced = Image(frame->width, frame->height,
                     monitor_->camera->Colours(), monitor_->camera->SubpixelOrder(),
                     nullptr, 0UL, 0);

    if (!convert_context_) {
      AVPixelFormat input_format;
      const int *coefs = nullptr;
      int src_range = 0;
      switch (frame->format) {
      case AV_PIX_FMT_YUVJ420P: input_format = AV_PIX_FMT_YUV420P; src_range = 1; break;
      case AV_PIX_FMT_YUVJ422P: input_format = AV_PIX_FMT_YUV422P; src_range = 1; break;
      case AV_PIX_FMT_YUVJ444P: input_format = AV_PIX_FMT_YUV444P; src_range = 1; break;
      case AV_PIX_FMT_YUVJ440P: input_format = AV_PIX_FMT_YUV440P; src_range = 1; break;
      default: input_format = static_cast<AVPixelFormat>(frame->format);
      }
      convert_context_ = sws_getContext(
          frame->width, frame->height, input_format,
          frame->width, frame->height, produced.AVPixFormat(),
          SWS_BICUBIC, nullptr, nullptr, nullptr);
      if (!convert_context_) {
        Error("Monitor %d: unable to create substream conversion context", monitor_->id);
        return false;
      }
      if (src_range) {
        // Mark the source as full-range (yuvj) so levels are not crushed.
        int dummy[4];
        int in_range, out_range, brightness, contrast, saturation;
        sws_getColorspaceDetails(convert_context_, reinterpret_cast<int **>(&dummy), &in_range,
                                 reinterpret_cast<int **>(&dummy), &out_range,
                                 &brightness, &contrast, &saturation);
        coefs = sws_getCoefficients(SWS_CS_DEFAULT);
        sws_setColorspaceDetails(convert_context_, coefs, 1, coefs, out_range,
                                 brightness, contrast, saturation);
      }
    }
    if (!produced.Assign(frame, convert_context_)) {
      Error("Monitor %d: failed to convert substream frame", monitor_->id);
      return false;
    }
  }

  {
    std::lock_guard<std::mutex> lck(mutex_);
    latest_image_.Assign(produced);
    have_image_ = true;
    sequence_++;
    capture_time_ = std::chrono::system_clock::now();
  }
  return true;
}

void SecondStreamThread::Run() {
  Debug(2, "SecondStreamThread::Run() for monitor %d", monitor_->id);

  int backoff = kMinBackoffSeconds;

  // Interruptible sleep: wake promptly on Stop()/zm_terminate.
  auto backoff_sleep = [this, &backoff]() {
    for (int i = 0; i < backoff * 10 && !(terminate_ or zm_terminate); i++) {
      std::this_thread::sleep_for(Milliseconds(100));
    }
    backoff = std::min(backoff * 2, kMaxBackoffSeconds);
  };

  while (!(terminate_ or zm_terminate)) {
    if (!input_) {
      if (!OpenInput()) {
        backoff_sleep();
        continue;
      }
    }

    AVFrame *frame = input_->get_frame(input_->get_video_stream_id());
    if (!frame) {
      Warning("Monitor %d: secondary analysis stream read failed, reconnecting", monitor_->id);
      CloseInput();
      backoff_sleep();
      continue;
    }

    ProduceImage(frame);
    backoff = kMinBackoffSeconds;  // healthy stream, reset backoff
  }

  CloseInput();
  Debug(2, "SecondStreamThread::Run() exiting for monitor %d", monitor_->id);
}
