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

#include "zm.h"
#include "zm_signal.h"
#include "zm_utils.h"

#if HAVE_LIBAVFORMAT

#include "zm_ffmpeg_camera.h"

extern "C" {
#include "libavutil/time.h"
#if HAVE_LIBAVUTIL_HWCONTEXT_H
  #include "libavutil/hwcontext.h"
#endif
#include "libavutil/pixdesc.h"
}
#ifndef AV_ERROR_MAX_STRING_SIZE
#define AV_ERROR_MAX_STRING_SIZE 64
#endif

#include <string>


#if HAVE_LIBAVUTIL_HWCONTEXT_H
#if LIBAVCODEC_VERSION_CHECK(57, 89, 0, 89, 0)
static enum AVPixelFormat hw_pix_fmt;
static enum AVPixelFormat get_hw_format(
    AVCodecContext *ctx,
    const enum AVPixelFormat *pix_fmts
) {
  const enum AVPixelFormat *p;

  for ( p = pix_fmts; *p != -1; p++ ) {
    if ( *p == hw_pix_fmt )
      return *p;
  }

  Error("Failed to get HW surface format for %s.",
      av_get_pix_fmt_name(hw_pix_fmt));
  for ( p = pix_fmts; *p != -1; p++ )
    Error("Available HW surface format was %s.",
        av_get_pix_fmt_name(*p));

  return AV_PIX_FMT_NONE;
}
#if !LIBAVUTIL_VERSION_CHECK(56, 22, 0, 14, 0)
static enum AVPixelFormat find_fmt_by_hw_type(const enum AVHWDeviceType type) {
    enum AVPixelFormat fmt;
    switch (type) {
    case AV_HWDEVICE_TYPE_VAAPI:
        fmt = AV_PIX_FMT_VAAPI;
        break;
    case AV_HWDEVICE_TYPE_DXVA2:
        fmt = AV_PIX_FMT_DXVA2_VLD;
        break;
    case AV_HWDEVICE_TYPE_D3D11VA:
        fmt = AV_PIX_FMT_D3D11;
        break;
    case AV_HWDEVICE_TYPE_VDPAU:
        fmt = AV_PIX_FMT_VDPAU;
        break;
    case AV_HWDEVICE_TYPE_CUDA:
        fmt = AV_PIX_FMT_CUDA;
        break;
    case AV_HWDEVICE_TYPE_VIDEOTOOLBOX:
        fmt = AV_PIX_FMT_VIDEOTOOLBOX;
        break;
    default:
        fmt = AV_PIX_FMT_NONE;
        break;
    }
    return fmt;
}
#endif
#endif
#endif

