//
// ZoneMinder Ffmpeg Camera Class Implementation
// Copyright (C) 2001-2008 Philip Coombes
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
//

#include "zm_ffmpeg_camera.h"

#include "zm_ffmpeg_input.h"
#include "zm_monitor.h"
#include "zm_packet.h"
#include "zm_signal.h"
#include "zm_utils.h"

extern "C" {
#include <libavutil/time.h>
#include <libavdevice/avdevice.h>
}

TimePoint start_read_time;


FfmpegCamera::FfmpegCamera(
  const Monitor *monitor,
  const std::string &p_path,
  const std::string &p_second_path,
  const std::string &p_user,
  const std::string &p_pass,
  const std::string &p_method,
  const std::string &p_options,
  int p_width,
  int p_height,
  int p_colours,
  int p_brightness,
  int p_contrast,
  int p_hue,
  int p_colour,
  bool p_capture,
  bool p_record_audio,
  const std::string &p_hwaccel_name,
  const std::string &p_hwaccel_device) :
  Camera(
    monitor,
    FFMPEG_SRC,
    p_width,
    p_height,
    p_colours,
    ZM_SUBPIX_ORDER_DEFAULT_FOR_COLOUR(p_colours),
    p_brightness,
    p_contrast,
    p_hue,
    p_colour,
    p_capture,
    p_record_audio
  ),
  mPath(p_path),
  mSecondPath(p_second_path),
  mUser(p_user),
  mPass(p_pass),
  mMethod(p_method),
  mOptions(p_options),
  hwaccel_name(p_hwaccel_name),
  hwaccel_device(p_hwaccel_device),
  mSecondInput(nullptr),
  frameCount(0),
  use_hwaccel(true),
  mConvertContext(nullptr),
  error_count(0),
  stream_width(0),
  stream_height(0) {
  mMaskedPath = remove_authentication(mPath);
  mMaskedSecondPath = remove_authentication(mSecondPath);
  if ( capture ) {
    FFMPEGInit();
  }

#if HAVE_LIBAVUTIL_HWCONTEXT_H
  hw_device_ctx = nullptr;
#endif

  /* Has to be located inside the constructor so other components such as zma
   * will receive correct colours and subpixel order */
  if ( colours == ZM_COLOUR_RGB32 ) {
    subpixelorder = ZM_SUBPIX_ORDER_RGBA;
    imagePixFormat = AV_PIX_FMT_RGBA;
  } else if ( colours == ZM_COLOUR_RGB24 ) {
    subpixelorder = ZM_SUBPIX_ORDER_RGB;
    imagePixFormat = AV_PIX_FMT_RGB24;
  } else if ( colours == ZM_COLOUR_GRAY8 ) {
    subpixelorder = ZM_SUBPIX_ORDER_NONE;
    imagePixFormat = AV_PIX_FMT_GRAY8;
  } else {
    Panic("Unexpected colours: %d", colours);
  }

}  // FfmpegCamera::FfmpegCamera

FfmpegCamera::~FfmpegCamera() {
  Close();

  FFMPEGDeInit();
}

int FfmpegCamera::PrimeCapture() {
  start_read_time = std::chrono::steady_clock::now();
  Close();
  mVideoStreamId = -1;
  mAudioStreamId = -1;
  Debug(1, "Priming capture from %s", mMaskedPath.c_str());

  return OpenFfmpeg();
}

int FfmpegCamera::PreCapture() {
  return 0;
}

