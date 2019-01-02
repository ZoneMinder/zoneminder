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

FfmpegCamera::FfmpegCamera( int p_id, const std::string &p_path, const std::string &p_method, const std::string &p_options, int p_width, int p_height, int p_colours, int p_brightness, int p_contrast, int p_hue, int p_colour, bool p_capture, bool p_record_audio ) :
  Camera( p_id, FFMPEG_SRC, p_width, p_height, p_colours, ZM_SUBPIX_ORDER_DEFAULT_FOR_COLOUR(p_colours), p_brightness, p_contrast, p_hue, p_colour, p_capture, p_record_audio ),
  mPath( p_path ),
  mMethod( p_method ),
  mOptions( p_options )
{
  if ( capture ) {
    Initialise();
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
  startTime = 0;
  mCanCapture = false;
  videoStore = NULL;
  video_last_pts = 0;
  have_video_keyframe = false;

#if HAVE_LIBSWSCALE  
  mConvertContext = NULL;
#endif
  /* Has to be located inside the constructor so other components such as zma will receive correct colours and subpixel order */
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
    Panic("Unexpected colours: %d",colours);
  }
}

FfmpegCamera::~FfmpegCamera() {

  if ( videoStore ) {
    delete videoStore;
    videoStore = NULL;
  }
  Close();

  if ( capture ) {
    Terminate();
  }
  avformat_network_deinit();
}

void FfmpegCamera::Initialise() {
  FFMPEGInit();
}

void FfmpegCamera::Terminate() {
}

int FfmpegCamera::PrimeCapture() {
  if ( mCanCapture ) {
    Info( "Priming capture from %s, CLosing", mPath.c_str() );
    Close();
  }
  mVideoStreamId = -1;
  mAudioStreamId = -1;
  Info( "Priming capture from %s", mPath.c_str() );

  return OpenFfmpeg();
}

int FfmpegCamera::PreCapture() {
  // If Reopen was called, then ffmpeg is closed and we need to reopen it.
  if ( ! mCanCapture )
    return OpenFfmpeg();
  // Nothing to do here
  return( 0 );
}

