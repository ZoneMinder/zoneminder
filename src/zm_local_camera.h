//
// ZoneMinder Local Camera Class Interface, $Date$, $Revision$
// Copyright (C) 2003  Philip Coombes
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
#include <linux/videodev.h>

#include "zm_camera.h"

//
// Class representing 'local' cameras, i.e. those which are
// directly connect to the host machine and which are accessed
// via a video interface.
//
class LocalCamera : public Camera
{
protected:
	int			device;
	int			channel;
	int			format;

protected:
	static int m_cap_frame;
	static int m_sync_frame;
	static video_mbuf m_vmb;
	static video_mmap *m_vmm;
	static int m_videohandle;
	static unsigned char *m_buffer;
	static int camera_count;

public:
	LocalCamera( int p_device, int p_channel, int p_format, int p_width, int p_height, int p_colours, bool p_capture=true );
	~LocalCamera();

	void Initialise();
	void Terminate();

	unsigned int Device() const { return( device ); }
	unsigned int Channel() const { return( channel ); }
	unsigned int Format() const { return( format ); }

	int PreCapture()
	{
		//Info(( "%s: Capturing image\n", id ));

		if ( camera_count > 1 )
		{
			//Info(( "Switching\n" ));
			struct video_channel vs;

			vs.channel = channel;
			//vs.norm = VIDEO_MODE_AUTO;
			vs.norm = format;
			vs.flags = 0;
			vs.type = VIDEO_TYPE_CAMERA;
			if(ioctl(m_videohandle, VIDIOCSCHAN, &vs))
			{
				Error(( "Failed to set camera source %d: %s\n", channel, strerror(errno) ));
				return( -1 );
			}
		}
		//Info(( "MC:%d\n", m_videohandle ));
		if ( ioctl(m_videohandle, VIDIOCMCAPTURE, &m_vmm[m_cap_frame]) )
		{
			Error(( "Capture failure for frame %d: %s\n", m_cap_frame, strerror(errno)));
			return( -1 );
		}
		m_cap_frame = (m_cap_frame+1)%m_vmb.frames;
		return( 0 );
	}
	unsigned char *PostCapture()
	{
		//Info(( "%s: Capturing image\n", id ));

		if ( ioctl(m_videohandle, VIDIOCSYNC, &m_sync_frame) )
		{
			Error(( "Sync failure for frame %d: %s\n", m_sync_frame, strerror(errno)));
			return( 0 );
		}

		unsigned char *buffer = m_buffer+(m_sync_frame*m_vmb.size/m_vmb.frames);
		m_sync_frame = (m_sync_frame+1)%m_vmb.frames;

		return( buffer );
	}
	int PostCapture( Image &image )
	{
		//Info(( "%s: Capturing image\n", id ));

		if ( ioctl(m_videohandle, VIDIOCSYNC, &m_sync_frame) )
		{
			Error(( "Sync failure for frame %d: %s\n", m_sync_frame, strerror(errno)));
			return( -1 );
		}

		unsigned char *buffer = m_buffer+(m_sync_frame*m_vmb.size/m_vmb.frames);
		m_sync_frame = (m_sync_frame+1)%m_vmb.frames;

		image.Assign( width, height, colours, buffer );

		return( 0 );
	}

	static bool GetCurrentSettings( int device, char *output, bool verbose );
};

#endif // ZM_LOCAL_CAMERA_H
