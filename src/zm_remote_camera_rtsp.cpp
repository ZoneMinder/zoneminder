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

#include "zm_remote_camera_rtsp.h"

#include "zm_config.h"
#include "zm_monitor.h"
#include "zm_packet.h"
#include "zm_signal.h"

RemoteCameraRtsp::RemoteCameraRtsp(
  const Monitor *monitor,
  const std::string &p_method,
  const std::string &p_host,
  const std::string &p_port,
  const std::string &p_path,
  const std::string &p_user,
  const std::string &p_pass,
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
  RemoteCamera(
    monitor, "rtsp",
    p_host, p_port, p_path,
    p_user, p_pass,
    p_width, p_height, p_colours,
    p_brightness, p_contrast, p_hue, p_colour,
    p_capture, p_record_audio),
  rtsp_describe(p_rtsp_describe),
  user(p_user),
  pass(p_pass),
  frameCount(0) {
  if ( p_method == "rtpUni" )
    method = RtspThread::RTP_UNICAST;
  else if ( p_method == "rtpMulti" )
    method = RtspThread::RTP_MULTICAST;
  else if ( p_method == "rtpRtsp" )
    method = RtspThread::RTP_RTSP;
  else if ( p_method == "rtpRtspHttp" )
    method = RtspThread::RTP_RTSP_HTTP;
  else
    Fatal("Unrecognised method '%s' when creating RTSP camera %d", p_method.c_str(), monitor->Id());

  if ( capture ) {
    Initialise();
  }

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

  if ( mVideoCodecContext ) {
    //avcodec_close(mVideoCodecContext);
    avcodec_free_context(&mVideoCodecContext);
    mVideoCodecContext = nullptr; // Freed by avformat_free_context in the destructor of RtspThread class
  }
  // Is allocated in RTSPThread and is free there as well
  mFormatContext = nullptr;

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
}

void RemoteCameraRtsp::Terminate() {
  Disconnect();
}

int RemoteCameraRtsp::Connect() {
  rtspThread = zm::make_unique<RtspThread>(monitor->Id(), method, protocol, host, port, path, user, pass, rtsp_describe);

  return 0;
}

int RemoteCameraRtsp::Disconnect() {
  if (rtspThread) {
    rtspThread->Stop();
    rtspThread.reset();
  }
  return 0;
}

int RemoteCameraRtsp::PrimeCapture() {
  if (rtspThread) Disconnect();
  Connect();

  Debug(2, "Waiting for sources");
  for (int i = 100; i && !zm_terminate && !rtspThread->hasSources(); i--) {
    std::this_thread::sleep_for(Microseconds(10000));
  }

  if (!rtspThread->hasSources()) {
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
        mVideoStream = mFormatContext->streams[i];
        mVideoStream->time_base = AV_TIME_BASE_Q;
        continue;
      } else {
        Debug(2, "Have another video stream.");
      }
#if 0
    } else if ( is_audio_stream(mFormatContext->streams[i]) ) {
      if ( mAudioStreamId == -1 ) {
        mAudioStreamId = i;
        mAudioStream = mFormatContext->streams[i];
      } else {
        Debug(2, "Have another audio stream.");
      }
#endif
    } else {
      Debug(1, "Have unknown codec type in stream %d", i);
    }
  } // end foreach stream

  if ( mVideoStreamId == -1 ) {
    Error("Unable to locate video stream");
    return -1;
  }
  if ( mAudioStreamId == -1 )
    Debug(3, "Unable to locate audio stream");

  // Get a pointer to the codec context for the video stream
  mVideoCodecContext = avcodec_alloc_context3(nullptr);
  avcodec_parameters_to_context(mVideoCodecContext, mFormatContext->streams[mVideoStreamId]->codecpar);

  // Find the decoder for the video stream
  const AVCodec *codec = avcodec_find_decoder(mVideoCodecContext->codec_id);
  if ( codec == nullptr ) {
    Error("Unable to locate codec %d decoder", mVideoCodecContext->codec_id);
    return -1;
  }

  // Open codec
  if ( avcodec_open2(mVideoCodecContext, codec, nullptr) < 0 ) {
    Error("Can't open codec");
    return -1;
  }

  int pSize = av_image_get_buffer_size(imagePixFormat, width, height, 1);

  if ( (unsigned int)pSize != imagesize ) {
    Error("Image size mismatch. Required: %d Available: %llu", pSize, imagesize);
    return -1;
  }

  return 1;
}  // end PrimeCapture

int RemoteCameraRtsp::PreCapture() {
  if (!rtspThread || rtspThread->IsStopped())
    return -1;
  if ( !rtspThread->hasSources() ) {
    Error("Cannot precapture, no RTP sources");
    return -1;
  }
  return 1;
}

int RemoteCameraRtsp::Capture(std::shared_ptr<ZMPacket> &zm_packet) {
  int frameComplete = false;
  AVPacket *packet = zm_packet->packet.get();

  while (!frameComplete) {
    buffer.clear();
    if (!rtspThread || rtspThread->IsStopped() || zm_terminate)
      return -1;

    if (rtspThread->getFrame(buffer)) {
      Debug(3, "Read frame %d bytes", buffer.size());
      Hexdump(4, buffer.head(), 16);

      if ( !buffer.size() )
        return -1;

      if ( mVideoCodecContext->codec_id == AV_CODEC_ID_H264 ) {
        // SPS and PPS frames should be saved and appended to IDR frames
        int nalType = (buffer.head()[3] & 0x1f);

        // SPS The SPS NAL unit contains parameters that apply to a series of consecutive coded video pictures
        if ( nalType == 1 ) {
        } else if ( nalType == 7 ) {
          lastSps = buffer;
          continue;
        } else if ( nalType == 8 ) {
          // PPS The PPS NAL unit contains parameters that apply to the decoding of one or more individual pictures inside a coded video sequence
          lastPps = buffer;
          continue;
        } else if ( nalType == 5 ) {
          packet->flags |= AV_PKT_FLAG_KEY;
          zm_packet->keyframe = 1;
          // IDR
          buffer += lastSps;
          buffer += lastPps;
        } else {
          Debug(2, "Unknown nalType %d", nalType);
        }
      } else {
        Debug(3, "Not an h264 packet");
      }

      //while ( (!frameComplete) && (buffer.size() > 0) ) {
      if ( buffer.size() > 0 ) {
        packet->data = (uint8_t*)av_malloc(buffer.size());
        memcpy(packet->data, buffer.head(), buffer.size());
        //packet->data = buffer.head();
        packet->size = buffer.size();
        bytes += packet->size;
        buffer -= packet->size;

        struct timeval now;
        gettimeofday(&now, nullptr);
        packet->pts = packet->dts = now.tv_sec*1000000+now.tv_usec;
        zm_packet->codec_type = mVideoCodecContext->codec_type;
        zm_packet->stream = mVideoStream;
        frameComplete = true;
        Debug(2, "Frame: %d - %d/%d", frameCount, packet->size, buffer.size());
      }
    } /* getFrame() */
  } // end while true

  return 1;
} // end int RemoteCameraRtsp::Capture(ZMPacket &packet)

int RemoteCameraRtsp::PostCapture() {
  return 1;
}