int FfmpegCamera::Capture( Image &image ) {
  if ( ! mCanCapture ) {
    return -1;
  }

  // If the reopen thread has a value, but mCanCapture != 0, then we have just reopened the connection to the ffmpeg device, and we can clean up the thread.

  int frameComplete = false;
  while ( !frameComplete && !zm_terminate) {
    int avResult = av_read_frame(mFormatContext, &packet);
    char errbuf[AV_ERROR_MAX_STRING_SIZE];
    if ( avResult < 0 ) {
      av_strerror(avResult, errbuf, AV_ERROR_MAX_STRING_SIZE);
      if (
          // Check if EOF.
          (avResult == AVERROR_EOF || (mFormatContext->pb && mFormatContext->pb->eof_reached)) ||
          // Check for Connection failure.
          (avResult == -110)
         ) {
        Info("Unable to read packet from stream %d: error %d \"%s\".", packet.stream_index, avResult, errbuf);
      } else {
        Error("Unable to read packet from stream %d: error %d \"%s\".", packet.stream_index, avResult, errbuf);
      }
      return -1;
    }

    int keyframe = packet.flags & AV_PKT_FLAG_KEY;
    if ( keyframe )
      have_video_keyframe = true;

    Debug( 5, "Got packet from stream %d dts (%d) pts(%d)", packet.stream_index, packet.pts, packet.dts );
    // What about audio stream? Maybe someday we could do sound detection...
    if ( ( packet.stream_index == mVideoStreamId ) && ( keyframe || have_video_keyframe ) ) {
    int ret;
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
      ret = avcodec_send_packet( mVideoCodecContext, &packet );
      if ( ret < 0 ) {
        av_strerror( ret, errbuf, AV_ERROR_MAX_STRING_SIZE );
        Error( "Unable to send packet at frame %d: %s, continuing", frameCount, errbuf );
        zm_av_packet_unref( &packet );
        continue;
      }

#if HAVE_AVUTIL_HWCONTEXT_H
      if ( hwaccel ) {
        ret = avcodec_receive_frame( mVideoCodecContext, hwFrame );
        if ( ret < 0 ) {
          av_strerror( ret, errbuf, AV_ERROR_MAX_STRING_SIZE );
          Error( "Unable to send packet at frame %d: %s, continuing", frameCount, errbuf );
          zm_av_packet_unref( &packet );
          continue;
        }
        ret = av_hwframe_transfer_data(mRawFrame, hwFrame, 0);
        if (ret < 0) {
          av_strerror( ret, errbuf, AV_ERROR_MAX_STRING_SIZE );
          Error( "Unable to transfer frame at frame %d: %s, continuing", frameCount, errbuf );
          zm_av_packet_unref( &packet );
          continue;
        }
      } else {
#endif
        ret = avcodec_receive_frame( mVideoCodecContext, mRawFrame );
        if ( ret < 0 ) {
          av_strerror( ret, errbuf, AV_ERROR_MAX_STRING_SIZE );
          Error( "Unable to send packet at frame %d: %s, continuing", frameCount, errbuf );
          zm_av_packet_unref( &packet );
          continue;
        }

#if HAVE_AVUTIL_HWCONTEXT_H
      }
#endif

      frameComplete = 1;
# else
      ret = zm_avcodec_decode_video( mVideoCodecContext, mRawFrame, &frameComplete, &packet );
      if ( ret < 0 ) {
        av_strerror( ret, errbuf, AV_ERROR_MAX_STRING_SIZE );
        Error( "Unable to decode frame at frame %d: %s, continuing", frameCount, errbuf );
        zm_av_packet_unref( &packet );
        continue;
      }
#endif

      Debug( 4, "Decoded video packet at frame %d", frameCount );

      if ( frameComplete ) {
        Debug( 4, "Got frame %d", frameCount );

        uint8_t* directbuffer;

        /* Request a writeable buffer of the target image */
        directbuffer = image.WriteBuffer(width, height, colours, subpixelorder);
        if ( directbuffer == NULL ) {
          Error("Failed requesting writeable buffer for the captured image.");
          return -1;
        }

#if LIBAVUTIL_VERSION_CHECK(54, 6, 0, 6, 0)
        av_image_fill_arrays(mFrame->data, mFrame->linesize,
            directbuffer, imagePixFormat, width, height, 1);
#else
        avpicture_fill( (AVPicture *)mFrame, directbuffer,
            imagePixFormat, width, height);
#endif

#if HAVE_LIBSWSCALE
        if ( sws_scale(mConvertContext, mRawFrame->data, mRawFrame->linesize, 0, mVideoCodecContext->height, mFrame->data, mFrame->linesize) < 0 ) {
          Error("Unable to convert raw format %u to target format %u at frame %d", mVideoCodecContext->pix_fmt, imagePixFormat, frameCount);
          return -1;
        } 
#else // HAVE_LIBSWSCALE
        Fatal("You must compile ffmpeg with the --enable-swscale option to use ffmpeg cameras");
#endif // HAVE_LIBSWSCALE

        frameCount++;
      } // end if frameComplete
    } else {
      Debug( 4, "Different stream_index %d", packet.stream_index );
    } // end if packet.stream_index == mVideoStreamId
    bytes += packet.size;
    zm_av_packet_unref( &packet );
  } // end while ! frameComplete
  return frameComplete ? 1 : 0;
} // FfmpegCamera::Capture

int FfmpegCamera::PostCapture() {
  // Nothing to do here
  return 0;
}

