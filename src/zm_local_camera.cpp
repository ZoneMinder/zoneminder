//
// ZoneMinder Local Camera Class Implementation, $Date$, $Revision$
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

#include "zm_local_camera.h"

#include <sys/types.h>
#include <sys/stat.h>
#include <fcntl.h>
#include <unistd.h>
#include <sys/shm.h>
#include <sys/mman.h>

int LocalCamera::camera_count = 0;
int LocalCamera::m_cap_frame = 0;
int LocalCamera::m_sync_frame = 0;
video_mbuf LocalCamera::m_vmb;
video_mmap *LocalCamera::m_vmm;
int LocalCamera::m_videohandle;
unsigned char *LocalCamera::m_buffer=0;

LocalCamera::LocalCamera( int p_device, int p_channel, int p_format, int p_width, int p_height, int p_colours, bool p_capture ) : Camera( LOCAL, p_width, p_height, p_colours, p_capture ), device( p_device ), channel( p_channel ), format( p_format )
{
	if ( !camera_count++ && capture )
	{
		Initialise();
	}
}

LocalCamera::~LocalCamera()
{
	if ( !--camera_count && capture )
	{
		Terminate();
	}
}

void LocalCamera::Initialise()
{
	char device_path[64];

	sprintf( device_path, "/dev/video%d", device );
	if( (m_videohandle=open(device_path, O_RDONLY)) <=0 )
	{
		Error(( "Failed to open video device %s: %s\n", device_path, strerror(errno) ));
		exit(-1);
	}

	struct video_window vid_win;
	if( !ioctl( m_videohandle, VIDIOCGWIN, &vid_win))
	{
		Info(( "X:%d\n", vid_win.x ));
		Info(( "Y:%d\n", vid_win.y ));
		Info(( "W:%d\n", vid_win.width ));
		Info(( "H:%d\n", vid_win.height ));
	}
	else
	{
		Error(( "Failed to get window attributes: %s\n", strerror(errno) ));
		exit(-1);
	}
	vid_win.x = 0;
	vid_win.y = 0;
	vid_win.width = width;
	vid_win.height = height;

	if( ioctl( m_videohandle, VIDIOCSWIN, &vid_win ) )
	{
		Error(( "Failed to set window attributes: %s\n", strerror(errno) ));
		if ( !ZM_STRICT_VIDEO_CONFIG ) exit(-1);
	}

	struct video_picture vid_pic;
	if( !ioctl( m_videohandle, VIDIOCGPICT, &vid_pic))
	{
		Info(( "P:%d\n", vid_pic.palette ));
		Info(( "D:%d\n", vid_pic.depth ));
		Info(( "B:%d\n", vid_pic.brightness ));
		Info(( "h:%d\n", vid_pic.hue ));
		Info(( "Cl:%d\n", vid_pic.colour ));
		Info(( "Cn:%d\n", vid_pic.contrast ));
	}
	else
	{
		Error(( "Failed to get picture attributes: %s\n", strerror(errno) ));
		exit(-1);
	}

	if ( colours == 1 )
	{
		vid_pic.palette = VIDEO_PALETTE_GREY;
		vid_pic.depth = 8;
	}
	else
	{
		vid_pic.palette = VIDEO_PALETTE_RGB24;
		vid_pic.depth = 24;
	}

	if( ioctl( m_videohandle, VIDIOCSPICT, &vid_pic ) )
	{
		Error(( "Failed to set picture attributes: %s\n", strerror(errno) ));
		if ( !ZM_STRICT_VIDEO_CONFIG ) exit(-1);
	}
	if(!ioctl(m_videohandle, VIDIOCGMBUF, &m_vmb))
	{
		m_vmm = new video_mmap[m_vmb.frames];
		Info(( "vmb.frames = %d\n", m_vmb.frames ));
		Info(( "vmb.size = %d\n", m_vmb.size ));
	}
	else
	{
		Error(( "Failed to setup memory: %s\n", strerror(errno) ));
		exit(-1);
	}

	for(int loop=0; loop < m_vmb.frames; loop++)
	{
		m_vmm[loop].frame = loop;
		m_vmm[loop].width = width;
		m_vmm[loop].height = height;
		m_vmm[loop].format = (colours==1?VIDEO_PALETTE_GREY:VIDEO_PALETTE_RGB24);
	}

	m_buffer = (unsigned char *)mmap(0, m_vmb.size, PROT_READ, MAP_SHARED, m_videohandle,0);
	if( !((long)m_buffer > 0) )
	{
		Error(( "Could not mmap video: %s", strerror(errno) ));
		exit(-1);
	}

	struct video_channel vid_src;
	vid_src.channel = channel;

	if( !ioctl( m_videohandle, VIDIOCGCHAN, &vid_src))
	{
		Info(( "C:%d\n", vid_src.channel ));
		Info(( "F:%d\n", vid_src.norm ));
		Info(( "Fl:%x\n", vid_src.flags ));
		Info(( "T:%d\n", vid_src.type ));
	}
	else
	{
		Error(( "Failed to get camera source: %s\n", strerror(errno) ));
		exit(-1);
	}

	//vid_src.norm = VIDEO_MODE_AUTO;
	vid_src.norm = format;
	vid_src.flags = 0;
	vid_src.type = VIDEO_TYPE_CAMERA;
	if(ioctl(m_videohandle, VIDIOCSCHAN, &vid_src))
	{
		Error(( "Failed to set camera source %d: %s\n", channel, strerror(errno) ));
		if ( !ZM_STRICT_VIDEO_CONFIG ) exit(-1);
	}

	if( !ioctl( m_videohandle, VIDIOCGWIN, &vid_win))
	{
		Info(( "X:%d\n", vid_win.x ));
		Info(( "Y:%d\n", vid_win.y ));
		Info(( "W:%d\n", vid_win.width ));
		Info(( "H:%d\n", vid_win.height ));
	}
	else
	{
		Error(( "Failed to get window data: %s\n", strerror(errno) ));
		exit(-1);
	}

	if( !ioctl( m_videohandle, VIDIOCGPICT, &vid_pic))
	{
		Info(( "P:%d\n", vid_pic.palette ));
		Info(( "D:%d\n", vid_pic.depth ));
		Info(( "B:%d\n", vid_pic.brightness ));
		Info(( "h:%d\n", vid_pic.hue ));
		Info(( "Cl:%d\n", vid_pic.colour ));
		Info(( "Cn:%d\n", vid_pic.contrast ));
	}
	else
	{
		Error(( "Failed to get window data: %s\n", strerror(errno) ));
		exit(-1);
	}
}

