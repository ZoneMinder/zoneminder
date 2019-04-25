//
// ZoneMinder Ffmpeg Camera Class Implementation, $Date: 2009-01-16 12:18:50 +0000 (Fri, 16 Jan 2009) $, $Revision: 2713 $
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

#if HAVE_LIBAVFORMAT

#include "zm_ffmpeg_camera.h"

extern "C" {
#include "libavutil/time.h"
#if HAVE_AVUTIL_HWCONTEXT_H
	#include "libavutil/hwcontext.h"
	#include "libavutil/hwcontext_qsv.h"
#endif
}
#ifndef AV_ERROR_MAX_STRING_SIZE
#define AV_ERROR_MAX_STRING_SIZE 64
#endif

#ifdef SOLARIS
#include <sys/errno.h>  // for ESRCH
#include <signal.h>
#include <pthread.h>
#endif


#if HAVE_AVUTIL_HWCONTEXT_H
static AVPixelFormat get_format(AVCodecContext *avctx, const enum AVPixelFormat *pix_fmts) {
  while (*pix_fmts != AV_PIX_FMT_NONE) {
    if (*pix_fmts == AV_PIX_FMT_QSV) {
      DecodeContext *decode = (DecodeContext *)avctx->opaque;
      AVHWFramesContext  *frames_ctx;
      AVQSVFramesContext *frames_hwctx;
      int ret;

      /* create a pool of surfaces to be used by the decoder */
      avctx->hw_frames_ctx = av_hwframe_ctx_alloc(decode->hw_device_ref);
      if (!avctx->hw_frames_ctx)
        return AV_PIX_FMT_NONE;
      frames_ctx   = (AVHWFramesContext*)avctx->hw_frames_ctx->data;
      frames_hwctx = (AVQSVFramesContext*)frames_ctx->hwctx;

      frames_ctx->format            = AV_PIX_FMT_QSV;
      frames_ctx->sw_format         = avctx->sw_pix_fmt;
      frames_ctx->width             = FFALIGN(avctx->coded_width,  32);
      frames_ctx->height            = FFALIGN(avctx->coded_height, 32);
      frames_ctx->initial_pool_size = 32;

      frames_hwctx->frame_type = MFX_MEMTYPE_VIDEO_MEMORY_DECODER_TARGET;

      ret = av_hwframe_ctx_init(avctx->hw_frames_ctx);
      if (ret < 0)
        return AV_PIX_FMT_NONE;

      return AV_PIX_FMT_QSV;
    }

    pix_fmts++;
  }

  Error( "The QSV pixel format not offered in get_format()");

  return AV_PIX_FMT_NONE;
}
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
    bool p_record_audio
    ) :
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
  mPath( p_path ),
  mMethod( p_method ),
  mOptions( p_options )
{
  if ( capture ) {
    FFMPEGInit();
  }

  hwaccel = false;
#if HAVE_AVUTIL_HWCONTEXT_H
  decode = { NULL };
  hwFrame = NULL;
#endif

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
  error_count = 0;

} // end FFmpegCamera::FFmpegCamera

FfmpegCamera::~FfmpegCamera() {

  Close();

  FFMPEGDeInit();
}

int FfmpegCamera::PrimeCapture() {
  if ( mCanCapture ) {
    Info("Priming capture from %s, Closing", mPath.c_str());
    Close();
  }
  mVideoStreamId = -1;
  mAudioStreamId = -1;
  Info("Priming capture from %s", mPath.c_str());

  return ! OpenFfmpeg();
}

int FfmpegCamera::PreCapture() {
  return 0;
}