int FfmpegCamera::OpenFfmpeg() {


  int ret;

  have_video_keyframe = false;

  // Open the input, not necessarily a file
#if !LIBAVFORMAT_VERSION_CHECK(53, 2, 0, 4, 0)
  Debug ( 1, "Calling av_open_input_file" );
  if ( av_open_input_file( &mFormatContext, mPath.c_str(), NULL, 0, NULL ) != 0 )
#else
  // Handle options
  AVDictionary *opts = 0;
  ret = av_dict_parse_string(&opts, Options().c_str(), "=", ",", 0);
  if ( ret < 0 ) {
    Warning("Could not parse ffmpeg input options list '%s'\n", Options().c_str());
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
    Warning("Unknown method (%s)", method.c_str() );
  }

  if ( ret < 0 ) {
    Warning("Could not set rtsp_transport method '%s'\n", method.c_str());
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
  AVDictionaryEntry *e=NULL;
  while ( (e = av_dict_get(opts, "", e, AV_DICT_IGNORE_SUFFIX)) != NULL ) {
    Warning( "Option %s not recognized by ffmpeg", e->key);
  }
  av_dict_free(&opts);

  Debug(1, "Opened input");

  Info( "Stream open %s, parsing streams...", mPath.c_str() );

#if !LIBAVFORMAT_VERSION_CHECK(53, 6, 0, 6, 0)
  Debug(4, "Calling av_find_stream_info");
  if ( av_find_stream_info( mFormatContext ) < 0 )
#else
  Debug(4, "Calling avformat_find_stream_info");
  if ( avformat_find_stream_info( mFormatContext, 0 ) < 0 )
#endif
  {
    Error("Unable to find stream info from %s due to: %s", mPath.c_str(), strerror(errno));
    return -1;
  }

  startTime = av_gettime();//FIXME here or after find_Stream_info
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
        Debug(2, "Have another video stream." );
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
        Debug(2, "Have another audio stream." );
      }
    }
  } // end foreach stream
  if ( mVideoStreamId == -1 )
    Fatal( "Unable to locate video stream in %s", mPath.c_str() );
  if ( mAudioStreamId == -1 )
    Debug( 3, "Unable to locate audio stream in %s", mPath.c_str() );

  Debug(3, "Found video stream at index %d", mVideoStreamId);
  Debug(3, "Found audio stream at index %d", mAudioStreamId);

#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
  mVideoCodecContext = avcodec_alloc_context3(NULL);
  avcodec_parameters_to_context( mVideoCodecContext, mFormatContext->streams[mVideoStreamId]->codecpar );
#else
  mVideoCodecContext = mFormatContext->streams[mVideoStreamId]->codec;
#endif
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
  } else {
#ifdef AV_CODEC_ID_H265
    if ( mVideoCodecContext->codec_id == AV_CODEC_ID_H265 ) {
      Debug( 1, "Input stream appears to be h265.  The stored event file may not be viewable in browser." );
    } else {
#endif
      Warning( "Input stream is not h264.  The stored event file may not be viewable in browser." );
#ifdef AV_CODEC_ID_H265
    }
#endif
  } // end if h264
