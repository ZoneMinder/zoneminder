//
// ZoneMinder Remote RTSP Camera Class Interface, $Date$, $Revision$
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

#ifndef ZM_REMOTE_CAMERA_RTSP_H
#define ZM_REMOTE_CAMERA_RTSP_H

#include "zm_remote_camera.h"

#include "zm_buffer.h"
#include "zm_utils.h"
#include "zm_rtsp.h"
#include "zm_ffmpeg.h"
#include "zm_videostore.h"
#include "zm_packetqueue.h"

//
// Class representing 'rtsp' cameras, i.e. those which are
// accessed over a network connection using rtsp protocol
// (Real Time Streaming Protocol)
//
class RemoteCameraRtsp : public RemoteCamera {
protected:
  struct sockaddr_in rtsp_sa;
  struct sockaddr_in rtcp_sa;
  int rtsp_sd;
  int rtp_sd;
  int rtcp_sd;
  bool rtsp_describe;

  Buffer buffer;
  Buffer lastSps;
  Buffer lastPps;

  RtspThread::RtspMethod method;

  RtspThread *rtspThread;

  int frameCount;

#if HAVE_LIBAVFORMAT
  AVFormatContext     *mFormatContext;
  int                 mVideoStreamId;
  int                 mAudioStreamId;
  AVCodecContext      *mCodecContext;
  AVCodec             *mCodec;
  AVFrame             *mRawFrame; 
  AVFrame             *mFrame;
  _AVPIXELFORMAT         imagePixFormat;
#endif // HAVE_LIBAVFORMAT
  bool                wasRecording;
  VideoStore          *videoStore;
  char                oldDirectory[4096];
  int64_t             startTime;

#if HAVE_LIBSWSCALE
  struct SwsContext   *mConvertContext;
#endif

public:
  RemoteCameraRtsp( unsigned int p_monitor_id, const std::string &method, const std::string &host, const std::string &port, const std::string &path, int p_width, int p_height, bool p_rtsp_describe, int p_colours, int p_brightness, int p_contrast, int p_hue, int p_colour, bool p_capture, bool p_record_audio );
  ~RemoteCameraRtsp();

  void Initialise();
  void Terminate();
  int Connect();
  int Disconnect();

  int PrimeCapture();
  int PreCapture();
  int Capture( Image &image );
  int PostCapture();
  int CaptureAndRecord( Image &image, timeval recording, char* event_directory ) {return 0;};
  int Close() { return 0; };
};

#endif // ZM_REMOTE_CAMERA_RTSP_H
