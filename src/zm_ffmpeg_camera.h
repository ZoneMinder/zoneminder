//
// ZoneMinder Ffmpeg Class Interface, $Date: 2008-07-25 10:33:23 +0100 (Fri, 25 Jul 2008) $, $Revision: 2611 $
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

#ifndef ZM_FFMPEG_CAMERA_H
#define ZM_FFMPEG_CAMERA_H

#include "zm_camera.h"

#include "zm_buffer.h"
//#include "zm_utils.h"
#include "zm_ffmpeg.h"

//
// Class representing 'remote' cameras, i.e. those which are
// accessed over a network connection.
//
class FfmpegCamera : public Camera
{
protected:
    std::string         mPath;

#if HAVE_LIBAVFORMAT
    AVFormatContext     *mFormatContext;
    int                 mVideoStreamId;
    AVCodecContext      *mCodecContext;
    AVCodec             *mCodec;
    struct SwsContext   *mConvertContext;
    AVFrame             *mRawFrame; 
    AVFrame             *mFrame;
#endif // HAVE_LIBAVFORMAT

	Buffer              mBuffer;

public:
	FfmpegCamera( int p_id, const std::string &path, int p_width, int p_height, int p_colours, int p_brightness, int p_contrast, int p_hue, int p_colour, bool p_capture );
	~FfmpegCamera();

    const std::string &Path() const { return( mPath ); }

	void Initialise();
	void Terminate();

	int PrimeCapture();
	int PreCapture();
	int Capture( Image &image );
	int PostCapture();
};

#endif // ZM_FFMPEG_CAMERA_H