#endif
  if ( mVideoCodecContext->codec_id == AV_CODEC_ID_H264 ) {
    if ( (mVideoCodec = avcodec_find_decoder_by_name("h264_mmal")) == NULL ) {
      Debug(1, "Failed to find decoder (h264_mmal)" );
    } else {
      Debug(1, "Success finding decoder (h264_mmal)" );
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
  Debug ( 1, "Calling avcodec_open" );
  if ( avcodec_open(mVideoCodecContext, mVideoCodec) < 0 ){
#else
    Debug ( 1, "Calling avcodec_open2" );
  if ( avcodec_open2(mVideoCodecContext, mVideoCodec, &opts) < 0 ) {
#endif
    AVDictionaryEntry *e = NULL;
    while ( (e = av_dict_get(opts, "", e, AV_DICT_IGNORE_SUFFIX)) != NULL ) {
      Warning( "Option %s not recognized by ffmpeg", e->key);
    }
    Error( "Unable to open codec for video stream from %s", mPath.c_str() );
    av_dict_free(&opts);
    return -1;
  } else {

    AVDictionaryEntry *e = NULL;
    if ( (e = av_dict_get(opts, "", e, AV_DICT_IGNORE_SUFFIX)) != NULL ) {
      Warning( "Option %s not recognized by ffmpeg", e->key);
    }
    av_dict_free(&opts);
  }
  }

  if (mVideoCodecContext->hwaccel != NULL) {
    Debug(1, "HWACCEL in use");
  } else {
    Debug(1, "HWACCEL not in use");
  }
  if ( mAudioStreamId >= 0 ) {
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
    mAudioCodecContext = avcodec_alloc_context3( NULL );
    avcodec_parameters_to_context( mAudioCodecContext, mFormatContext->streams[mAudioStreamId]->codecpar );
#else
    mAudioCodecContext = mFormatContext->streams[mAudioStreamId]->codec;
#endif
    if ( (mAudioCodec = avcodec_find_decoder(mAudioCodecContext->codec_id)) == NULL ) {
      Debug(1, "Can't find codec for audio stream from %s", mPath.c_str());
    } else {
      Debug(1, "Audio Found decoder");
      zm_dump_stream_format(mFormatContext, mAudioStreamId, 0, 0);
      // Open the codec
#if !LIBAVFORMAT_VERSION_CHECK(53, 8, 0, 8, 0)
      Debug ( 1, "Calling avcodec_open" );
      if ( avcodec_open(mAudioCodecContext, mAudioCodec) < 0 ) {
#else
      Debug ( 1, "Calling avcodec_open2" );
      if ( avcodec_open2(mAudioCodecContext, mAudioCodec, 0) < 0 ) {
#endif
        Error( "Unable to open codec for video stream from %s", mPath.c_str() );
        return -1;
      }
      Debug(2, "Opened audio codec");
    } // end if find decoder
  } // end if have audio_context

  // Allocate space for the native video frame
  mRawFrame = zm_av_frame_alloc();

  // Allocate space for the converted video frame
  mFrame = zm_av_frame_alloc();

  if ( mRawFrame == NULL || mFrame == NULL ) {
    Error("Unable to allocate frame for %s", mPath.c_str());
    return -1;
  }

  Debug( 3, "Allocated frames");

#if LIBAVUTIL_VERSION_CHECK(54, 6, 0, 6, 0)
  int pSize = av_image_get_buffer_size( imagePixFormat, width, height,1 );
#else
  int pSize = avpicture_get_size( imagePixFormat, width, height );
#endif

  if ( (unsigned int)pSize != imagesize ) {
    Error("Image size mismatch. Required: %d Available: %d",pSize,imagesize);
    return -1;
  }

  Debug(4, "Validated imagesize");

#if HAVE_LIBSWSCALE
  Debug(1, "Calling sws_isSupportedInput");
  if ( !sws_isSupportedInput(mVideoCodecContext->pix_fmt) ) {
    Error("swscale does not support the codec format: %c%c%c%c", (mVideoCodecContext->pix_fmt)&0xff, ((mVideoCodecContext->pix_fmt >> 8)&0xff), ((mVideoCodecContext->pix_fmt >> 16)&0xff), ((mVideoCodecContext->pix_fmt >> 24)&0xff));
    return -1;
  }

  if ( !sws_isSupportedOutput(imagePixFormat) ) {
    Error("swscale does not support the target format: %c%c%c%c",(imagePixFormat)&0xff,((imagePixFormat>>8)&0xff),((imagePixFormat>>16)&0xff),((imagePixFormat>>24)&0xff));
    return -1;
  }

  mConvertContext = sws_getContext(
      mVideoCodecContext->width,
      mVideoCodecContext->height,
      mVideoCodecContext->pix_fmt,
      width, height,
      imagePixFormat, SWS_BICUBIC, NULL,
      NULL, NULL);
  if ( mConvertContext == NULL ) {
    Error( "Unable to create conversion context for %s", mPath.c_str() );
    return -1;
  }
#else // HAVE_LIBSWSCALE
  Fatal( "You must compile ffmpeg with the --enable-swscale option to use ffmpeg cameras" );
#endif // HAVE_LIBSWSCALE

  if ( (unsigned int)mVideoCodecContext->width != width || (unsigned int)mVideoCodecContext->height != height ) {
    Warning( "Monitor dimensions are %dx%d but camera is sending %dx%d", width, height, mVideoCodecContext->width, mVideoCodecContext->height );
  }

  mCanCapture = true;

  return 0;
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

#if HAVE_LIBSWSCALE
  if ( mConvertContext ) {
    sws_freeContext( mConvertContext );
    mConvertContext = NULL;
  }
#endif

  if ( mVideoCodecContext ) {
    avcodec_close(mVideoCodecContext);
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
    avcodec_free_context(&mVideoCodecContext);
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
    av_close_input_file( mFormatContext );
#else
    avformat_close_input( &mFormatContext );
#endif
    mFormatContext = NULL;
  }

  if ( videoStore ) {
    delete videoStore;
    videoStore = NULL;
  }

  return 0;
} // end FfmpegCamera::Close

//Function to handle capture and store
int FfmpegCamera::CaptureAndRecord( Image &image, timeval recording, char* event_file ) {
  if ( !mCanCapture ) {
    return -1;
  }
  int ret;
  static char errbuf[AV_ERROR_MAX_STRING_SIZE];
  
  int frameComplete = false;
  while ( !frameComplete ) {
    av_init_packet(&packet);

    ret = av_read_frame(mFormatContext, &packet);
    if ( ret < 0 ) {
      av_strerror(ret, errbuf, AV_ERROR_MAX_STRING_SIZE);
      if (
          // Check if EOF.
          (ret == AVERROR_EOF || (mFormatContext->pb && mFormatContext->pb->eof_reached)) ||
          // Check for Connection failure.
          (ret == -110)
         ) {
        Info("Unable to read packet from stream %d: error %d \"%s\".", packet.stream_index, ret, errbuf);
      } else {
        Error("Unable to read packet from stream %d: error %d \"%s\".", packet.stream_index, ret, errbuf);
      }
      return -1;
    }

    int keyframe = packet.flags & AV_PKT_FLAG_KEY;
    bytes += packet.size;
    dumpPacket(&packet);

    //Video recording
    if ( recording.tv_sec ) {

      uint32_t last_event_id = monitor->GetLastEventId() ;
      uint32_t video_writer_event_id = monitor->GetVideoWriterEventId();

      if ( last_event_id != video_writer_event_id ) {
        Debug(2, "Have change of event.  last_event(%d), our current (%d)",
			last_event_id, video_writer_event_id);

        if ( videoStore ) {
          Info("Re-starting video storage module");

          // I don't know if this is important or not... but I figure we might as well write this last packet out to the store before closing it.
          // Also don't know how much it matters for audio.
          if ( packet.stream_index == mVideoStreamId ) {
            //Write the packet to our video store
            int ret = videoStore->writeVideoFramePacket(&packet);
            if ( ret < 0 ) { //Less than zero and we skipped a frame
              Warning("Error writing last packet to videostore.");
            }
          } // end if video

          delete videoStore;
          videoStore = NULL;
          have_video_keyframe = false;

          monitor->SetVideoWriterEventId(0);
        } // end if videoStore
      } // end if end of recording

      if ( last_event_id and !videoStore ) {
        //Instantiate the video storage module

        if ( record_audio ) {
          if ( mAudioStreamId == -1 ) {
            Debug(3, "Record Audio on but no audio stream found");
            videoStore = new VideoStore((const char *) event_file, "mp4",
                mFormatContext->streams[mVideoStreamId],
                NULL,
                startTime,
                this->getMonitor());

          } else {
            Debug(3, "Video module initiated with audio stream");
            videoStore = new VideoStore((const char *) event_file, "mp4",
                mFormatContext->streams[mVideoStreamId],
                mFormatContext->streams[mAudioStreamId],
                startTime,
                this->getMonitor());
          }
        } else {
          if ( mAudioStreamId >= 0 ) {
            Debug(3, "Record_audio is false so exclude audio stream");
          }
          videoStore = new VideoStore((const char *) event_file, "mp4",
              mFormatContext->streams[mVideoStreamId],
              NULL,
              startTime,
              this->getMonitor());
        } // end if record_audio

        if ( ! videoStore->open() ) {
          delete videoStore;
          videoStore = NULL;

        } else {
          monitor->SetVideoWriterEventId( last_event_id );

          // Need to write out all the frames from the last keyframe?
          // No... need to write out all frames from when the event began. Due to PreEventFrames, this could be more than since the last keyframe.
          unsigned int packet_count = 0;
          ZMPacket *queued_packet;

          // Clear all packets that predate the moment when the recording began
          packetqueue.clear_unwanted_packets( &recording, mVideoStreamId );

          while ( ( queued_packet = packetqueue.popPacket() ) ) {
            AVPacket *avp = queued_packet->av_packet();

            packet_count += 1;
            //Write the packet to our video store
            Debug(2, "Writing queued packet stream: %d  KEY %d, remaining (%d)", avp->stream_index, avp->flags & AV_PKT_FLAG_KEY, packetqueue.size() );
            if ( avp->stream_index == mVideoStreamId ) {
              ret = videoStore->writeVideoFramePacket( avp );
              have_video_keyframe = true;
            } else if ( avp->stream_index == mAudioStreamId ) {
              ret = videoStore->writeAudioFramePacket( avp );
            } else {
              Warning("Unknown stream id in queued packet (%d)", avp->stream_index );
              ret = -1;
            }
            if ( ret < 0 ) {
              //Less than zero and we skipped a frame
            }
            delete queued_packet;
          } // end while packets in the packetqueue
          Debug(2, "Wrote %d queued packets", packet_count );
        }
      } // end if ! was recording

    } else {
      // Not recording
      if ( videoStore ) {
        Info("Deleting videoStore instance");
        delete videoStore;
        videoStore = NULL;
        have_video_keyframe = false;
        monitor->SetVideoWriterEventId(0);
      }

      // Buffer video packets, since we are not recording.
      // All audio packets are keyframes, so only if it's a video keyframe
      if ( packet.stream_index == mVideoStreamId ) {
        if ( keyframe ) {
          Debug(3, "Clearing queue");
          packetqueue.clearQueue(monitor->GetPreEventCount(), mVideoStreamId);
          packetqueue.queuePacket(&packet);
        } else if ( packetqueue.size() ) {
          // it's a keyframe or we already have something in the queue
          packetqueue.queuePacket(&packet);
        } 
      } else if ( packet.stream_index == mAudioStreamId ) {
      // The following lines should ensure that the queue always begins with a video keyframe
//Debug(2, "Have audio packet, reocrd_audio is (%d) and packetqueue.size is (%d)", record_audio, packetqueue.size() );
        if ( record_audio && packetqueue.size() ) { 
          // if it's audio, and we are doing audio, and there is already something in the queue
          packetqueue.queuePacket(&packet);
        }
      }
    } // end if recording or not

    if ( packet.stream_index == mVideoStreamId ) {
      // only do decode if we have had a keyframe, should save a few cycles.
      if ( have_video_keyframe || keyframe ) {

        if ( videoStore ) {
              
          //Write the packet to our video store
          int ret = videoStore->writeVideoFramePacket(&packet);
          if ( ret < 0 ) { //Less than zero and we skipped a frame
            zm_av_packet_unref(&packet);
            return 0;
          }
          have_video_keyframe = true;
        }
      } // end if keyframe or have_video_keyframe

      Debug(4, "about to decode video");

#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
      ret = avcodec_send_packet(mVideoCodecContext, &packet);
      if ( ret < 0 ) {
        av_strerror(ret, errbuf, AV_ERROR_MAX_STRING_SIZE);
        Error("Unable to send packet at frame %d: %s, continuing", frameCount, errbuf);
        zm_av_packet_unref(&packet);
        continue;
      }
#if HAVE_AVUTIL_HWCONTEXT_H
        if ( hwaccel ) {
          ret = avcodec_receive_frame(mVideoCodecContext, hwFrame);
          if ( ret < 0 ) {
            av_strerror(ret, errbuf, AV_ERROR_MAX_STRING_SIZE);
            Error("Unable to send packet at frame %d: %s, continuing", frameCount, errbuf);
            zm_av_packet_unref(&packet);
            continue;
          }
          ret = av_hwframe_transfer_data(mRawFrame, hwFrame, 0);
          if (ret < 0) {
            av_strerror(ret, errbuf, AV_ERROR_MAX_STRING_SIZE);
            Error("Unable to transfer frame at frame %d: %s, continuing", frameCount, errbuf);
            zm_av_packet_unref(&packet);
            continue;
          }
        } else {
#endif
          ret = avcodec_receive_frame(mVideoCodecContext, mRawFrame);
          if ( ret < 0 ) {
            av_strerror(ret, errbuf, AV_ERROR_MAX_STRING_SIZE);
            Warning("Unable to receive frame %d: %s, continuing", frameCount, errbuf);
            zm_av_packet_unref(&packet);
            continue;
          }
        
#if HAVE_AVUTIL_HWCONTEXT_H
        }
#endif

        frameComplete = 1;
# else
        ret = zm_avcodec_decode_video(mVideoCodecContext, mRawFrame, &frameComplete, &packet);
        if ( ret < 0 ) {
          av_strerror(ret, errbuf, AV_ERROR_MAX_STRING_SIZE);
          Error("Unable to decode frame at frame %d: %s, continuing", frameCount, errbuf);
          zm_av_packet_unref( &packet );
          continue;
        }
#endif

        if ( frameComplete ) {
          Debug( 4, "Got frame %d", frameCount );

          uint8_t* directbuffer;

          /* Request a writeable buffer of the target image */
          directbuffer = image.WriteBuffer(width, height, colours, subpixelorder);
          if ( directbuffer == NULL ) {
            Error("Failed requesting writeable buffer for the captured image.");
            zm_av_packet_unref( &packet );
            return -1;
          }
#if LIBAVUTIL_VERSION_CHECK(54, 6, 0, 6, 0)
          av_image_fill_arrays(mFrame->data, mFrame->linesize, directbuffer, imagePixFormat, width, height, 1);
#else
          avpicture_fill( (AVPicture *)mFrame, directbuffer, imagePixFormat, width, height);
#endif
          if (sws_scale(mConvertContext, mRawFrame->data, mRawFrame->linesize,
                0, mVideoCodecContext->height, mFrame->data, mFrame->linesize) < 0) {
            Error("Unable to convert raw format %u to target format %u at frame %d",
                mVideoCodecContext->pix_fmt, imagePixFormat, frameCount);
            return -1;
          }

          frameCount++;
        } else {
          Debug( 3, "Not framecomplete after av_read_frame" );
        } // end if frameComplete
    } else if ( packet.stream_index == mAudioStreamId ) { //FIXME best way to copy all other streams
      frameComplete = 1;
      if ( videoStore ) {
        if ( record_audio ) {
          if ( have_video_keyframe ) {
          Debug(3, "Recording audio packet streamindex(%d) packetstreamindex(%d)", mAudioStreamId, packet.stream_index );
          //Write the packet to our video store
          //FIXME no relevance of last key frame
          int ret = videoStore->writeAudioFramePacket( &packet );
          if ( ret < 0 ) {//Less than zero and we skipped a frame
            Warning("Failure to write audio packet.");
            zm_av_packet_unref( &packet );
            return 0;
          }
          } else {
            Debug(3, "Not recording audio yet because we don't have a video keyframe yet");
          }
        } else {
          Debug(4, "Not doing recording of audio packet" );
        }
      } else {
        Debug(4, "Have audio packet, but not recording atm" );
      }
      zm_av_packet_unref( &packet );
      return 0;
    } else {
#if LIBAVUTIL_VERSION_CHECK(56, 23, 0, 23, 0)
      Debug( 3, "Some other stream index %d, %s", packet.stream_index, av_get_media_type_string( mFormatContext->streams[packet.stream_index]->codecpar->codec_type) );
#else
      Debug( 3, "Some other stream index %d", packet.stream_index );
#endif
    } // end if is video or audio or something else
      
    // the packet contents are ref counted... when queuing, we allocate another packet and reference it with that one, so we should always need to unref here, which should not affect the queued version.
    zm_av_packet_unref( &packet );
  } // end while ! frameComplete
  return frameCount;
} // end FfmpegCamera::CaptureAndRecord

int FfmpegCamera::FfmpegInterruptCallback(void *ctx) {
  //FfmpegCamera* camera = reinterpret_cast<FfmpegCamera*>(ctx);

  return zm_terminate;
}

#endif // HAVE_LIBAVFORMAT