void LocalCamera::Terminate()
{
	munmap((char*)m_buffer, m_vmb.size);

	delete[] m_vmm;

	close(m_videohandle);
}

bool LocalCamera::GetCurrentSettings( int device, char *output, bool verbose )
{
	char device_path[64];

	output[0] = 0;
	sprintf( device_path, "/dev/video%d", device );
	if ( verbose )
		sprintf( output, output+strlen(output), "Checking Video Device: %s\n", device_path );
	if( (m_videohandle=open(device_path, O_RDONLY)) <=0 )
	{
		Error(( "Failed to open video device %s: %s\n", device_path, strerror(errno) ));
		if ( verbose )
			sprintf( output+strlen(output), "Error, failed to open video device: %s\n", strerror(errno) );
		else
			sprintf( output+strlen(output), "error%d\n", errno );
		return( false );
	}

	struct video_capability vid_cap;
	if( !ioctl( m_videohandle, VIDIOCGCAP, &vid_cap))
	{
		if ( verbose )
		{
			sprintf( output+strlen(output), "Video Capabilities\n" );
			sprintf( output+strlen(output), "  Name: %s\n", vid_cap.name );
			sprintf( output+strlen(output), "  Type: %d\n%s%s%s%s%s%s%s%s%s%s%s%s%s%s", vid_cap.type,
				vid_cap.type&VID_TYPE_CAPTURE?"    Can capture\n":"",
				vid_cap.type&VID_TYPE_TUNER?"    Can tune\n":"",
				vid_cap.type&VID_TYPE_TELETEXT?"    Does teletext\n":"",
				vid_cap.type&VID_TYPE_OVERLAY?"    Overlay onto frame buffer\n":"",
				vid_cap.type&VID_TYPE_CHROMAKEY?"    Overlay by chromakey\n":"",
				vid_cap.type&VID_TYPE_CLIPPING?"    Can clip\n":"",
				vid_cap.type&VID_TYPE_FRAMERAM?"    Uses the frame buffer memory\n":"",
				vid_cap.type&VID_TYPE_SCALES?"    Scalable\n":"",
				vid_cap.type&VID_TYPE_MONOCHROME?"    Monochrome only\n":"",
				vid_cap.type&VID_TYPE_SUBCAPTURE?"    Can capture subareas of the image\n":"",
				vid_cap.type&VID_TYPE_MPEG_DECODER?"    Can decode MPEG streams\n":"",
				vid_cap.type&VID_TYPE_MPEG_ENCODER?"    Can encode MPEG streams\n":"",
				vid_cap.type&VID_TYPE_MJPEG_DECODER?"    Can decode MJPEG streams\n":"",
				vid_cap.type&VID_TYPE_MJPEG_ENCODER?"    Can encode MJPEG streams\n":""
			);
			sprintf( output+strlen(output), "  Video Channels: %d\n", vid_cap.channels );
			sprintf( output+strlen(output), "  Audio Channels: %d\n", vid_cap.audios );
			sprintf( output+strlen(output), "  Maximum Width: %d\n", vid_cap.maxwidth );
			sprintf( output+strlen(output), "  Maximum Height: %d\n", vid_cap.maxheight );
			sprintf( output+strlen(output), "  Minimum Width: %d\n", vid_cap.minwidth );
			sprintf( output+strlen(output), "  Minimum Height: %d\n", vid_cap.minheight );
		}
		else
		{
			sprintf( output+strlen(output), "N:%s,", vid_cap.name );
			sprintf( output+strlen(output), "T:%d,", vid_cap.type );
			sprintf( output+strlen(output), "nC:%d,", vid_cap.channels );
			sprintf( output+strlen(output), "nA:%d,", vid_cap.audios );
			sprintf( output+strlen(output), "mxW:%d,", vid_cap.maxwidth );
			sprintf( output+strlen(output), "mxH:%d,", vid_cap.maxheight );
			sprintf( output+strlen(output), "mnW:%d,", vid_cap.minwidth );
			sprintf( output+strlen(output), "mnH:%d,", vid_cap.minheight );
		}
	}
	else
	{
		Error(( "Failed to get video capabilities: %s", strerror(errno) ));
		if ( verbose )
			sprintf( output, "Error, failed to get video capabilities: %s\n", strerror(errno) );
		else
			sprintf( output, "error%d\n", errno );
		return( false );
	}

	struct video_window vid_win;
	if( !ioctl( m_videohandle, VIDIOCGWIN, &vid_win))
	{
		if ( verbose )
		{
			sprintf( output+strlen(output), "Window Attributes\n" );
			sprintf( output+strlen(output), "  X Offset: %d\n", vid_win.x );
			sprintf( output+strlen(output), "  Y Offset: %d\n", vid_win.y );
			sprintf( output+strlen(output), "  Width: %d\n", vid_win.width );
			sprintf( output+strlen(output), "  Height: %d\n", vid_win.height );
		}
		else
		{
			sprintf( output+strlen(output), "X:%d,", vid_win.x );
			sprintf( output+strlen(output), "Y:%d,", vid_win.y );
			sprintf( output+strlen(output), "W:%d,", vid_win.width );
			sprintf( output+strlen(output), "H:%d,", vid_win.height );
		}
	}
	else
	{
		Error(( "Failed to get window attributes: %s", strerror(errno) ));
		if ( verbose )
			sprintf( output, "Error, failed to get window attributes: %s\n", strerror(errno) );
		else
			sprintf( output, "error%d\n", errno );
		return( false );
	}

	struct video_picture vid_pic;
	if( !ioctl( m_videohandle, VIDIOCGPICT, &vid_pic))
	{
		if ( verbose )
		{
			sprintf( output+strlen(output), "Picture Atributes\n" );
			sprintf( output+strlen(output), "  Palette: %d - %s\n", vid_pic.palette, 
				vid_pic.palette==VIDEO_PALETTE_GREY?"Linear greyscale":(
				vid_pic.palette==VIDEO_PALETTE_HI240?"High 240 cube (BT848)":(
				vid_pic.palette==VIDEO_PALETTE_RGB565?"565 16 bit RGB":(
				vid_pic.palette==VIDEO_PALETTE_RGB24?"24bit RGB":(
				vid_pic.palette==VIDEO_PALETTE_RGB32?"32bit RGB":(
				vid_pic.palette==VIDEO_PALETTE_RGB555?"555 15bit RGB":(
				vid_pic.palette==VIDEO_PALETTE_YUV422?"YUV422 capture":(
				vid_pic.palette==VIDEO_PALETTE_YUYV?"YUYV":(
				vid_pic.palette==VIDEO_PALETTE_UYVY?"UVYV":(
				vid_pic.palette==VIDEO_PALETTE_YUV420?"YUV420":(
				vid_pic.palette==VIDEO_PALETTE_YUV411?"YUV411 capture":(
				vid_pic.palette==VIDEO_PALETTE_RAW?"RAW capture (BT848)":(
				vid_pic.palette==VIDEO_PALETTE_YUV422P?"YUV 4:2:2 Planar":(
				vid_pic.palette==VIDEO_PALETTE_YUV411P?"YUV 4:1:1 Planar":(
				vid_pic.palette==VIDEO_PALETTE_YUV420P?"YUV 4:2:0 Planar":(
				vid_pic.palette==VIDEO_PALETTE_YUV410P?"YUV 4:1:0 Planar":"Unknown"
			))))))))))))))));
			sprintf( output+strlen(output), "  Colour Depth: %d\n", vid_pic.depth );
			sprintf( output+strlen(output), "  Brightness: %d\n", vid_pic.brightness );
			sprintf( output+strlen(output), "  Hue: %d\n", vid_pic.hue );
			sprintf( output+strlen(output), "  Colour :%d\n", vid_pic.colour );
			sprintf( output+strlen(output), "  Contrast: %d\n", vid_pic.contrast );
			sprintf( output+strlen(output), "  Whiteness: %d\n", vid_pic.whiteness );
		}
		else
		{
			sprintf( output+strlen(output), "P:%d,", vid_pic.palette );
			sprintf( output+strlen(output), "D:%d,", vid_pic.depth );
			sprintf( output+strlen(output), "B:%d,", vid_pic.brightness );
			sprintf( output+strlen(output), "h:%d,", vid_pic.hue );
			sprintf( output+strlen(output), "Cl:%d,", vid_pic.colour );
			sprintf( output+strlen(output), "Cn:%d,", vid_pic.contrast );
			sprintf( output+strlen(output), "w:%d,", vid_pic.whiteness );
		}
	}
	else
	{
		Error(( "Failed to get picture attributes: %s", strerror(errno) ));
		if ( verbose )
			sprintf( output, "Error, failed to get picture attributes: %s\n", strerror(errno) );
		else
			sprintf( output, "error%d\n", errno );
		return( false );
	}

	for ( int chan = 0; chan < vid_cap.channels; chan++ )
	{
		struct video_channel vid_src;
		vid_src.channel = chan;
		if( !ioctl( m_videohandle, VIDIOCGCHAN, &vid_src))
		{
			if ( verbose )
			{
				sprintf( output+strlen(output), "Channel %d Attributes\n", chan );
				sprintf( output+strlen(output), "  Name: %s\n", vid_src.name );
				sprintf( output+strlen(output), "  Channel: %d\n", vid_src.channel );
				sprintf( output+strlen(output), "  Flags: %d\n%s%s", vid_src.flags,
					vid_src.flags&VIDEO_VC_TUNER?"    Channel has a tuner\n":"",
					vid_src.flags&VIDEO_VC_AUDIO?"    Channel has audio\n":""
				);
				sprintf( output+strlen(output), "  Type: %d - %s\n", vid_src.type,
					vid_src.type==VIDEO_TYPE_TV?"TV":(
					vid_src.type==VIDEO_TYPE_CAMERA?"Camera":"Unknown"
				));
				sprintf( output+strlen(output), "  Format: %d - %s\n", vid_src.norm,
					vid_src.norm==VIDEO_MODE_PAL?"PAL":(
					vid_src.norm==VIDEO_MODE_NTSC?"NTSC":(
					vid_src.norm==VIDEO_MODE_SECAM?"SECAM":(
					vid_src.norm==VIDEO_MODE_AUTO?"AUTO":"Unknown"
				))));
			}
			else
			{
				sprintf( output+strlen(output), "n%d:%s,", chan, vid_src.name );
				sprintf( output+strlen(output), "C%d:%d,", chan, vid_src.channel );
				sprintf( output+strlen(output), "Fl%d:%x,", chan, vid_src.flags );
				sprintf( output+strlen(output), "T%d:%d", chan, vid_src.type );
				sprintf( output+strlen(output), "F%d:%d%s,", chan, vid_src.norm, chan==(vid_cap.channels-1)?"":"," );
			}
		}
		else
		{
			Error(( "Failed to get channel %d attributes: %s\n", chan, strerror(errno) ));
			if ( verbose )
				sprintf( output, "Error, failed to get channel %d attributes: %s\n", chan, strerror(errno) );
			else
				sprintf( output, "error%d\n", errno );
			return( false );
		}
	}
	return( true );
}