int FfmpegCamera::Capture(ZMPacket &zm_packet) {
  if ( ! mCanCapture ) {
    return -1;
  }

  int ret;

  // If the reopen thread has a value, but mCanCapture != 0, then we have just reopened the connection to the ffmpeg device, and we can clean up the thread.

  if ( (ret = av_read_frame(mFormatContext, &packet)) < 0 ) {
    if (
        // Check if EOF.
        (ret == AVERROR_EOF || (mFormatContext->pb && mFormatContext->pb->eof_reached)) ||
        // Check for Connection failure.
        (ret == -110)
       ) {
        Info("Unable to read packet from stream %d: error %d \"%s\".", packet.stream_index, ret,
            av_make_error_string(ret).c_str()
            );
      } else {
        Error("Unable to read packet from stream %d: error %d \"%s\".", packet.stream_index, ret,
            av_make_error_string(ret).c_str()
            );
      }
      return -1;
  }
  dumpPacket(mFormatContext->streams[packet.stream_index], &packet, "ffmpeg_camera in");
  if ( 0 && ( packet.dts < 0 ) ) {
    zm_av_packet_unref(&packet);
    return 0;
  }

  bytes += packet.size;
  zm_packet.set_packet(&packet);
  zm_av_packet_unref(&packet);
  return 1;
} // FfmpegCamera::Capture

int FfmpegCamera::PostCapture() {
  // Nothing to do here
  return 0;
}

