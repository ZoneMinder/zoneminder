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

#include "zm_ffmpeg.h"
#include "zm_remote_camera.h"
#include "zm_rtsp.h"

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

  const std::string user;
  const std::string pass;

  Buffer buffer;
  Buffer lastSps;
  Buffer lastPps;

  RtspThread::RtspMethod method;

  std::unique_ptr<RtspThread> rtspThread;

  int frameCount;

  _AVPIXELFORMAT         imagePixFormat;

 public:
  RemoteCameraRtsp(
    const Monitor *monitor,
    const std::string &method,
    const std::string &host,
    const std::string &port,
    const std::string &path,
    const std::string &user,
    const std::string &pass,
    int p_width,
    int p_height,
    bool p_rtsp_describe,
    int p_colours,
    int p_brightness,
    int p_contrast,
    int p_hue,
    int p_colour,
    bool p_capture,
    bool p_record_audio);
  ~RemoteCameraRtsp();

  void Initialise() override;
  void Terminate() override;
  int Connect() override;
  int Disconnect() override;

  int PrimeCapture() override;
  int PreCapture() override;
  int Capture(std::shared_ptr <ZMPacket> &p) override;
  int PostCapture() override;
  int Close() override { return 0; };

  AVStream *get_VideoStream() {
    if ( mVideoStreamId != -1 )
      return mFormatContext->streams[mVideoStreamId];
    return nullptr;
  }
  AVStream *get_AudioStream() {
    if ( mAudioStreamId != -1 )
      return mFormatContext->streams[mAudioStreamId];
    return nullptr;
  }
  AVCodecContext      *get_VideoCodecContext() { return mVideoCodecContext; };
  AVCodecContext      *get_AudioCodecContext() { return mAudioCodecContext; };
};

#endif // ZM_REMOTE_CAMERA_RTSP_H