int FfmpegCamera::Capture(std::shared_ptr<ZMPacket> &zm_packet) {
  if (!mIsPrimed) return -1;

  start_read_time = std::chrono::steady_clock::now();
  int ret;
  AVFormatContext *formatContextPtr;
  int64_t lastPTS = -1;
  av_packet_ptr packet = av_packet_ptr{av_packet_alloc()};
  av_packet_guard pkt_guard{packet};

  if ( mSecondFormatContext and
       (
         av_rescale_q(mLastAudioPTS, mAudioStream->time_base, AV_TIME_BASE_Q)
         <
         av_rescale_q(mLastVideoPTS, mVideoStream->time_base, AV_TIME_BASE_Q)
       ) ) {
    // if audio stream is behind video stream, then read from audio, otherwise video
    formatContextPtr = mSecondFormatContext;
    lastPTS = mLastAudioPTS;
    Debug(4, "Using audio input because audio PTS %" PRId64 " < video PTS %" PRId64,
          av_rescale_q(mLastAudioPTS, mAudioStream->time_base, AV_TIME_BASE_Q),
          av_rescale_q(mLastVideoPTS, mVideoStream->time_base, AV_TIME_BASE_Q)
         );
    if ((ret = av_read_frame(formatContextPtr, packet.get())) < 0) {
      if (
        // Check if EOF.
        (ret == AVERROR_EOF || (formatContextPtr->pb && formatContextPtr->pb->eof_reached)) ||
        // Check for Connection failure.
        (ret == -110)
      ) {
        Info("Unable to read packet from stream %d: error %d \"%s\".",
             packet->stream_index, ret, av_make_error_string(ret).c_str());
      } else {
        logPrintf(Logger::ERROR + monitor->Importance(),
            "Unable to read packet from stream %d: error %d \"%s\".",
            packet->stream_index, ret, av_make_error_string(ret).c_str());
      }
      return -1;
    }
  } else {
    formatContextPtr = mFormatContext;
    Debug(4, "Using video input because %" PRId64 " >= %" PRId64,
          (mAudioStream?av_rescale_q(mLastAudioPTS, mAudioStream->time_base, AV_TIME_BASE_Q):0),
          av_rescale_q(mLastVideoPTS, mVideoStream->time_base, AV_TIME_BASE_Q)
         );

    if ((ret = av_read_frame(formatContextPtr, packet.get())) < 0) {
      if (
        // Check if EOF.
        (ret == AVERROR_EOF || (formatContextPtr->pb && formatContextPtr->pb->eof_reached)) ||
        // Check for Connection failure.
        (ret == -110)
      ) {
        Info("Unable to read packet from stream %d: error %d \"%s\".",
             packet->stream_index, ret, av_make_error_string(ret).c_str());
      } else {
        logPrintf(Logger::ERROR + monitor->Importance(),
            "Unable to read packet from stream %d: error %d \"%s\".",
            packet->stream_index, ret, av_make_error_string(ret).c_str());
      }
      return -1;
    }

    if ( packet->stream_index == mVideoStreamId) {
      lastPTS = mLastVideoPTS;
    } else if (packet->stream_index == mAudioStreamId) {
      lastPTS = mLastAudioPTS;
    } else {
      Debug(1, "Have packet (%d) which isn't for video (%d) or audio stream (%d).", packet->stream_index, mVideoStreamId, mAudioStreamId);
      return 0;
    }
  }

  AVStream *stream = formatContextPtr->streams[packet->stream_index];
  ZM_DUMP_STREAM_PACKET(stream, packet, "ffmpeg_camera in");

  if ((packet->pts != AV_NOPTS_VALUE) and (lastPTS >= 0)) {
    if (packet->pts < 0) {
      // 32-bit wrap around?
      Info("Suspected 32bit wraparound in input pts. %" PRId64, packet->pts);
      return -1;
    } else if (packet->pts - lastPTS < -10*stream->time_base.den) {
      if (!monitor->WallClockTimestamps()) {
        // -10 is for 10 seconds. Avigilon cameras seem to jump around by about 36 constantly
        double pts_time = static_cast<double>(av_rescale_q(packet->pts, stream->time_base, AV_TIME_BASE_Q)) / AV_TIME_BASE;
        double last_pts_time = static_cast<double>(av_rescale_q(lastPTS, stream->time_base, AV_TIME_BASE_Q)) / AV_TIME_BASE;
        logPrintf(Logger::WARNING + monitor->Importance(), "Stream pts jumped back in time too far. pts %.2f - last pts %.2f = %.2f > 10seconds",
                  pts_time, last_pts_time, pts_time - last_pts_time);
      }
      if (error_count > 5)
        return -1;
      error_count += 1;
      return 0;
    }
  }



  zm_packet->codec_type = stream->codecpar->codec_type;

  bytes += packet->size;
  zm_packet->set_packet(packet.get());
  zm_packet->stream = stream;
  zm_packet->pts = av_rescale_q(packet->pts, stream->time_base, AV_TIME_BASE_Q);
  if (packet->pts != AV_NOPTS_VALUE) {
    if (stream == mVideoStream) {
      if (mFirstVideoPTS == AV_NOPTS_VALUE)
        mFirstVideoPTS = packet->pts;

      mLastVideoPTS = packet->pts - mFirstVideoPTS;
    } else if (stream == mAudioStream) {
      if (mFirstAudioPTS == AV_NOPTS_VALUE)
        mFirstAudioPTS = packet->pts;

      mLastAudioPTS = packet->pts - mFirstAudioPTS;
    }
  }

  return 1;
} // FfmpegCamera::Capture

