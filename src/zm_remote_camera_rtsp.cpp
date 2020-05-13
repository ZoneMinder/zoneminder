//
// ZoneMinder Remote Camera Class Implementation, $Date$, $Revision$
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

#if HAVE_LIBAVFORMAT

#include "zm_remote_camera_rtsp.h"
#include "zm_ffmpeg.h"
#include "zm_mem_utils.h"

#include <sys/types.h>
#include <sys/socket.h>

RemoteCameraRtsp::RemoteCameraRtsp(
    unsigned int p_monitor_id,
    const std::string &p_method,
    const std::string &p_host,
    const std::string &p_port,
    const std::string &p_path,
    int p_width,
    int p_height,
    bool p_rtsp_describe,
    int p_colours,
    int p_brightness,
    int p_contrast,
    int p_hue,
    int p_colour,
    bool p_capture,
    bool p_record_audio ) :
  RemoteCamera( p_monitor_id, "rtsp", p_host, p_port, p_path, p_width, p_height, p_colours, p_brightness, p_contrast, p_hue, p_colour, p_capture, p_record_audio ),
  rtsp_describe( p_rtsp_describe ),
  rtspThread( 0 )

{
  if ( p_method == "rtpUni" )
    method = RtspThread::RTP_UNICAST;
  else if ( p_method == "rtpMulti" )
    method = RtspThread::RTP_MULTICAST;
  else if ( p_method == "rtpRtsp" )
    method = RtspThread::RTP_RTSP;
  else if ( p_method == "rtpRtspHttp" )
    method = RtspThread::RTP_RTSP_HTTP;
  else
    Fatal("Unrecognised method '%s' when creating RTSP camera %d", p_method.c_str(), monitor_id);

  if ( capture ) {
    Initialise();
  }
  
  mFormatContext = NULL;
  mVideoStreamId = -1;
  mAudioStreamId = -1;
  mCodecContext = NULL;
  mCodec = NULL;
  mRawFrame = NULL;
  mFrame = NULL;
  frameCount = 0;
  startTime=0;
  
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
    Panic("Unexpected colours: %d", colours);
  }
} // end RemoteCameraRtsp::RemoteCameraRtsp(...)

RemoteCameraRtsp::~RemoteCameraRtsp() {
  av_frame_free(&mFrame);
  av_frame_free(&mRawFrame);
  
#if HAVE_LIBSWSCALE
  if ( mConvertContext ) {
    sws_freeContext(mConvertContext);
    mConvertContext = NULL;
  }
#endif

  if ( mCodecContext ) {
     avcodec_close(mCodecContext);
     mCodecContext = NULL; // Freed by avformat_free_context in the destructor of RtspThread class
  }

  if ( capture ) {
    Terminate();
  }
}

void RemoteCameraRtsp::Initialise() {
  RemoteCamera::Initialise();

  int max_size = width*height*colours;

  // This allocates a buffer able to hold a raw fframe, which is a little artbitrary.  Might be nice to get some
  // decent data on how large a buffer is really needed.  I think in ffmpeg there are now some functions to do that.
  buffer.size(max_size);

  FFMPEGInit();

  Connect();
}

void RemoteCameraRtsp::Terminate() {
  Disconnect();
}

int RemoteCameraRtsp::Connect() {
  rtspThread = new RtspThread(monitor_id, method, protocol, host, port, path, auth, rtsp_describe);

  rtspThread->start();

  return 0;
}

int RemoteCameraRtsp::Disconnect() {
  if ( rtspThread ) {
    rtspThread->stop();
    rtspThread->join();
    delete rtspThread;
    rtspThread = 0;
  }
  return 0;
}

