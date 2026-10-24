/*
 * This file is part of the ZoneMinder Project. See AUTHORS file for Copyright information
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation; either version 2 of the License, or (at your
 * option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program. If not, see <http://www.gnu.org/licenses/>.
 */

#include "zm_stream_encoder.h"

#include "zm_image.h"
#include "zm_logger.h"

#include <algorithm>
#include <cmath>
#include <cstring>

extern "C" {
#include <libavutil/opt.h>
}

namespace {

const char *const kMjpeg = "mjpeg";

// The encoders a monitor may choose besides mjpeg. Hardware only: see the
// class comment.
const char *const kHardwareEncoders[] = {
  "h264_vaapi", "hevc_vaapi",
  "h264_qsv", "hevc_qsv",
  "h264_nvenc", "hevc_nvenc",
  "h264_v4l2m2m",
};

}  // namespace

StreamEncoder::StreamEncoder(std::string encoder_name, std::string hwaccel_device, int jpeg_quality)
  : wanted_name_(std::move(encoder_name)),
    hwaccel_device_(std::move(hwaccel_device)),
    jpeg_quality_(jpeg_quality) {
}

StreamEncoder::~StreamEncoder() {
  Close();
}

bool StreamEncoder::IsSupported(const std::string &name) {
  if (name == kMjpeg) return true;
  return std::any_of(std::begin(kHardwareEncoders), std::end(kHardwareEncoders),
                     [&name](const char *encoder) { return name == encoder; });
}

bool StreamEncoder::Open(int width, int height, AVRational frame_rate) {
  Close();
  if (wanted_name_ != kMjpeg) {
    if (!IsSupported(wanted_name_)) {
      Warning("StreamEncoder: %s is not a stream encoder, using mjpeg", wanted_name_.c_str());
    } else if (OpenEncoder(wanted_name_, width, height, frame_rate)) {
      return true;
    } else {
      Warning("StreamEncoder: %s would not open, using mjpeg", wanted_name_.c_str());
    }
  }
  return OpenEncoder(kMjpeg, width, height, frame_rate);
}

bool StreamEncoder::OpenEncoder(const std::string &name, int width, int height, AVRational frame_rate) {
  std::list<const CodecData *> candidates = get_encoder_data("", name);
  if (candidates.empty()) {
    Warning("StreamEncoder: no encoder named %s in this FFmpeg build", name.c_str());
    return false;
  }
  const CodecData *data = candidates.front();
  const AVCodec *codec = avcodec_find_encoder_by_name(data->codec_name);
  AVCodecContext *ctx = codec ? avcodec_alloc_context3(codec) : nullptr;
  if (!ctx) {
    Error("StreamEncoder: unable to allocate a context for %s", name.c_str());
    return false;
  }

  ctx->codec_type = AVMEDIA_TYPE_VIDEO;
  ctx->codec_id = data->codec_id;
  ctx->width = width;
  ctx->height = height;
  ctx->time_base = AV_TIME_BASE_Q;
  ctx->pix_fmt = data->hw_pix_fmt;
  ctx->sw_pix_fmt = data->sw_pix_fmt;
  // Without a frame rate, rate control derives one from the microsecond
  // time base and gives each frame a millionth of the bit rate.
  if (frame_rate.num <= 0 or frame_rate.den <= 0)
    frame_rate = {25, 1};
  ctx->framerate = frame_rate;

  if (data->codec_id == AV_CODEC_ID_MJPEG) {
    ctx->color_range = AVCOL_RANGE_JPEG;
    ctx->flags |= AV_CODEC_FLAG_QSCALE;
    ctx->global_quality = FF_QP2LAMBDA * libjpeg_to_ffmpeg_qv(jpeg_quality_);
  } else {
    // A live stream: no B-frames, and a keyframe at least every two seconds
    // for a consumer that joins without asking for one.
    ctx->gop_size = std::max(1, static_cast<int>(std::lround(2 * av_q2d(frame_rate))));
    ctx->max_b_frames = 0;
    ctx->bit_rate = 2000000;
    // Parameter sets go into extradata for the HELLO; Drain() also puts them
    // in front of every keyframe.
    ctx->flags |= AV_CODEC_FLAG_GLOBAL_HEADER;
    // Make a requested I-frame an IDR, which a fresh decoder can start on.
    av_opt_set_int(ctx->priv_data, "forced-idr", 1, 0);   // nvenc
    av_opt_set_int(ctx->priv_data, "forced_idr", 1, 0);   // qsv
  }

  AVBufferRef *hw_device_ctx = nullptr;
  if (setup_hwaccel(ctx, data, hw_device_ctx, hwaccel_device_, width, height)) {
    if (hw_device_ctx) av_buffer_unref(&hw_device_ctx);
    avcodec_free_context(&ctx);
    return false;
  }
  if (hw_device_ctx) av_buffer_unref(&hw_device_ctx);

  int ret = avcodec_open2(ctx, codec, nullptr);
  if (ret < 0) {
    Warning("StreamEncoder: can't open %s: %s", name.c_str(), av_make_error_string(ret).c_str());
    avcodec_free_context(&ctx);
    return false;
  }

  par_ = avcodec_parameters_alloc();
  if (!par_ or avcodec_parameters_from_context(par_, ctx) < 0) {
    Error("StreamEncoder: unable to copy the parameters of %s", name.c_str());
    avcodec_parameters_free(&par_);
    avcodec_free_context(&ctx);
    return false;
  }

  ctx_ = ctx;
  active_name_ = name;
  sw_pix_fmt_ = data->sw_pix_fmt;
  width_ = width;
  height_ = height;
  Info("StreamEncoder: publishing %dx%d as %s", width, height, name.c_str());
  return true;
}

