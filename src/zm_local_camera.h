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
	LocalCamera( int p_device, int p_channel, int p_format, int p_width, int p_height, int p_palette, bool p_capture=true );
	~LocalCamera();

	void Initialise();
	void Terminate();

	unsigned int Device() const { return( device ); }
	unsigned int Channel() const { return( channel ); }
	unsigned int Format() const { return( format ); }

	int PreCapture();
	int PostCapture( Image &image );

	static bool GetCurrentSettings( int device, char *output, bool verbose );
};

#endif // ZM_LOCAL_CAMERA_H