int RemoteCameraRtsp::PrimeCapture() {
  Debug(2, "Waiting for sources");
  for ( int i = 0; (i < 100) && !rtspThread->hasSources(); i++ ) {
    usleep(100000);
  }
  if ( !rtspThread->hasSources() ) {
    Error("No RTSP sources");
    return -1;
  }

  Debug(2, "Got sources");

  mFormatContext = rtspThread->getFormatContext();

  // Find first video stream present
  mVideoStreamId = -1;
  mAudioStreamId = -1;
  
  // Find the first video stream. 
  for ( unsigned int i = 0; i < mFormatContext->nb_streams; i++ ) {
    if ( is_video_stream(mFormatContext->streams[i]) ) {
      if ( mVideoStreamId == -1 ) {
        mVideoStreamId = i;
        continue;
      } else {
        Debug(2, "Have another video stream.");
      }
    } else if ( is_audio_stream(mFormatContext->streams[i]) ) {
      if ( mAudioStreamId == -1 ) {
        mAudioStreamId = i;
      } else {
        Debug(2, "Have another audio stream.");
      }
    } else {
      Debug(1, "Have unknown codec type in stream %d", i);
    }
  } // end foreach stream

  if ( mVideoStreamId == -1 )
    Fatal("Unable to locate video stream");
  if ( mAudioStreamId == -1 )
    Debug(3, "Unable to locate audio stream");

  // Get a pointer to the codec context for the video stream
  mCodecContext = mFormatContext->streams[mVideoStreamId]->codec;

  // Find the decoder for the video stream
  mCodec = avcodec_find_decoder(mCodecContext->codec_id);
  if ( mCodec == NULL )
    Panic("Unable to locate codec %d decoder", mCodecContext->codec_id);

  // Open codec
#if !LIBAVFORMAT_VERSION_CHECK(53, 8, 0, 8, 0)
  if ( avcodec_open(mCodecContext, mCodec) < 0 )
#else
  if ( avcodec_open2(mCodecContext, mCodec, 0) < 0 )
#endif
    Panic("Can't open codec");

  // Allocate space for the native video frame
  mRawFrame = zm_av_frame_alloc();

  // Allocate space for the converted video frame
  mFrame = zm_av_frame_alloc();

  if ( mRawFrame == NULL || mFrame == NULL )
    Fatal("Unable to allocate frame(s)");

#if LIBAVUTIL_VERSION_CHECK(54, 6, 0, 6, 0)
  int pSize = av_image_get_buffer_size(imagePixFormat, width, height, 1);
#else
  int pSize = avpicture_get_size(imagePixFormat, width, height);
#endif

  if ( (unsigned int)pSize != imagesize ) {
    Fatal("Image size mismatch. Required: %d Available: %d", pSize, imagesize);
  }
/*  
#if HAVE_LIBSWSCALE
  if(!sws_isSupportedInput(mCodecContext->pix_fmt)) {
    Fatal("swscale does not support the codec format: %c%c%c%c",(mCodecContext->pix_fmt)&0xff,((mCodecContext->pix_fmt>>8)&0xff),((mCodecContext->pix_fmt>>16)&0xff),((mCodecContext->pix_fmt>>24)&0xff));
  }

  if(!sws_isSupportedOutput(imagePixFormat)) {
    Fatal("swscale does not support the target format: %c%c%c%c",(imagePixFormat)&0xff,((imagePixFormat>>8)&0xff),((imagePixFormat>>16)&0xff),((imagePixFormat>>24)&0xff));
  }
  
#else // HAVE_LIBSWSCALE
  Fatal( "You must compile ffmpeg with the --enable-swscale option to use RTSP cameras" );
#endif // HAVE_LIBSWSCALE
*/

  return 0;
}

int RemoteCameraRtsp::PreCapture() {
  if ( !rtspThread->isRunning() )
    return -1;
  if ( !rtspThread->hasSources() ) {
    Error("Cannot precapture, no RTP sources");
    return -1;
  }
  return 0;
}