int FfmpegCamera::PostCapture() {
  // Nothing to do here
  return 0;
}

int FfmpegCamera::OpenFfmpeg() {
  int ret = 0;
  error_count = 0;

#if LIBAVFORMAT_VERSION_CHECK(59, 16, 100, 16, 100)
  const
#endif
  AVInputFormat *input_format = nullptr;
  // Handle options
  AVDictionary *opts = nullptr;
  if (!mOptions.empty()) {
    ret = av_dict_parse_string(&opts, mOptions.c_str(), "=", ",", 0);
    if (ret < 0) {
      Warning("Could not parse ffmpeg input options '%s'", mOptions.c_str());
    }
    av_dict_set(&opts, "xcoder-params", nullptr, AV_DICT_MATCH_CASE);
  }

  // Set transport method as specified by method field, rtpUni is default
  std::string protocol = mPath.substr(0, 4);
  protocol = StringToUpper(protocol);
  if ( protocol == "RTSP" ) {
    const std::string method = Method();
    if ( method == "rtpMulti" ) {
      ret = av_dict_set(&opts, "rtsp_transport", "udp_multicast", 0);
    } else if ( method == "rtpRtsp" ) {
      ret = av_dict_set(&opts, "rtsp_transport", "tcp", 0);
    } else if ( method == "rtpRtspHttp" ) {
      ret = av_dict_set(&opts, "rtsp_transport", "http", 0);
    } else if ( method == "rtpUni" ) {
      ret = av_dict_set(&opts, "rtsp_transport", "udp", 0);
    } else {
      Warning("Unknown method (%s)", method.c_str());
    }
    if (ret < 0) {
      Warning("Could not set rtsp_transport method '%s'", method.c_str());
    }
  } else if (protocol == "V4L2") {
    avdevice_register_all();
    input_format = av_find_input_format("video4linux2");
    if (!input_format) {
      Error("Cannot find v4l2 input format");
      return -1;
    }
    mPath = mPath.substr(7);
  }  // end if RTSP

  Debug(1, "Calling avformat_open_input for %s", mMaskedPath.c_str());

  mFormatContext = avformat_alloc_context();
  mFormatContext->interrupt_callback.callback = FfmpegInterruptCallback;
  mFormatContext->interrupt_callback.opaque = this;
  mFormatContext->flags |= AVFMT_FLAG_NOBUFFER | AVFMT_FLAG_FLUSH_PACKETS;

  if (mUser.length() > 0) {
    // build the actual uri string with encoded parameters (from the user and pass fields)
    mPath = StringToLower(protocol) + "://" + mUser + ":" + UriEncode(mPass) + "@" + mMaskedPath.substr(7, std::string::npos);
    Debug(1, "Rebuilt URI with encoded parameters: '%s'", mPath.c_str());
  }

  ret = avformat_open_input(&mFormatContext, mPath.c_str(), input_format, &opts);
  if (ret != 0) {
    logPrintf(Logger::ERROR + monitor->Importance(),
              "Unable to open input %s due to: %s", mMaskedPath.c_str(),
              av_make_error_string(ret).c_str());
    avformat_close_input(&mFormatContext);
    mFormatContext = nullptr;
    av_dict_free(&opts);
    return -1;
  }

  AVDictionaryEntry *e = nullptr;
  while ((e = av_dict_get(opts, "", e, AV_DICT_IGNORE_SUFFIX)) != nullptr) {
    Warning("Option %s not recognized by ffmpeg", e->key);
  }
  av_dict_free(&opts);

  ret = avformat_find_stream_info(mFormatContext, nullptr);
  if (ret < 0) {
    Error("Unable to find stream info from %s due to: %s",
          mMaskedPath.c_str(), av_make_error_string(ret).c_str());
    avformat_close_input(&mFormatContext);
    return -1;
  }

  // Find first video stream present
  // The one we want Might not be the first
  mVideoStreamId = -1;
  mAudioStreamId = -1;
  for (unsigned int i=0; i < mFormatContext->nb_streams; i++) {
    AVStream *stream = mFormatContext->streams[i];
    zm_dump_stream_format(mFormatContext, i, 0, 0);
    if (is_video_stream(stream)) {
      if (!(stream->codecpar->width && stream->codecpar->height)) {
        Warning("No width and height in video stream. Trying again");
        continue;
      }
      if (mVideoStreamId == -1) {
        mVideoStreamId = i;
        mVideoStream = stream;
      } else {
        Debug(2, "Have another video stream.");
        std::list<const CodecData *>codec_data = get_decoder_data(stream->codecpar->codec_id, "auto");

        if (codec_data.size() && (stream->codecpar->width == width) and (stream->codecpar->height == height)) {
          Debug(1, "Choosing alternate video stream because it matches our resolution.");
          mVideoStreamId = i;
          mVideoStream = stream;
        } else {
          stream->discard = AVDISCARD_ALL;
        }
      }
    } else if (is_audio_stream(stream)) {
      Debug(1, "Have audio stream");
      if (!monitor->RecordAudio()) {
        Debug(1, "Have audio stream, but discarding");
        stream->discard = AVDISCARD_ALL;
      } else {
        if (mAudioStreamId == -1) {
          mAudioStreamId = i;
          mAudioStream = stream;
        } else {
          Debug(2, "Have another audio stream.");
        }
      }
    } else {
	    Debug(1, "Unknown stream type for stream %d", i);
    }
  }  // end foreach stream

  if (mVideoStreamId == -1) {
    avformat_close_input(&mFormatContext);
    return -1;
  }

  width = mVideoStream->codecpar->width;
  height = mVideoStream->codecpar->height;

  Debug(3, "Found video stream at index %d, audio stream at index %d",
        mVideoStreamId, mAudioStreamId);

#if 0
  const AVCodec *mVideoCodec = nullptr;

  std::list<const CodecData *>codec_data = get_decoder_data(mFormatContext->streams[mVideoStreamId]->codecpar->codec_id, "auto");
  for (auto it = codec_data.begin(); it != codec_data.end(); it ++) {
    const CodecData *chosen_codec_data = *it;
    Debug(1, "Found codec %s", chosen_codec_data->codec_name);
 

    if (!monitor->DecoderName().empty() and (monitor->DecoderName() != "auto")) {
      if (monitor->DecoderName() != chosen_codec_data->codec_name) {
        Debug(1, "Not the specified codec.");
        continue;
      }
    }
    mVideoCodec = avcodec_find_decoder_by_name(chosen_codec_data->codec_name);

    if (!mVideoCodec) {
      mVideoCodec = avcodec_find_decoder(mVideoStream->codecpar->codec_id);
      if (!mVideoCodec) {
        // Try and get the codec from the codec context
        Error("Can't find codec for video stream from %s", mMaskedPath.c_str());
        continue;
      }
    }

    mVideoCodecContext = avcodec_alloc_context3(mVideoCodec);
    avcodec_parameters_to_context(mVideoCodecContext, mFormatContext->streams[mVideoStreamId]->codecpar);

#ifdef CODEC_FLAG2_FAST
    mVideoCodecContext->flags2 |= CODEC_FLAG2_FAST | CODEC_FLAG_LOW_DELAY;
#endif

    zm_dump_stream_format(mFormatContext, mVideoStreamId, 0, 0);

    if (use_hwaccel && (hwaccel_name != "")) {
      if (0 && setup_hwaccel(mVideoCodecContext,
               chosen_codec_data, hw_device_ctx, "", mFormatContext->streams[mVideoStreamId]->codecpar->width,
               mFormatContext->streams[mVideoStreamId]->codecpar->height
               )) {
        avcodec_free_context(&mVideoCodecContext);
        continue;
      }
    }  // end if hwaccel_name

    if (!mOptions.empty()) {
      ret = av_dict_parse_string(&opts, mOptions.c_str(), "=", ",", 0);
      // reorder_queue is for avformat not codec
      av_dict_set(&opts, "reorder_queue_size", nullptr, AV_DICT_MATCH_CASE);
      av_dict_set(&opts, "probesize", nullptr, AV_DICT_MATCH_CASE);
    }
    mVideoCodecContext->framerate = av_guess_frame_rate(mFormatContext, mFormatContext->streams[mVideoStreamId], NULL);

    av_opt_set(mVideoCodecContext->priv_data, "dec", (hwaccel_device != "" ? hwaccel_device.c_str() : "-1"), 0);
  //av_opt_set(mVideoCodecContext->priv_data, "xcoder-params","out=hw", 0);

    ret = avcodec_open2(mVideoCodecContext, mVideoCodec, &opts);

    e = nullptr;
    while ((e = av_dict_get(opts, "", e, AV_DICT_IGNORE_SUFFIX)) != nullptr) {
      Warning("Option %s not recognized by ffmpeg", e->key);
    }
    av_dict_free(&opts);
    if (ret < 0) {
      Error("Unable to open codec for video stream from %s", mMaskedPath.c_str());
      avcodec_free_context(&mVideoCodecContext);
      continue;
    }
    zm_dump_codec(mVideoCodecContext);
    break;
  }
  if (!mVideoCodecContext) {
    return -1;
  }

  if (mAudioStreamId >= 0) {
    const AVCodec *mAudioCodec = nullptr;
    if (!(mAudioCodec = avcodec_find_decoder(mAudioStream->codecpar->codec_id))) {
      Debug(1, "Can't find codec for audio stream from %s", mMaskedPath.c_str());
    } else {
      mAudioCodecContext = avcodec_alloc_context3(mAudioCodec);
      avcodec_parameters_to_context(mAudioCodecContext, mAudioStream->codecpar);

      zm_dump_stream_format((mSecondFormatContext?mSecondFormatContext:mFormatContext), mAudioStreamId, 0, 0);
      // Open the codec
      if (avcodec_open2(mAudioCodecContext, mAudioCodec, nullptr) < 0) {
        Error("Unable to open codec for audio stream from %s", mMaskedPath.c_str());
        return -1;
      }  // end if opened
    }  // end if found decoder
  } else if (!monitor->GetSecondPath().empty()) {
    Debug(1, "Trying secondary stream at %s", monitor->GetSecondPath().c_str());
    mSecondInput = zm::make_unique<FFmpeg_Input>();
    if (mSecondInput->Open(monitor->GetSecondPath().c_str()) > 0) {
      mSecondFormatContext = mSecondInput->get_format_context();
      mAudioStreamId = mSecondInput->get_audio_stream_id();
      mAudioStream = mSecondInput->get_audio_stream();
      mAudioCodecContext = mSecondInput->get_audio_codec_context();
    } else {
      Warning("Failed to open secondary input");
    }
  }  // end if have audio stream

  if (
    ((unsigned int)mVideoCodecContext->width != width)
    ||
    ((unsigned int)mVideoCodecContext->height != height)
  ) {
    Debug(1, "Monitor dimensions are %dx%d but camera is sending %dx%d",
          width, height, mVideoCodecContext->width, mVideoCodecContext->height);
  }
#endif

  mIsPrimed = true;

  return 1;
} // int FfmpegCamera::OpenFfmpeg()

