//
// ZoneMinder Core Interfaces, $Date$, $Revision$
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

#ifndef ZM_EVENT_H
#define ZM_EVENT_H

//#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <errno.h>
#include <limits.h>
//#include <math.h>
#include <time.h>
//#include <signal.h>
//#include <assert.h>
#include <sys/stat.h>
#include <sys/types.h>
//#include <sys/time.h>
//#include <sys/mman.h>
//#include <sys/ioctl.h>
#include <mysql/mysql.h>

#include "zm.h"
#include "zm_image.h"

class Monitor;

//
// Class describing events, i.e. captured periods of activity.
//
class Event
{
protected:
	static int		sd;

protected:
	int				id;
	Monitor			*monitor;
	struct timeval	start_time;
	struct timeval	end_time;
	int				frames;
	int				alarm_frames;
	unsigned int	tot_score;
	unsigned int	max_score;
	char			path[PATH_MAX];

public:
	static bool OpenFrameSocket( int );
	static bool ValidateFrameSocket( int );

public:
	Event( Monitor *p_monitor, struct timeval p_start_time );
	~Event();

	int Id() const { return( id ); }
	int Frames() const { return( frames ); }
	int AlarmFrames() const { return( alarm_frames ); }

	const struct timeval &StartTime() const { return( start_time ); }
	const struct timeval &EndTime() const { return( end_time ); }
	struct timeval &EndTime() { return( end_time ); }

	bool SendFrameImage( const Image *image, bool alarm_frame=false );
	bool WriteFrameImage( const Image *image, const char *event_file, bool alarm_frame=false );

	void AddFrames( int n_frames, struct timeval **timestamps, const Image **images );
	void AddFrame( struct timeval timestamp, const Image *image, unsigned int score=0, const Image *alarm_frame=NULL );

	static void StreamEvent( const char *path, int event_id, int rate=1, int scale=1, FILE *fd=stdout );
};

#endif // ZM_EVENT_H