void StreamEncoder::Close() {
  if (ctx_) avcodec_free_context(&ctx_);
  if (par_) avcodec_parameters_free(&par_);
  active_name_.clear();
  sw_pix_fmt_ = AV_PIX_FMT_NONE;
  width_ = height_ = 0;
  first_us_ = AV_NOPTS_VALUE;
  last_pts_ = AV_NOPTS_VALUE;
  discard_before_ = AV_NOPTS_VALUE;
}

bool StreamEncoder::Encode(const Image *image, SystemTimePoint timestamp, bool force_keyframe, const Sink &sink) {
  if (!ctx_ or !image) return false;
  if (static_cast<int>(image->Width()) != width_ or static_cast<int>(image->Height()) != height_) {
    Debug(1, "StreamEncoder: image is %ux%u, encoder is %dx%d", image->Width(), image->Height(), width_, height_);
    return false;
  }

  av_frame_ptr in{av_frame_alloc()};
  av_frame_ptr frame{av_frame_alloc()};
  if (!in or !frame or image->PopulateFrame(in.get()) < 0) return false;
  frame->format = sw_pix_fmt_;
  frame->width = width_;
  frame->height = height_;
  if (av_frame_get_buffer(frame.get(), 0) < 0) return false;
  if (swscale_.Convert(in.get(), frame.get()) < 0) return false;
  if (sw_pix_fmt_ == AV_PIX_FMT_YUVJ420P) frame->color_range = AVCOL_RANGE_JPEG;

#if HAVE_LIBAVUTIL_HWCONTEXT_H
  if (ctx_->hw_frames_ctx) {
    av_frame_ptr hw_frame{av_frame_alloc()};
    int ret;
    if (!hw_frame or (ret = av_hwframe_get_buffer(ctx_->hw_frames_ctx, hw_frame.get(), 0)) < 0) {
      Error("StreamEncoder: no hardware frame for %s", active_name_.c_str());
      return false;
    }
    if ((ret = av_hwframe_transfer_data(hw_frame.get(), frame.get(), 0)) < 0) {
      Error("StreamEncoder: upload to %s failed: %s", active_name_.c_str(), av_make_error_string(ret).c_str());
      return false;
    }
    frame = std::move(hw_frame);
  }
#endif

  // Microseconds since the first frame, kept strictly increasing because
  // encoders reject a repeated pts and the wall clock can step.
  int64_t now_us = std::chrono::duration_cast<Microseconds>(timestamp.time_since_epoch()).count();
  if (first_us_ == AV_NOPTS_VALUE) first_us_ = now_us;
  int64_t pts = now_us - first_us_;
  if (last_pts_ != AV_NOPTS_VALUE and pts <= last_pts_) pts = last_pts_ + 1;
  last_pts_ = pts;
  frame->pts = pts;
  if (force_keyframe) {
    frame->pict_type = AV_PICTURE_TYPE_I;
    discard_before_ = pts;
  }

  int ret = avcodec_send_frame(ctx_, frame.get());
  if (ret < 0 and ret != AVERROR(EAGAIN)) {
    Warning("StreamEncoder: %s rejected a frame: %s", active_name_.c_str(), av_make_error_string(ret).c_str());
    return false;
  }
  return Drain(sink);
}

bool StreamEncoder::Drain(const Sink &sink) {
  av_packet_ptr pkt{av_packet_alloc()};
  if (!pkt) return false;
  bool annexb_extradata = par_->extradata_size > 4
    and (par_->codec_id == AV_CODEC_ID_H264 or par_->codec_id == AV_CODEC_ID_HEVC)
    and par_->extradata[0] == 0 and par_->extradata[1] == 0;

  int ret;
  while ((ret = avcodec_receive_packet(ctx_, pkt.get())) == 0) {
    if (discard_before_ != AV_NOPTS_VALUE and pkt->pts < discard_before_) {
      av_packet_unref(pkt.get());
      continue;
    }
    bool keyframe = pkt->flags & AV_PKT_FLAG_KEY;
    const AVPacket *out = pkt.get();
    av_packet_ptr with_headers;
    // With a global header the encoder leaves the parameter sets out of the
    // stream. A consumer that starts on this keyframe needs them in band.
    if (keyframe and annexb_extradata
        and (pkt->size < par_->extradata_size
             or memcmp(pkt->data, par_->extradata, par_->extradata_size) != 0)) {
      with_headers = av_packet_ptr{av_packet_alloc()};
      if (with_headers and av_new_packet(with_headers.get(), par_->extradata_size + pkt->size) == 0) {
        memcpy(with_headers->data, par_->extradata, par_->extradata_size);
        memcpy(with_headers->data + par_->extradata_size, pkt->data, pkt->size);
        av_packet_copy_props(with_headers.get(), pkt.get());
        out = with_headers.get();
      }
    }
    sink(out, keyframe, pkt->pts);
    av_packet_unref(pkt.get());
  }
  if (ret == AVERROR(EAGAIN) or ret == AVERROR_EOF) return true;
  Warning("StreamEncoder: %s failed: %s", active_name_.c_str(), av_make_error_string(ret).c_str());
  return false;
}
