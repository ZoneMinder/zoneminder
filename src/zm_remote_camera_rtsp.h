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
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// 

#ifndef ZM_REMOTE_CAMERA_RTSP_H
#define ZM_REMOTE_CAMERA_RTSP_H

#include "zm_remote_camera.h"

#include "zm_buffer.h"
#include "zm_utils.h"
#include "zm_rtsp.h"
#include "zm_ffmpeg.h"

//
// Class representing 'remote' cameras, i.e. those which are
// accessed over a network connection.
//
class RemoteCameraRtsp : public RemoteCamera
{
protected:
	struct sockaddr_in rtsp_sa;
	struct sockaddr_in rtcp_sa;
	int rtsp_sd;
	int rtp_sd;
	int rtcp_sd;

	Buffer buffer;

    RtspThread::RtspMethod method;

    RtspThread *rtspThread;

    AVFormatContext *formatContext;
    AVCodec *codec;
    AVCodecContext *codecContext;
    AVFrame *picture;
    int frameCount;

public:
	RemoteCameraRtsp( int p_id, const std::string &method, const std::string &host, const std::string &port, const std::string &path, int p_width, int p_height, int p_colours, int p_brightness, int p_contrast, int p_hue, int p_colour, bool p_capture );
	~RemoteCameraRtsp();

	void Initialise();
	void Terminate();
    int Connect();
    int Disconnect();

	int PrimeCapture();
	int PreCapture();
	int Capture( Image &image );
	int PostCapture();
};

#endif // ZM_REMOTE_CAMERA_RTSP_H