int FfmpegCamera::Close() {
  mIsPrimed = false;
  mLastVideoPTS = 0;
  mLastAudioPTS = 0;

  if (mVideoCodecContext) {
    avcodec_free_context(&mVideoCodecContext);
    mVideoCodecContext = nullptr;
  }

  if (mAudioCodecContext and !mSecondInput) {
    // If second input, then these will get freed in FFmpeg_Input's destructor
    avcodec_free_context(&mAudioCodecContext);
    mAudioCodecContext = nullptr;
  }

#if HAVE_LIBAVUTIL_HWCONTEXT_H
  if ( hw_device_ctx ) {
    av_buffer_unref(&hw_device_ctx);
  }
#endif

  if ( mFormatContext ) {
    avformat_close_input(&mFormatContext);
    mFormatContext = nullptr;
  }

  return 0;
}  // end FfmpegCamera::Close

int FfmpegCamera::FfmpegInterruptCallback(void *ctx) {
  if (zm_terminate) {
    Debug(1, "Received terminate in cb");
    return zm_terminate;
  }

  TimePoint now = std::chrono::steady_clock::now();
  if (now - start_read_time > Seconds(10)) {
    Debug(1, "timeout in ffmpeg camera now %" PRIi64 " - %" PRIi64 " > 10 s",
          static_cast<int64>(std::chrono::duration_cast<Seconds>(now.time_since_epoch()).count()),
          static_cast<int64>(std::chrono::duration_cast<Seconds>(start_read_time.time_since_epoch()).count()));
    return 1;
  }
  return 0;
}
