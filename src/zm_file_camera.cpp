//
// ZoneMinder File Camera Class Implementation, $Date$, $Revision$
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

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <time.h>
#include <sys/time.h>
#include <syslog.h>
#include <signal.h>
#include <stdarg.h>
#include <errno.h>
#include <sys/types.h>
#include <sys/time.h>
#include <sys/stat.h>

#include "zm.h"
#include "zm_file_camera.h"

FileCamera::FileCamera( int p_id, const char *p_path, int p_width, int p_height, int p_colours, int p_brightness, int p_contrast, int p_hue, int p_colour, bool p_capture ) : Camera( p_id, FILE_SRC, p_width, p_height, p_colours, ZM_SUBPIX_ORDER_DEFAULT_FOR_COLOUR(p_colours), p_brightness, p_contrast, p_hue, p_colour, p_capture )
{
	strncpy( path, p_path, sizeof(path) );
	if ( capture )
	{
		Initialise();
	}
}

FileCamera::~FileCamera()
{
	if ( capture )
	{
		Terminate();
	}
}

void FileCamera::Initialise()
{
	if ( !path[0] )
	{
		Error( "No path specified for file image" );
		exit( -1 );
	}
}

void FileCamera::Terminate()
{
}

int FileCamera::PreCapture()
{
	struct stat statbuf;
	if ( stat( path, &statbuf ) < 0 )
	{
		Error( "Can't stat %s: %s", path, strerror(errno) );
		return( -1 );
	}

	while ( (time( 0 ) - statbuf.st_mtime) < 1 )
	{
		usleep( 100000 );
	}
	return( 0 );
}

int FileCamera::Capture( Image &image )
{
	return( image.ReadJpeg( path, colours, subpixelorder )?0:-1 );
}

int FileCamera::PostCapture()
{
    return( 0 );
}