int FfmpegCamera::OpenFfmpeg() {

  int ret;

  error_count = 0;

  // Open the input, not necessarily a file
#if !LIBAVFORMAT_VERSION_CHECK(53, 2, 0, 4, 0)
  Debug(1, "Calling av_open_input_file");
  if ( av_open_input_file(&mFormatContext, mPath.c_str(), NULL, 0, NULL) != 0 )
#else
  // Handle options
  AVDictionary *opts = NULL;
  ret = av_dict_parse_string(&opts, Options().c_str(), "=", ",", 0);
  if ( ret < 0 ) {
    Warning("Could not parse ffmpeg input options list '%s'", Options().c_str());
  } else {
    Debug(2,"Could not parse ffmpeg input options list '%s'", Options().c_str());
  }

  // Set transport method as specified by method field, rtpUni is default
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
//#av_dict_set(&opts, "timeout", "10000000", 0); // in microseconds.

  if ( ret < 0 ) {
    Warning("Could not set rtsp_transport method '%s'", method.c_str());
  }

  Debug(1, "Calling avformat_open_input for %s", mPath.c_str());

  mFormatContext = avformat_alloc_context( );
  // Speed up find_stream_info
  //FIXME can speed up initial analysis but need sensible parameters...
  //mFormatContext->probesize = 32;
  //mFormatContext->max_analyze_duration = 32;
  mFormatContext->interrupt_callback.callback = FfmpegInterruptCallback;
  mFormatContext->interrupt_callback.opaque = this;

  if ( avformat_open_input(&mFormatContext, mPath.c_str(), NULL, &opts) != 0 )
#endif
  {
    Error("Unable to open input %s due to: %s", mPath.c_str(), strerror(errno));
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

  monitor->GetLastEventId() ;

  Debug(1, "Opened input");

  Info("Stream open %s, parsing streams...", mPath.c_str());

#if !LIBAVFORMAT_VERSION_CHECK(53, 6, 0, 6, 0)
  if ( av_find_stream_info(mFormatContext) < 0 )
#else
  if ( avformat_find_stream_info(mFormatContext, 0) < 0 )
#endif
  {
    Error("Unable to find stream info from %s due to: %s", mPath.c_str(), strerror(errno));
    return -1;
  }

  Debug(4, "Got stream info");

  // Find first video stream present
  // The one we want Might not be the first
  mVideoStreamId = -1;
  mAudioStreamId = -1;
  for ( unsigned int i=0; i < mFormatContext->nb_streams; i++ ) {
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
    if ( mFormatContext->streams[i]->codecpar->codec_type == AVMEDIA_TYPE_VIDEO ) {
#else
#if (LIBAVCODEC_VERSION_CHECK(52, 64, 0, 64, 0) || LIBAVUTIL_VERSION_CHECK(50, 14, 0, 14, 0))
    if ( mFormatContext->streams[i]->codec->codec_type == AVMEDIA_TYPE_VIDEO ) {
#else
    if ( mFormatContext->streams[i]->codec->codec_type == CODEC_TYPE_VIDEO ) {
#endif
#endif
      if ( mVideoStreamId == -1 ) {
        mVideoStreamId = i;
        // if we break, then we won't find the audio stream
        continue;
      } else {
        Debug(2, "Have another video stream.");
      }
    }
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
    if ( mFormatContext->streams[i]->codecpar->codec_type == AVMEDIA_TYPE_AUDIO ) {
#else
#if (LIBAVCODEC_VERSION_CHECK(52, 64, 0, 64, 0) || LIBAVUTIL_VERSION_CHECK(50, 14, 0, 14, 0))
    if ( mFormatContext->streams[i]->codec->codec_type == AVMEDIA_TYPE_AUDIO ) {
#else
    if ( mFormatContext->streams[i]->codec->codec_type == CODEC_TYPE_AUDIO ) {
#endif
#endif
      if ( mAudioStreamId == -1 ) {
        mAudioStreamId = i;
      } else {
        Debug(2, "Have another audio stream.");
      }
    }
  } // end foreach stream
  if ( mVideoStreamId == -1 )
    Fatal("Unable to locate video stream in %s", mPath.c_str());
  if ( mAudioStreamId == -1 )
    Debug(3, "Unable to locate audio stream in %s", mPath.c_str());

  Debug(3, "Found video stream at index %d", mVideoStreamId);
  Debug(3, "Found audio stream at index %d", mAudioStreamId);

#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
  //mVideoCodecContext = avcodec_alloc_context3(NULL);
  //avcodec_parameters_to_context( mVideoCodecContext, mFormatContext->streams[mVideoStreamId]->codecpar );
  // this isn't copied.
  //mVideoCodecContext->time_base = mFormatContext->streams[mVideoStreamId]->codec->time_base;
#else
#endif
  mVideoCodecContext = mFormatContext->streams[mVideoStreamId]->codec;
	// STolen from ispy
	//this fixes issues with rtsp streams!! woot.
	//mVideoCodecContext->flags2 |= CODEC_FLAG2_FAST | CODEC_FLAG2_CHUNKS | CODEC_FLAG_LOW_DELAY;  // Enable faster H264 decode.
#ifdef CODEC_FLAG2_FAST
	mVideoCodecContext->flags2 |= CODEC_FLAG2_FAST | CODEC_FLAG_LOW_DELAY;
#endif

#if HAVE_AVUTIL_HWCONTEXT_H
  if ( mVideoCodecContext->codec_id == AV_CODEC_ID_H264 ) {

    //vaapi_decoder = new VAAPIDecoder();
    //mVideoCodecContext->opaque = vaapi_decoder;
    //mVideoCodec = vaapi_decoder->openCodec( mVideoCodecContext );

    if ( ! mVideoCodec ) {
      // Try to open an hwaccel codec.
      if ( (mVideoCodec = avcodec_find_decoder_by_name("h264_vaapi")) == NULL ) {
        Debug(1, "Failed to find decoder (h264_vaapi)" );
      } else {
        Debug(1, "Success finding decoder (h264_vaapi)" );
      }
    }
    if ( ! mVideoCodec ) {
      // Try to open an hwaccel codec.
      if ( (mVideoCodec = avcodec_find_decoder_by_name("h264_qsv")) == NULL ) {
        Debug(1, "Failed to find decoder (h264_qsv)" );
      } else {
        Debug(1, "Success finding decoder (h264_qsv)" );
        /* open the hardware device */
        ret = av_hwdevice_ctx_create(&decode.hw_device_ref, AV_HWDEVICE_TYPE_QSV,
            "auto", NULL, 0);
        if (ret < 0) {
          Error("Failed to open the hardware device");
          mVideoCodec = NULL;
        } else {
          mVideoCodecContext->opaque      = &decode;
          mVideoCodecContext->get_format  = get_format;
          hwaccel = true;
          hwFrame = zm_av_frame_alloc();
        }
      }
    }
  } // end if h264
#endif
  if ( mVideoCodecContext->codec_id == AV_CODEC_ID_H264 ) {
    if ( (mVideoCodec = avcodec_find_decoder_by_name("h264_mmal")) == NULL ) { 
      Debug(1, "Failed to find decoder (h264_mmal)");
    } else {
      Debug(1, "Success finding decoder (h264_mmal)");
    }
  }

  if ( (!mVideoCodec) and ( (mVideoCodec = avcodec_find_decoder(mVideoCodecContext->codec_id)) == NULL ) ) {
  // Try and get the codec from the codec context
    Error("Can't find codec for video stream from %s", mPath.c_str());
    return -1;
  } else {
    Debug(1, "Video Found decoder %s", mVideoCodec->name);
    zm_dump_stream_format(mFormatContext, mVideoStreamId, 0, 0);

    // Open the codec
#if !LIBAVFORMAT_VERSION_CHECK(53, 8, 0, 8, 0)
    ret = avcodec_open(mVideoCodecContext, mVideoCodec);
#else
    ret = avcodec_open2(mVideoCodecContext, mVideoCodec, &opts);
#endif
    AVDictionaryEntry *e = NULL;
    while ( (e = av_dict_get(opts, "", e, AV_DICT_IGNORE_SUFFIX)) != NULL ) {
      Warning( "Option %s not recognized by ffmpeg", e->key);
    }
    if ( ret < 0 ) {
      Error("Unable to open codec for video stream from %s", mPath.c_str());
      av_dict_free(&opts);
      return -1;
    }
    zm_dump_codec(mVideoCodecContext);
  }

  if ( mVideoCodecContext->hwaccel != NULL ) {
    Debug(1, "HWACCEL in use");
  } else {
    Debug(1, "HWACCEL not in use");
  }
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
      avcodec_parameters_to_context( mAudioCodecContext, mFormatContext->streams[mAudioStreamId]->codecpar );
#else
      mAudioCodecContext = mFormatContext->streams[mAudioStreamId]->codec;
     // = avcodec_alloc_context3(mAudioCodec);
#endif

      Debug(1, "Audio Found decoder");
      zm_dump_stream_format(mFormatContext, mAudioStreamId, 0, 0);
      // Open the codec
#if !LIBAVFORMAT_VERSION_CHECK(53, 8, 0, 8, 0)
      Debug(1, "Calling avcodec_open");
      if ( avcodec_open(mAudioCodecContext, mAudioCodec) < 0 )
#else
        Debug(1, "Calling avcodec_open2");
      if ( avcodec_open2(mAudioCodecContext, mAudioCodec, 0) < 0 )
#endif
        Fatal("Unable to open codec for video stream from %s", mPath.c_str());
    }
    Debug(1, "Opened audio codec");
  } // end if have audio stream

  if ( (unsigned int)mVideoCodecContext->width != width || (unsigned int)mVideoCodecContext->height != height ) {
    Warning("Monitor dimensions are %dx%d but camera is sending %dx%d", width, height, mVideoCodecContext->width, mVideoCodecContext->height);
  }

  mCanCapture = true;

  return 1;
} // int FfmpegCamera::OpenFfmpeg()

int FfmpegCamera::Close() {

  Debug(2, "CloseFfmpeg called.");

  mCanCapture = false;

  if ( mFrame ) {
    av_frame_free( &mFrame );
    mFrame = NULL;
  }
  if ( mRawFrame ) {
    av_frame_free( &mRawFrame );
    mRawFrame = NULL;
  }

  if ( mVideoCodecContext ) {
    avcodec_close(mVideoCodecContext);
    Debug(1,"After codec close");
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
    //avcodec_free_context(&mVideoCodecContext);
#endif
    mVideoCodecContext = NULL; // Freed by av_close_input_file
  }
  if ( mAudioCodecContext ) {
    avcodec_close(mAudioCodecContext);
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
    avcodec_free_context(&mAudioCodecContext);
#endif
    mAudioCodecContext = NULL; // Freed by av_close_input_file
  }

  if ( mFormatContext ) {
#if !LIBAVFORMAT_VERSION_CHECK(53, 17, 0, 25, 0)
    av_close_input_file(mFormatContext);
#else
    avformat_close_input(&mFormatContext);
#endif
    mFormatContext = NULL;
  }

  return 0;
} // end FfmpegCamera::Close

int FfmpegCamera::FfmpegInterruptCallback(void *ctx) {
  //FfmpegCamera* camera = reinterpret_cast<FfmpegCamera*>(ctx);
  //Debug(4, "FfmpegInterruptCallback");
  return zm_terminate;
}

#endif // HAVE_LIBAVFORMAT