int RemoteCameraRtsp::Capture( Image &image ) {
  AVPacket packet;
  uint8_t* directbuffer;
  int frameComplete = false;
  
  /* Request a writeable buffer of the target image */
  directbuffer = image.WriteBuffer(width, height, colours, subpixelorder);
  if ( directbuffer == NULL ) {
    Error("Failed requesting writeable buffer for the captured image.");
    return -1;
  }
  
  while ( true ) {
    buffer.clear();
    if ( !rtspThread->isRunning() )
      return -1;

    if ( rtspThread->getFrame(buffer) ) {
      Debug(3, "Read frame %d bytes", buffer.size());
      Hexdump(4, buffer.head(), 16);

      if ( !buffer.size() )
        return -1;

      if ( mCodecContext->codec_id == AV_CODEC_ID_H264 ) {
        // SPS and PPS frames should be saved and appended to IDR frames
        int nalType = (buffer.head()[3] & 0x1f);
        
        // SPS The SPS NAL unit contains parameters that apply to a series of consecutive coded video pictures
        if ( nalType == 7 ) {
          lastSps = buffer;
          continue;
        } else if ( nalType == 8 ) {
        // PPS The PPS NAL unit contains parameters that apply to the decoding of one or more individual pictures inside a coded video sequence
          lastPps = buffer;
          continue;
        } else if ( nalType == 5 ) {
        // IDR
          buffer += lastSps;
          buffer += lastPps;
        } else {
          Debug(2, "Unknown nalType %d", nalType);
        }
      } else {
        Debug(3, "Not an h264 packet");
      }

      av_init_packet(&packet);
      while ( (!frameComplete) && (buffer.size() > 0) ) {
        packet.data = buffer.head();
        packet.size = buffer.size();
        bytes += packet.size;

        // So I think this is the magic decode step. Result is a raw image?
        int len = zm_send_packet_receive_frame(mCodecContext, mRawFrame, packet);
        if ( len < 0 ) {
          Error("Error while decoding frame %d", frameCount);
          Hexdump(Logger::ERROR, buffer.head(), buffer.size()>256?256:buffer.size());
          buffer.clear();
          continue;
        }
        frameComplete = true;
        Debug(2, "Frame: %d - %d/%d", frameCount, len, buffer.size());
        //if ( buffer.size() < 400 )
        //Hexdump( 0, buffer.head(), buffer.size() );
        
        buffer -= len;
      }
      // At this point, we either have a frame or ran out of buffer. What happens if we run out of buffer?
      if ( frameComplete ) {
         
        Debug(3, "Got frame %d", frameCount);
            
#if LIBAVUTIL_VERSION_CHECK(54, 6, 0, 6, 0)
        // From what I've read, we should align the linesizes to 32bit so that ffmpeg can use SIMD instructions too.
        int size = av_image_fill_arrays(
            mFrame->data, mFrame->linesize,
            directbuffer, imagePixFormat, width, height,
            (AV_PIX_FMT_RGBA == imagePixFormat ? 32 : 1)
            );
        if ( size < 0 ) {
          Error("Problem setting up data pointers into image %s",
              av_make_error_string(size).c_str());
        }
#else
        avpicture_fill((AVPicture *)mFrame, directbuffer, imagePixFormat, width, height);
#endif
          
    #if HAVE_LIBSWSCALE
        if ( mConvertContext == NULL ) {
          mConvertContext = sws_getContext(
              mCodecContext->width, mCodecContext->height, mCodecContext->pix_fmt,
              width, height, imagePixFormat, SWS_BICUBIC, NULL, NULL, NULL);

          if ( mConvertContext == NULL )
            Fatal("Unable to create conversion context");

          if (
              ((unsigned int)mRawFrame->width != width)
              ||
              ((unsigned int)mRawFrame->height != height)
             ) {
            Warning("Monitor dimensions are %dx%d but camera is sending %dx%d",
                width, height, mRawFrame->width, mRawFrame->height);
          }
        }
      
        if ( sws_scale(mConvertContext, mRawFrame->data, mRawFrame->linesize, 0, mCodecContext->height, mFrame->data, mFrame->linesize) < 0 )
          Fatal("Unable to convert raw format %u to target format %u at frame %d",
              mCodecContext->pix_fmt, imagePixFormat, frameCount );
    #else // HAVE_LIBSWSCALE
        Fatal("You must compile ffmpeg with the --enable-swscale option to use RTSP cameras");
    #endif // HAVE_LIBSWSCALE
      
        frameCount++;

      } /* frame complete */
       
      zm_av_packet_unref(&packet);
    } /* getFrame() */
   
    if ( frameComplete )
      return 1;
  
  } // end while true

  // can never get here.
  return 0;
}

//Function to handle capture and store

int RemoteCameraRtsp::PostCapture() {
  return 0;
}
#endif // HAVE_LIBAVFORMAT
