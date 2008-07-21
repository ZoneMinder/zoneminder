//
// ZoneMinder Local Camera Class Interface, $Date$, $Revision$
// Copyright (C) 2003, 2004, 2005, 2006  Philip Coombes
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

#ifndef ZM_LOCAL_CAMERA_H
#define ZM_LOCAL_CAMERA_H

#include <sys/types.h>
#include <sys/ioctl.h>

#include <string.h>

#include <string>

#include "zm.h"
#include "zm_camera.h"

#ifdef HAVE_LINUX_VIDEODEV2_H
#include <linux/videodev2.h>
#define ZM_V4L2
#endif // HAVE_LINUX_VIDEODEV2_H
#ifdef HAVE_LINUX_VIDEODEV_H
#include <linux/videodev.h>
#endif // HAVE_LINUX_VIDEODEV_H

//
// Class representing 'local' cameras, i.e. those which are
// directly connect to the host machine and which are accessed
// via a video interface.
//
class LocalCamera : public Camera
{
protected:
#ifdef ZM_V4L2
    typedef struct
    {
        void    *start;
        size_t  length;
    } V4L2MappedBuffer;

    struct V4L2Data
    {
        v4l2_cropcap        cropcap;
        v4l2_crop           crop;
        v4l2_format         fmt;
        v4l2_requestbuffers reqbufs;
        V4L2MappedBuffer    *buffers;
        v4l2_buffer         *buffer;
    };
#endif // ZM_V4L2

    struct V4L1Data
    {
	    int				    cap_frame;
	    int				    cap_frame_active;
	    int				    sync_frame;
	    video_mbuf		    frames;
	    video_mmap		    *buffers;
	    unsigned char	    *buffer;
    };

protected:
	std::string device;
	int		    channel;
	int		    format;

    int         palette;

    bool        device_prime;
    bool        channel_prime;

    bool        v4l2;

protected:
	static int				camera_count;
	static int				channel_count;
    static int              last_channel;

	static int				vid_fd;

#ifdef ZM_V4L2
    static V4L2Data         v4l2_data;
#endif // ZM_V4L2
    static V4L1Data         v4l1_data;

	static unsigned char	*y_table;
	static signed char		*uv_table;
	static short			*r_v_table;
	static short			*g_v_table;
	static short			*g_u_table;
	static short			*b_u_table;

public:
	LocalCamera( int p_id, const std::string &device, int p_channel, int p_format, const std::string &p_method, int p_width, int p_height, int p_palette, int p_brightness, int p_contrast, int p_hue, int p_colour, bool p_capture );
	~LocalCamera();

	void Initialise();
	void Terminate();

	const std::string &Device() const { return( device ); }
	unsigned int Channel() const { return( channel ); }
	unsigned int Format() const { return( format ); }

	int Palette() const { return( palette ); }

	int Brightness( int p_brightness=-1 );
	int Hue( int p_hue=-1 );
	int Colour( int p_colour=-1 );
	int Contrast( int p_contrast=-1 );

	int PrimeCapture();
	int PreCapture();
	int PostCapture( Image &image );

	static bool GetCurrentSettings( const char *device, char *output, bool verbose );
};

#endif // ZM_LOCAL_CAMERA_H