FfmpegCamera::FfmpegCamera(
    int p_id,
    const std::string &p_path,
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
      p_id,
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
  mMethod(p_method),
  mOptions(p_options),
  hwaccel_name(p_hwaccel_name),
  hwaccel_device(p_hwaccel_device)
{
  if ( capture ) {
    Initialise();
  }

  mFormatContext = NULL;
  mVideoStreamId = -1;
  mAudioStreamId = -1;
  mVideoCodecContext = NULL;
  mAudioCodecContext = NULL;
  mVideoCodec = NULL;
  mAudioCodec = NULL;
  mRawFrame = NULL;
  mFrame = NULL;
  frameCount = 0;
  mCanCapture = false;
  videoStore = NULL;
  have_video_keyframe = false;
  packetqueue = NULL;
  error_count = 0;
#if HAVE_LIBAVUTIL_HWCONTEXT_H
  hwFrame = NULL;
  hw_device_ctx = NULL;
#if LIBAVCODEC_VERSION_CHECK(57, 89, 0, 89, 0)
  hw_pix_fmt = AV_PIX_FMT_NONE;
#endif
#endif

#if HAVE_LIBSWSCALE
  mConvertContext = NULL;
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

  if ( capture ) {
    Terminate();
  }
  FFMPEGDeInit();
}

void FfmpegCamera::Initialise() {
  FFMPEGInit();
}

void FfmpegCamera::Terminate() {
}

int FfmpegCamera::PrimeCapture() {
  if ( mCanCapture ) {
    Info("Priming capture from %s, Closing", mPath.c_str());
    Close();
  }
  mVideoStreamId = -1;
  mAudioStreamId = -1;
  Info("Priming capture from %s", mPath.c_str());

  return OpenFfmpeg();
}

int FfmpegCamera::PreCapture() {
  // If Reopen was called, then ffmpeg is closed and we need to reopen it.
  if ( !mCanCapture )
    return OpenFfmpeg();
  // Nothing to do here
  return 0;
}

int FfmpegCamera::Capture(Image &image) {
  if ( !mCanCapture ) {
    return -1;
  }

  int ret;
  // If the reopen thread has a value, but mCanCapture != 0, then we have just
  // reopened the connection to the device, and we can clean up the thread.

  int frameComplete = false;
  while ( !frameComplete && !zm_terminate ) {
    ret = av_read_frame(mFormatContext, &packet);
    if ( ret < 0 ) {
      if (
          // Check if EOF.
          (
           ret == AVERROR_EOF
           ||
           (mFormatContext->pb && mFormatContext->pb->eof_reached)
          ) ||
          // Check for Connection failure.
          (ret == -110)
         ) {
        Info("Unable to read packet from stream %d: error %d \"%s\".",
            packet.stream_index, ret, av_make_error_string(ret).c_str());
      } else {
        Error("Unable to read packet from stream %d: error %d \"%s\".",
            packet.stream_index, ret, av_make_error_string(ret).c_str());
      }
      return -1;
    }
    bytes += packet.size;

    int keyframe = packet.flags & AV_PKT_FLAG_KEY;
    if ( keyframe )
      have_video_keyframe = true;

    Debug(5, "Got packet from stream %d dts (%d) pts(%d)",
        packet.stream_index, packet.pts, packet.dts);
    // What about audio stream? Maybe someday we could do sound detection...
    if (
        (packet.stream_index == mVideoStreamId)
        &&
        (keyframe || have_video_keyframe)
        ) {
      ret = zm_send_packet_receive_frame(mVideoCodecContext, mRawFrame, packet);
      if ( ret < 0 ) {
        if ( AVERROR(EAGAIN) != ret ) {
          Warning("Unable to receive frame %d: code %d %s. error count is %d",
              frameCount, ret, av_make_error_string(ret).c_str(), error_count);
          error_count += 1;
          if ( error_count > 100 ) {
            Error("Error count over 100, going to close and re-open stream");
            return -1;
          }
        }
        zm_av_packet_unref(&packet);
        continue;
      }

      frameComplete = 1;
      zm_dump_video_frame(mRawFrame, "raw frame from decoder");

#if HAVE_LIBAVUTIL_HWCONTEXT_H
#if LIBAVCODEC_VERSION_CHECK(57, 89, 0, 89, 0)
      if (
          (hw_pix_fmt != AV_PIX_FMT_NONE)
          &&
          (mRawFrame->format == hw_pix_fmt)
         ) {
        /* retrieve data from GPU to CPU */
        ret = av_hwframe_transfer_data(hwFrame, mRawFrame, 0);
        if ( ret < 0 ) {
          Error("Unable to transfer frame at frame %d: %s, continuing",
              frameCount, av_make_error_string(ret).c_str());
          zm_av_packet_unref(&packet);
          continue;
        }
        zm_dump_video_frame(hwFrame, "After hwtransfer");

        hwFrame->pts = mRawFrame->pts;
        input_frame = hwFrame;
      } else {
#endif
#endif
        input_frame = mRawFrame;
#if HAVE_LIBAVUTIL_HWCONTEXT_H
#if LIBAVCODEC_VERSION_CHECK(57, 89, 0, 89, 0)
      }
#endif
#endif

      if ( transfer_to_image(image, mFrame, input_frame) < 0 ) {
        zm_av_packet_unref(&packet);
        return -1;
      }

      frameCount++;
    } else {
      Debug(4, "Different stream_index %d", packet.stream_index);
    }  // end if packet.stream_index == mVideoStreamId
    zm_av_packet_unref(&packet);
  }  // end while ! frameComplete
  return frameComplete ? 1 : 0;
}  // FfmpegCamera::Capture

int FfmpegCamera::PostCapture() {
  // Nothing to do here
  return 0;
}

int FfmpegCamera::OpenFfmpeg() {
  int ret;

  have_video_keyframe = false;
  error_count = 0;

  // Open the input, not necessarily a file
#if !LIBAVFORMAT_VERSION_CHECK(53, 2, 0, 4, 0)
  if ( av_open_input_file(&mFormatContext, mPath.c_str(), NULL, 0, NULL) != 0 )
#else
  // Handle options
  AVDictionary *opts = 0;
  ret = av_dict_parse_string(&opts, Options().c_str(), "=", ",", 0);
  if ( ret < 0 ) {
    Warning("Could not parse ffmpeg input options '%s'", Options().c_str());
  }

  // Set transport method as specified by method field, rtpUni is default
  std::string protocol = mPath.substr(0, 4);
  string_toupper(protocol);
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
    if ( ret < 0 ) {
      Warning("Could not set rtsp_transport method '%s'", method.c_str());
    }
  }  // end if RTSP
  // #av_dict_set(&opts, "timeout", "10000000", 0); // in microseconds.

  Debug(1, "Calling avformat_open_input for %s", mPath.c_str());

  mFormatContext = avformat_alloc_context();
  // Speed up find_stream_info
  // FIXME can speed up initial analysis but need sensible parameters...
  // mFormatContext->probesize = 32;
  // mFormatContext->max_analyze_duration = 32;
  mFormatContext->interrupt_callback.callback = FfmpegInterruptCallback;
  mFormatContext->interrupt_callback.opaque = this;

  ret = avformat_open_input(&mFormatContext, mPath.c_str(), NULL, &opts);
  if ( ret != 0 )
#endif
  {
    Error("Unable to open input %s due to: %s", mPath.c_str(),
        av_make_error_string(ret).c_str());
#if !LIBAVFORMAT_VERSION_CHECK(53, 17, 0, 25, 0)
    av_close_input_file(mFormatContext);
#else
    if ( mFormatContext ) {
      avformat_close_input(&mFormatContext);
      mFormatContext = NULL;
    }
#endif
    av_dict_free(&opts);

    return -1;
  }
  AVDictionaryEntry *e = NULL;
  while ( (e = av_dict_get(opts, "", e, AV_DICT_IGNORE_SUFFIX)) != NULL ) {
    Warning("Option %s not recognized by ffmpeg", e->key);
  }
  av_dict_free(&opts);

#if !LIBAVFORMAT_VERSION_CHECK(53, 6, 0, 6, 0)
  ret = av_find_stream_info(mFormatContext);
#else
  ret = avformat_find_stream_info(mFormatContext, 0);
#endif
  if ( ret < 0 ) {
    Error("Unable to find stream info from %s due to: %s",
        mPath.c_str(), av_make_error_string(ret).c_str());
    return -1;
  }

  // Find first video stream present
  // The one we want Might not be the first
  mVideoStreamId = -1;
  mAudioStreamId = -1;
  for ( unsigned int i=0; i < mFormatContext->nb_streams; i++ ) {
    AVStream *stream = mFormatContext->streams[i];
    if ( is_video_stream(stream) ) {
      if ( mVideoStreamId == -1 ) {
        mVideoStreamId = i;
        // if we break, then we won't find the audio stream
        continue;
      } else {
        Debug(2, "Have another video stream.");
      }
    } else if ( is_audio_stream(stream) ) {
      if ( mAudioStreamId == -1 ) {
        mAudioStreamId = i;
      } else {
        Debug(2, "Have another audio stream.");
      }
    }
  }  // end foreach stream
  if ( mVideoStreamId == -1 ) {
    Error("Unable to locate video stream in %s", mPath.c_str());
    return -1;
  }

  Debug(3, "Found video stream at index %d, audio stream at index %d",
      mVideoStreamId, mAudioStreamId);
  packetqueue = new zm_packetqueue(
      (mVideoStreamId > mAudioStreamId) ? mVideoStreamId : mAudioStreamId);

#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
  // mVideoCodecContext = avcodec_alloc_context3(NULL);
  // avcodec_parameters_to_context(mVideoCodecContext,
  // mFormatContext->streams[mVideoStreamId]->codecpar);
  // this isn't copied.
  // mVideoCodecContext->time_base =
  // mFormatContext->streams[mVideoStreamId]->codec->time_base;
#else
#endif
  mVideoCodecContext = mFormatContext->streams[mVideoStreamId]->codec;
#ifdef CODEC_FLAG2_FAST
  mVideoCodecContext->flags2 |= CODEC_FLAG2_FAST | CODEC_FLAG_LOW_DELAY;
#endif

  if ( mVideoCodecContext->codec_id == AV_CODEC_ID_H264 ) {
    if ( (mVideoCodec = avcodec_find_decoder_by_name("h264_mmal")) == NULL ) {
      Debug(1, "Failed to find decoder (h264_mmal)");
    } else {
      Debug(1, "Success finding decoder (h264_mmal)");
    }
  }

  if ( !mVideoCodec ) {
    mVideoCodec = avcodec_find_decoder(mVideoCodecContext->codec_id);
    if ( !mVideoCodec ) {
      // Try and get the codec from the codec context
      Error("Can't find codec for video stream from %s", mPath.c_str());
      return -1;
    }
  }

  zm_dump_stream_format(mFormatContext, mVideoStreamId, 0, 0);

  if ( hwaccel_name != "" ) {
#if HAVE_LIBAVUTIL_HWCONTEXT_H
    // 3.2 doesn't seem to have all the bits in place, so let's require 3.3 and up
#if LIBAVCODEC_VERSION_CHECK(57, 89, 0, 89, 0)
// Print out available types
    enum AVHWDeviceType type = AV_HWDEVICE_TYPE_NONE;
    while ( (type = av_hwdevice_iterate_types(type)) != AV_HWDEVICE_TYPE_NONE )
      Debug(1, "%s", av_hwdevice_get_type_name(type));

    const char *hw_name = hwaccel_name.c_str();
    type = av_hwdevice_find_type_by_name(hw_name);
    if ( type == AV_HWDEVICE_TYPE_NONE ) {
      Debug(1, "Device type %s is not supported.", hw_name);
    } else {
      Debug(1, "Found hwdevice %s", av_hwdevice_get_type_name(type));
    }

#if LIBAVUTIL_VERSION_CHECK(56, 22, 0, 14, 0)
    // Get hw_pix_fmt
    for ( int i = 0;; i++ ) {
      const AVCodecHWConfig *config = avcodec_get_hw_config(mVideoCodec, i);
      if ( !config ) {
        Debug(1, "Decoder %s does not support device type %s.",
            mVideoCodec->name, av_hwdevice_get_type_name(type));
        break;
      }
      if ( (config->methods & AV_CODEC_HW_CONFIG_METHOD_HW_DEVICE_CTX)
          && (config->device_type == type)
          ) {
        hw_pix_fmt = config->pix_fmt;
        break;
      } else {
        Debug(1, "decoder %s hwConfig doesn't match our type: %s, pix_fmt %s.",
            mVideoCodec->name,
            av_hwdevice_get_type_name(config->device_type),
            av_get_pix_fmt_name(config->pix_fmt)
            );
      }
    }  // end foreach hwconfig
#else
    hw_pix_fmt = find_fmt_by_hw_type(type);
#endif
    if ( hw_pix_fmt != AV_PIX_FMT_NONE ) {
      Debug(1, "Selected hw_pix_fmt %d %s",
          hw_pix_fmt, av_get_pix_fmt_name(hw_pix_fmt));

      ret = av_hwdevice_ctx_create(&hw_device_ctx, type,
          (hwaccel_device != "" ? hwaccel_device.c_str(): NULL), NULL, 0);
      if ( ret < 0 ) {
        Error("Failed to create hwaccel device. %s",av_make_error_string(ret).c_str());
        hw_pix_fmt = AV_PIX_FMT_NONE;
      } else {
        Debug(1, "Created hwdevice for %s", hwaccel_device.c_str());
        mVideoCodecContext->get_format = get_hw_format;
        mVideoCodecContext->hw_device_ctx = av_buffer_ref(hw_device_ctx);
        hwFrame = zm_av_frame_alloc();
      }
    } else {
      Debug(1, "Failed to find suitable hw_pix_fmt.");
    }
#else
    Debug(1, "AVCodec not new enough for hwaccel");
#endif
#else
    Warning("HWAccel support not compiled in.");
#endif
  }  // end if hwaccel_name

  // Open the codec
#if !LIBAVFORMAT_VERSION_CHECK(53, 8, 0, 8, 0)
  ret = avcodec_open(mVideoCodecContext, mVideoCodec);
#else
  ret = avcodec_open2(mVideoCodecContext, mVideoCodec, &opts);
#endif
  e = NULL;
  while ( (e = av_dict_get(opts, "", e, AV_DICT_IGNORE_SUFFIX)) != NULL ) {
    Warning("Option %s not recognized by ffmpeg", e->key);
  }
  if ( ret < 0 ) {
    Error("Unable to open codec for video stream from %s", mPath.c_str());
    av_dict_free(&opts);
    return -1;
  }
  zm_dump_codec(mVideoCodecContext);

  Debug(1, hwFrame ? "HWACCEL in use" : "HWACCEL not in use");

  if ( mAudioStreamId >= 0 ) {
    if ( (mAudioCodec = avcodec_find_decoder(
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
            mFormatContext->streams[mAudioStreamId]->codecpar->codec_id
#else
            mFormatContext->streams[mAudioStreamId]->codec->codec_id
#endif
            )) == NULL ) {
      Debug(1, "Can't find codec for audio stream from %s", mPath.c_str());
    } else {
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
      mAudioCodecContext = avcodec_alloc_context3(mAudioCodec);
      avcodec_parameters_to_context(
          mAudioCodecContext,
          mFormatContext->streams[mAudioStreamId]->codecpar
          );
#else
      mAudioCodecContext = mFormatContext->streams[mAudioStreamId]->codec;
     // = avcodec_alloc_context3(mAudioCodec);
#endif

      zm_dump_stream_format(mFormatContext, mAudioStreamId, 0, 0);
      // Open the codec
#if !LIBAVFORMAT_VERSION_CHECK(53, 8, 0, 8, 0)
      if ( avcodec_open(mAudioCodecContext, mAudioCodec) < 0 ) {
#else
      if ( avcodec_open2(mAudioCodecContext, mAudioCodec, 0) < 0 ) {
#endif
        Error("Unable to open codec for audio stream from %s", mPath.c_str());
        return -1;
      }
      zm_dump_codec(mAudioCodecContext);
    }  // end if find decoder
  }  // end if have audio_context

  // Allocate space for the native video frame
  mRawFrame = zm_av_frame_alloc();

  // Allocate space for the converted video frame
  mFrame = zm_av_frame_alloc();

  if ( mRawFrame == NULL || mFrame == NULL ) {
    Error("Unable to allocate frame for %s", mPath.c_str());
    return -1;
  }
  mFrame->width = width;
  mFrame->height = height;

#if LIBAVUTIL_VERSION_CHECK(54, 6, 0, 6, 0)
  int pSize = av_image_get_buffer_size(imagePixFormat, width, height, 1);
#else
  int pSize = avpicture_get_size(imagePixFormat, width, height);
#endif

  if ( (unsigned int)pSize != imagesize ) {
    Error("Image size mismatch. Required: %d Available: %d", pSize, imagesize);
    return -1;
  }

#if HAVE_LIBSWSCALE
  if ( !sws_isSupportedInput(mVideoCodecContext->pix_fmt) ) {
    Error("swscale does not support the codec format for input: %s",
        av_get_pix_fmt_name(mVideoCodecContext->pix_fmt)
        );
    return -1;
  }

  if ( !sws_isSupportedOutput(imagePixFormat) ) {
    Error("swscale does not support the target format: %s",
        av_get_pix_fmt_name(imagePixFormat)
        );
    return -1;
  }

#else  // HAVE_LIBSWSCALE
  Fatal("You must compile ffmpeg with the --enable-swscale "
      "option to use ffmpeg cameras");
#endif  // HAVE_LIBSWSCALE

  if (
      ((unsigned int)mVideoCodecContext->width != width)
      ||
      ((unsigned int)mVideoCodecContext->height != height)
      ) {
    Warning("Monitor dimensions are %dx%d but camera is sending %dx%d",
        width, height, mVideoCodecContext->width, mVideoCodecContext->height);
  }

  mCanCapture = true;

  return 0;
}  // int FfmpegCamera::OpenFfmpeg()

int FfmpegCamera::Close() {
  Debug(2, "CloseFfmpeg called.");

  mCanCapture = false;

  if ( mFrame ) {
    av_frame_free(&mFrame);
    mFrame = NULL;
  }
  if ( mRawFrame ) {
    av_frame_free(&mRawFrame);
    mRawFrame = NULL;
  }
#if HAVE_LIBAVUTIL_HWCONTEXT_H
  if ( hwFrame ) {
    av_frame_free(&hwFrame);
    hwFrame = NULL;
  }
#endif

#if HAVE_LIBSWSCALE
  if ( mConvertContext ) {
    sws_freeContext(mConvertContext);
    mConvertContext = NULL;
  }
#endif

  if ( videoStore ) {
    delete videoStore;
    videoStore = NULL;
  }

  if ( mVideoCodecContext ) {
    avcodec_close(mVideoCodecContext);
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
    // avcodec_free_context(&mVideoCodecContext);
#endif
    mVideoCodecContext = NULL;  // Freed by av_close_input_file
  }
  if ( mAudioCodecContext ) {
    avcodec_close(mAudioCodecContext);
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
    avcodec_free_context(&mAudioCodecContext);
#endif
    mAudioCodecContext = NULL;  // Freed by av_close_input_file
  }

#if HAVE_LIBAVUTIL_HWCONTEXT_H
  if ( hw_device_ctx ) {
    av_buffer_unref(&hw_device_ctx);
  }
#endif

  if ( mFormatContext ) {
#if !LIBAVFORMAT_VERSION_CHECK(53, 17, 0, 25, 0)
    av_close_input_file(mFormatContext);
#else
    avformat_close_input(&mFormatContext);
#endif
    mFormatContext = NULL;
  }

  if ( packetqueue ) {
    delete packetqueue;
    packetqueue = NULL;
  }

  return 0;
}  // end FfmpegCamera::Close

// Function to handle capture and store
int FfmpegCamera::CaptureAndRecord(
    Image &image,
    timeval recording,
    char* event_file
    ) {
  if ( !mCanCapture ) {
    return -1;
  }
  int ret;

  struct timeval video_buffer_duration = monitor->GetVideoBufferDuration();

  int frameComplete = false;
  while ( !frameComplete ) {
    av_init_packet(&packet);

    ret = av_read_frame(mFormatContext, &packet);
    if ( ret < 0 ) {
      if (
          // Check if EOF.
          (
           (ret == AVERROR_EOF) ||
           (mFormatContext->pb && mFormatContext->pb->eof_reached)
           ) ||
          // Check for Connection failure.
          (ret == -110)
         ) {
        Info("Unable to read packet from stream %d: error %d \"%s\".",
            packet.stream_index, ret, av_make_error_string(ret).c_str());
      } else {
        Error("Unable to read packet from stream %d: error %d \"%s\".",
            packet.stream_index, ret, av_make_error_string(ret).c_str());
      }
      return -1;
    }

    if ( (packet.pts != AV_NOPTS_VALUE) && (packet.pts < -100000) ) {
      // Ignore packets that have crazy negative pts.
      // They aren't supposed to happen.
      Warning("Ignore packet because pts %" PRId64 " is massively negative."
         " Error count is %d", packet.pts, error_count);
      dumpPacket(
          mFormatContext->streams[packet.stream_index],
          &packet,
          "Ignored packet");
      if ( error_count > 100 ) {
        Error("Bad packet count over 100, going to close and re-open stream");
        return -1;
      }
      error_count += 1;
      continue;
    }
    // If we get a good frame, decrease the error count.. We could zero it...
    if ( error_count > 0 ) error_count -= 1;

    int keyframe = packet.flags & AV_PKT_FLAG_KEY;
    bytes += packet.size;
    dumpPacket(
        mFormatContext->streams[packet.stream_index],
        &packet,
        "Captured Packet");
    if ( packet.dts == AV_NOPTS_VALUE ) {
      packet.dts = packet.pts;
    }

    // Video recording
    if ( recording.tv_sec ) {
      uint32_t last_event_id = monitor->GetLastEventId();
      uint32_t video_writer_event_id = monitor->GetVideoWriterEventId();

      if ( last_event_id != video_writer_event_id ) {
        Debug(2, "Have change of event.  last_event(%d), our current (%d)",
            last_event_id, video_writer_event_id);

        if ( videoStore ) {
          Info("Re-starting video storage module");

          // I don't know if this is important or not... but I figure we might
          // as well write this last packet out to the store before closing it.
          // Also don't know how much it matters for audio.
          if ( packet.stream_index == mVideoStreamId ) {
            // Write the packet to our video store
            int ret = videoStore->writeVideoFramePacket(&packet);
            if ( ret < 0 ) {  // Less than zero and we skipped a frame
              Warning("Error writing last packet to videostore.");
            }
          }  // end if video

          delete videoStore;
          videoStore = NULL;
          have_video_keyframe = false;

          monitor->SetVideoWriterEventId(0);
        }  // end if videoStore
      }  // end if end of recording

      if ( last_event_id && !videoStore ) {
        // Instantiate the video storage module

        packetqueue->dumpQueue();
        if ( record_audio ) {
          if ( mAudioStreamId == -1 ) {
            Debug(3, "Record Audio on but no audio stream found");
            videoStore = new VideoStore((const char *) event_file, "mp4",
                mFormatContext->streams[mVideoStreamId],
                NULL,
                this->getMonitor());

          } else {
            Debug(3, "Video module initiated with audio stream");
            videoStore = new VideoStore((const char *) event_file, "mp4",
                mFormatContext->streams[mVideoStreamId],
                mFormatContext->streams[mAudioStreamId],
                this->getMonitor());
          }
        } else {
          if ( mAudioStreamId >= 0 ) {
            Debug(3, "Record_audio is false so exclude audio stream");
          }
          videoStore = new VideoStore((const char *) event_file, "mp4",
              mFormatContext->streams[mVideoStreamId],
              NULL,
              this->getMonitor());
        }  // end if record_audio

        if ( !videoStore->open() ) {
          delete videoStore;
          videoStore = NULL;

        } else {
          monitor->SetVideoWriterEventId(last_event_id);

          // Need to write out all the frames from the last keyframe?
          // No... need to write out all frames from when the event began.
          // Due to PreEventFrames, this could be more than
          // since the last keyframe.
          unsigned int packet_count = 0;
          ZMPacket *queued_packet;
          struct timeval video_offset = {0};

          // Clear all packets that predate the moment when the recording began
          packetqueue->clear_unwanted_packets(
              &recording, 0, mVideoStreamId);

          while ( (queued_packet = packetqueue->popPacket()) ) {
            AVPacket *avp = queued_packet->av_packet();

            // compute time offset between event start and first frame in video
            if (packet_count == 0){
                monitor->SetVideoWriterStartTime(queued_packet->timestamp);
                timersub(&queued_packet->timestamp, &recording, &video_offset);
                Info("Event video offset is %.3f sec (<0 means video starts early)",
                     video_offset.tv_sec + video_offset.tv_usec*1e-6);
            }

            packet_count += 1;
            // Write the packet to our video store
            Debug(2, "Writing queued packet stream: %d  KEY %d, remaining (%d)",
                avp->stream_index,
                avp->flags & AV_PKT_FLAG_KEY,
                packetqueue->size());
            if ( avp->stream_index == mVideoStreamId ) {
              ret = videoStore->writeVideoFramePacket(avp);
              have_video_keyframe = true;
            } else if ( avp->stream_index == mAudioStreamId ) {
              ret = videoStore->writeAudioFramePacket(avp);
            } else {
              Warning("Unknown stream id in queued packet (%d)",
                  avp->stream_index);
              ret = -1;
            }
            if ( ret < 0 ) {
              // Less than zero and we skipped a frame
            }
            delete queued_packet;
          }  // end while packets in the packetqueue
          Debug(2, "Wrote %d queued packets", packet_count);
        }
      }  // end if ! was recording

    } else {
      // Not recording

      if ( videoStore ) {
        Debug(1, "Deleting videoStore instance");
        delete videoStore;
        videoStore = NULL;
        have_video_keyframe = false;
        monitor->SetVideoWriterEventId(0);
      }
    }  // end if recording or not

    // Buffer video packets, since we are not recording.
    // All audio packets are keyframes, so only if it's a video keyframe
    if ( packet.stream_index == mVideoStreamId ) {
      if ( keyframe ) {
        Debug(3, "Clearing queue");
        if (video_buffer_duration.tv_sec > 0 || video_buffer_duration.tv_usec > 0) {
            packetqueue->clearQueue(&video_buffer_duration, mVideoStreamId);
        }
        else {
            packetqueue->clearQueue(monitor->GetPreEventCount(), mVideoStreamId);
        }

        if (
            packetqueue->packet_count(mVideoStreamId)
            >=
            monitor->GetImageBufferCount()
            ) {
          Warning(
              "ImageBufferCount %d is too small. "
              "Needs to be at least %d. "
              "Either increase it or decrease time between keyframes",
              monitor->GetImageBufferCount(),
              packetqueue->packet_count(mVideoStreamId)+1);
        }

        packetqueue->queuePacket(&packet);
      } else if ( packetqueue->size() ) {
        // it's a keyframe or we already have something in the queue
        packetqueue->queuePacket(&packet);
      }
    } else if ( packet.stream_index == mAudioStreamId ) {
      // Ensure that the queue always begins with a video keyframe
      if ( record_audio && packetqueue->size() ) {
        packetqueue->queuePacket(&packet);
      }
    }  // end if packet type

    if ( packet.stream_index == mVideoStreamId ) {
      if ( (have_video_keyframe || keyframe) && videoStore ) {
        int ret = videoStore->writeVideoFramePacket(&packet);
        if ( ret < 0 ) {
          // Less than zero and we skipped a frame
          Error("Unable to write video packet code: %d, framecount %d: %s",
              ret, frameCount, av_make_error_string(ret).c_str());
        } else {
          have_video_keyframe = true;
        }
      }  // end if keyframe or have_video_keyframe

      ret = zm_send_packet_receive_frame(mVideoCodecContext, mRawFrame, packet);
      if ( ret < 0 ) {
        if ( AVERROR(EAGAIN) != ret ) {
          Warning("Unable to receive frame %d: code %d %s. error count is %d",
              frameCount, ret, av_make_error_string(ret).c_str(), error_count);
          error_count += 1;
          if ( error_count > 100 ) {
            Error("Error count over 100, going to close and re-open stream");
            return -1;
          }
        }
        zm_av_packet_unref(&packet);
        continue;
      }
      if ( error_count > 0 ) error_count--;
      zm_dump_video_frame(mRawFrame, "raw frame from decoder");
#if HAVE_LIBAVUTIL_HWCONTEXT_H
#if LIBAVCODEC_VERSION_CHECK(57, 89, 0, 89, 0)
      if (
          (hw_pix_fmt != AV_PIX_FMT_NONE)
          &&
          (mRawFrame->format == hw_pix_fmt)
          ) {
        /* retrieve data from GPU to CPU */
        ret = av_hwframe_transfer_data(hwFrame, mRawFrame, 0);
        if ( ret < 0 ) {
          Error("Unable to transfer frame at frame %d: %s, continuing",
              frameCount, av_make_error_string(ret).c_str());
          zm_av_packet_unref(&packet);
          continue;
        }
        zm_dump_video_frame(hwFrame, "After hwtransfer");

        hwFrame->pts = mRawFrame->pts;
        input_frame = hwFrame;
      } else {
#endif
#endif
        input_frame = mRawFrame;
#if HAVE_LIBAVUTIL_HWCONTEXT_H
#if LIBAVCODEC_VERSION_CHECK(57, 89, 0, 89, 0)
      }
#endif
#endif
      if ( transfer_to_image(image, mFrame, input_frame) < 0 ) {
        Error("Failed to transfer from frame to image");
        zm_av_packet_unref(&packet);
        return -1;
      }

      frameComplete = 1;
      frameCount++;
    } else if ( packet.stream_index == mAudioStreamId ) {
      // FIXME best way to copy all other streams
      frameComplete = 1;
      if ( videoStore ) {
        if ( record_audio ) {
          if ( have_video_keyframe ) {
            // Write the packet to our video store
            // FIXME no relevance of last key frame
            int ret = videoStore->writeAudioFramePacket(&packet);
            if ( ret < 0 ) {
              // Less than zero and we skipped a frame
              Warning("Failure to write audio packet.");
              zm_av_packet_unref(&packet);
              return 0;
            }
          } else {
            Debug(3, "Not recording audio because no video keyframe");
          }
        } else {
          Debug(4, "Not doing recording of audio packet");
        }
      } else {
        Debug(4, "Have audio packet, but not recording atm");
      }
      zm_av_packet_unref(&packet);
      return 0;
    } else {
#if LIBAVUTIL_VERSION_CHECK(56, 23, 0, 23, 0)
      Debug(3, "Some other stream index %d, %s",
          packet.stream_index,
          av_get_media_type_string(
            mFormatContext->streams[packet.stream_index]->codecpar->codec_type)
          );
#else
      Debug(3, "Some other stream index %d", packet.stream_index);
#endif
    }  // end if is video or audio or something else

    // the packet contents are ref counted... when queuing, we allocate another
    // packet and reference it with that one, so we should always need to unref
    // here, which should not affect the queued version.
    zm_av_packet_unref(&packet);
  }  // end while ! frameComplete
  return frameCount;
}  // end FfmpegCamera::CaptureAndRecord

int FfmpegCamera::transfer_to_image(
    Image &image,
    AVFrame *output_frame,
    AVFrame *input_frame
    ) {
  uint8_t* directbuffer;

  /* Request a writeable buffer of the target image */
  directbuffer = image.WriteBuffer(width, height, colours, subpixelorder);
  if ( directbuffer == NULL ) {
    Error("Failed requesting writeable buffer for the captured image.");
    return -1;
  }
#if LIBAVUTIL_VERSION_CHECK(54, 6, 0, 6, 0)
  // From what I've read, we should align the linesizes to 32bit so that ffmpeg can use SIMD instructions too.
  int size = av_image_fill_arrays(
      output_frame->data, output_frame->linesize,
      directbuffer, imagePixFormat, width, height, 
      (AV_PIX_FMT_RGBA == imagePixFormat ? 32 : 1)
      );
  if ( size < 0 ) {
    Error("Problem setting up data pointers into image %s",
        av_make_error_string(size).c_str());
  }
#else
  avpicture_fill((AVPicture *)output_frame, directbuffer,
      imagePixFormat, width, height);
#endif
#if HAVE_LIBSWSCALE
  if ( !mConvertContext ) {
    mConvertContext = sws_getContext(
        input_frame->width,
        input_frame->height,
        (AVPixelFormat)input_frame->format,
        width, height,
        imagePixFormat, SWS_BICUBIC, NULL,
        NULL, NULL);
    if ( mConvertContext == NULL ) {
      Error("Unable to create conversion context for %s from %s to %s",
          mPath.c_str(),
          av_get_pix_fmt_name((AVPixelFormat)input_frame->format),
          av_get_pix_fmt_name(imagePixFormat)
          );
      return -1;
    }
    Debug(1, "Setup conversion context for %dx%d %s to %dx%d %s",
          input_frame->width, input_frame->height,
          av_get_pix_fmt_name((AVPixelFormat)input_frame->format),
          width, height,
          av_get_pix_fmt_name(imagePixFormat)
        );
  }

  int ret =
      sws_scale(
        mConvertContext, input_frame->data, input_frame->linesize,
        0, mVideoCodecContext->height,
        output_frame->data, output_frame->linesize);
  if ( ret < 0 ) {
    Error("Unable to convert format %u %s linesize %d,%d height %d to format %u %s linesize %d,%d at frame %d codec %u %s lines %d: code: %d",
        input_frame->format, av_get_pix_fmt_name((AVPixelFormat)input_frame->format),
        input_frame->linesize[0], input_frame->linesize[1], mVideoCodecContext->height,
        imagePixFormat,
        av_get_pix_fmt_name(imagePixFormat),
        output_frame->linesize[0], output_frame->linesize[1],
        frameCount,
        mVideoCodecContext->pix_fmt, av_get_pix_fmt_name(mVideoCodecContext->pix_fmt),
        mVideoCodecContext->height,
        ret
        );
    return -1;
  }
    Debug(4, "Able to convert format %u %s linesize %d,%d height %d to format %u %s linesize %d,%d at frame %d codec %u %s %dx%d ",
        input_frame->format, av_get_pix_fmt_name((AVPixelFormat)input_frame->format),
        input_frame->linesize[0], input_frame->linesize[1], mVideoCodecContext->height,
        imagePixFormat,
        av_get_pix_fmt_name(imagePixFormat),
        output_frame->linesize[0], output_frame->linesize[1],
        frameCount,
        mVideoCodecContext->pix_fmt, av_get_pix_fmt_name(mVideoCodecContext->pix_fmt),
        output_frame->width,
        output_frame->height
        );
#else  // HAVE_LIBSWSCALE
  Fatal("You must compile ffmpeg with the --enable-swscale "
      "option to use ffmpeg cameras");
#endif  // HAVE_LIBSWSCALE
  return 0;
}  // end int FfmpegCamera::transfer_to_image

int FfmpegCamera::FfmpegInterruptCallback(void *ctx) {
  // FfmpegCamera* camera = reinterpret_cast<FfmpegCamera*>(ctx);
  // Debug(4, "FfmpegInterruptCallback");
  return zm_terminate;
}

#endif  // HAVE_LIBAVFORMAT
