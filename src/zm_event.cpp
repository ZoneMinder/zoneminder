//
// ZoneMinder Event Class Implementation, $Date$, $Revision$
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

#include <fcntl.h>
#include <sys/socket.h>
#include <sys/un.h>
#include <sys/uio.h>
#include <getopt.h>
#include <glob.h>

#include "zm.h"
#include "zm_db.h"
#include "zm_event.h"
#include "zm_monitor.h"

#include "zmf.h"

Event::Event( Monitor *p_monitor, struct timeval p_start_time ) : monitor( p_monitor ), start_time( p_start_time )
{
	static char sql[BUFSIZ];
	static char start_time_str[32];

	strftime( start_time_str, sizeof(start_time_str), "%Y-%m-%d %H:%M:%S", localtime( &start_time.tv_sec ) );
	sprintf( sql, "insert into Events ( MonitorId, Name, StartTime ) values ( %d, 'New Event', '%s' )", monitor->Id(), start_time_str );
	if ( mysql_query( &dbconn, sql ) )
	{
		Error(( "Can't insert event: %s", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}
	id = mysql_insert_id( &dbconn );
	end_time.tv_sec = 0;
	frames = 0;
	alarm_frames = 0;
	tot_score = 0;
	max_score = 0;
	sprintf( path, "%s/%s/%d", (const char *)config.Item( ZM_DIR_EVENTS ), monitor->Name(), id );
	
	struct stat statbuf;
	errno = 0;
	stat( path, &statbuf );
	if ( errno == ENOENT || errno == ENOTDIR )
	{
		if ( mkdir( path, 0755 ) )
		{
			Error(( "Can't make %s: %s", path, strerror(errno)));
		}
	}
}

Event::~Event()
{
	static char sql[BUFSIZ];
	static char end_time_str[32];

	struct DeltaTimeval delta_time;
	DELTA_TIMEVAL( delta_time, end_time, start_time, DT_PREC_2 );

	strftime( end_time_str, sizeof(end_time_str), "%Y-%m-%d %H:%M:%S", localtime( &end_time.tv_sec ) );

	sprintf( sql, "update Events set Name='Event-%d', EndTime = '%s', Length = %s%ld.%02ld, Frames = %d, AlarmFrames = %d, TotScore = %d, AvgScore = %d, MaxScore = %d where Id = %d", id, end_time_str, delta_time.positive?"":"-", delta_time.sec, delta_time.fsec, frames, alarm_frames, tot_score, (int)(alarm_frames?(tot_score/alarm_frames):0), max_score, id );
	if ( mysql_query( &dbconn, sql ) )
	{
		Error(( "Can't update event: %s", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}
}

int Event::sd = -1;

bool Event::OpenFrameSocket( int monitor_id )
{
	if ( sd > 0 )
	{
		close( sd );
	}

	sd = socket( AF_UNIX, SOCK_STREAM, 0);
	if ( sd < 0 )
	{
		Error(( "Can't create socket: %s", strerror(errno) ));
		return( false );
	}

	int flags;
	if ( (flags = fcntl( sd, F_GETFL )) < 0 )
	{
		Error(( "Can't get socket flags, error = %s", strerror(errno) ));
		close( sd );
		sd = -1;
		return( false );
	}
	flags |= O_NONBLOCK;
	if ( fcntl( sd, F_SETFL, flags ) < 0 )
	{
		Error(( "Can't set socket flags, error = %s", strerror(errno) ));
		close( sd );
		sd = -1;
		return( false );
	}

	char sock_path[PATH_MAX] = "";
	sprintf( sock_path, "%s/zmf-%d.sock", (const char *)config.Item( ZM_PATH_SOCKS ), monitor_id );

	struct sockaddr_un addr;

	strcpy( addr.sun_path, sock_path );
	addr.sun_family = AF_UNIX;

	if ( connect( sd, (struct sockaddr *)&addr, strlen(addr.sun_path)+sizeof(addr.sun_family)) < 0 )
	{
		Warning(( "Can't connect: %s", strerror(errno) ));
		close( sd );
		sd = -1;
		return( false );
	}

	Info(( "Opened connection to frame server" ));
	return( true );
}

bool Event::ValidateFrameSocket( int monitor_id )
{
	if ( sd < 0 )
	{
		return( OpenFrameSocket( monitor_id ) );
	}
	return( true );
}

bool Event::SendFrameImage( const Image *image, bool alarm_frame )
{
	if ( !ValidateFrameSocket( monitor->Id() ) )
	{
		return( false );
	}

	static int jpg_buffer_size = 0;
	static unsigned char jpg_buffer[ZM_MAX_IMAGE_SIZE];

	image->EncodeJpeg( jpg_buffer, &jpg_buffer_size );

	static FrameHeader frame_header;

	frame_header.event_id = id;
	frame_header.frame_id = frames;
	frame_header.alarm_frame = alarm_frame;
	frame_header.image_length = jpg_buffer_size;

	struct iovec iovecs[2];
	iovecs[0].iov_base = &frame_header;
	iovecs[0].iov_len = sizeof(frame_header);
	iovecs[1].iov_base = jpg_buffer;
	iovecs[1].iov_len = jpg_buffer_size;

	ssize_t writev_size = sizeof(frame_header)+jpg_buffer_size;
	ssize_t writev_result = writev( sd, iovecs, sizeof(iovecs)/sizeof(*iovecs));
	if ( writev_result != writev_size )
	{
		if ( writev_result < 0 )
		{
			if ( errno == EAGAIN )
			{
				Warning(( "Blocking write detected" ));
			}
			else
			{
				Error(( "Can't write frame: %s", strerror(errno) ));
				close( sd );
				sd = -1;
			}
		}
		else
		{
			Error(( "Incomplete frame write: %d of %d bytes written", writev_result, writev_size ));
			close( sd );
			sd = -1;
		}
		return( false );
	}
	Debug( 1, ( "Wrote frame image, %d bytes", jpg_buffer_size ));

	return( true );
}

bool Event::WriteFrameImage( const Image *image, const char *event_file, bool alarm_frame )
{
	if ( !(bool)config.Item( ZM_OPT_FRAME_SERVER ) || !SendFrameImage( image, alarm_frame) )
	{
		image->WriteJpeg( event_file );
	}
	return( true );
}

void Event::AddFrames( int n_frames, struct timeval **timestamps, const Image **images )
{
	static char sql[BUFSIZ];
	strcpy( sql, "insert into Frames ( EventId, FrameId, Delta ) values " );
	for ( int i = 0; i < n_frames; i++ )
	{
		frames++;

		static char event_file[PATH_MAX];
		sprintf( event_file, "%s/%03d-capture.jpg", path, frames );
		
		Debug( 1, ( "Writing pre-capture frame %d", frames ));
		WriteFrameImage( images[i], event_file );

		struct DeltaTimeval delta_time;
		DELTA_TIMEVAL( delta_time, *(timestamps[i]), start_time, DT_PREC_2 );

		sprintf( sql+strlen(sql), "( %d, %d, %s%ld.%02ld ), ", id, frames, delta_time.positive?"":"-", delta_time.sec, delta_time.fsec );
	}

	Debug( 1, ( "Adding %d frames to DB", n_frames ));
	*(sql+strlen(sql)-2) = '\0';
	if ( mysql_query( &dbconn, sql ) )
	{
		Error(( "Can't insert frames: %s", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}
}

void Event::AddFrame( struct timeval timestamp, const Image *image, unsigned int score, const Image *alarm_image )
{
	frames++;

	static char event_file[PATH_MAX];
	sprintf( event_file, "%s/%03d-capture.jpg", path, frames );
		
	Debug( 1, ( "Writing capture frame %d", frames ));
	WriteFrameImage( image, event_file );

	struct DeltaTimeval delta_time;
	DELTA_TIMEVAL( delta_time, timestamp, start_time, DT_PREC_2 );

	Debug( 1, ( "Adding frame %d to DB", frames ));
	static char sql[BUFSIZ];
	sprintf( sql, "insert into Frames ( EventId, FrameId, AlarmFrame, Delta, Score ) values ( %d, %d, %d, %s%ld.%02ld, %d )", id, frames, score>0, delta_time.positive?"":"-", delta_time.sec, delta_time.fsec, score );
	if ( mysql_query( &dbconn, sql ) )
	{
		Error(( "Can't insert frame: %s", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}

	if ( score )
	{
		end_time = timestamp;

		alarm_frames++;

		tot_score += score;
		if ( score > max_score )
			max_score = score;

		if ( alarm_image )
		{
			sprintf( event_file, "%s/%03d-analyse.jpg", path, frames );

			Debug( 1, ( "Writing analysis frame %d", frames ));
			WriteFrameImage( alarm_image, event_file, true );
		}
	}

	if ( (bool)config.Item( ZM_RECORD_DIAG_IMAGES ) )
	{
		char diag_glob[PATH_MAX] = "";

		sprintf( diag_glob, "%s/%s/diag-*.jpg", (const char *)config.Item( ZM_DIR_EVENTS ), monitor->Name() );
		glob_t pglob;
		int glob_status = glob( diag_glob, 0, 0, &pglob );
		if ( glob_status != 0 )
		{
			if ( glob_status < 0 )
			{
				Error(( "Can't glob '%s': %s", diag_glob, strerror(errno) ));
			}
			else
			{
				Info(( "Can't glob '%s': %s", diag_glob, glob_status ));
			}
		}
		else
		{
			char new_diag_path[PATH_MAX] = "";
			for ( int i = 0; i < pglob.gl_pathc; i++ )
			{
				char *diag_path = pglob.gl_pathv[i];

				char *diag_file = strstr( diag_path, "diag-" );

				if ( diag_file )
				{
					sprintf( new_diag_path, "%s/%03d-%s", path, frames, diag_file );

					if ( rename( diag_path, new_diag_path ) < 0 )
					{
						Error(( "Can't rename '%s' to '%s': %s", diag_path, new_diag_path, strerror(errno) ));
					}
				}
			}
		}
		globfree( &pglob );
	}
}

void Event::StreamEvent( int event_id, int rate, int scale, FILE *fd )
{
	static char sql[BUFSIZ];
	static char eventpath[PATH_MAX];
	
	sprintf( sql, "select M.Id, M.Name from Events as E inner join Monitors as M on E.MonitorId = M.Id where E.Id = %d", event_id );
	if ( mysql_query( &dbconn, sql ) )
	{
		Error(( "Can't run query: %s", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}

	MYSQL_RES *result = mysql_store_result( &dbconn );
	if ( !result )
	{
		Error(( "Can't use query result: %s", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}
	MYSQL_ROW dbrow = mysql_fetch_row( result );

	if ( mysql_errno( &dbconn ) )
	{
		Error(( "Can't fetch row: %s", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}

	sprintf( eventpath, "%s/%s/%s/%d", ZM_PATH_WEB, (const char *)config.Item( ZM_DIR_EVENTS ), dbrow[1], event_id );

	mysql_free_result( result );

	sprintf( sql, "select FrameId, EventId, Delta from Frames where EventId = %d order by FrameId", event_id );
	if ( mysql_query( &dbconn, sql ) )
	{
		Error(( "Can't run query: %s", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}

	result = mysql_store_result( &dbconn );
	if ( !result )
	{
		Error(( "Can't use query result: %s", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}

	setbuf( fd, 0 );

	time_t cache_now = time( 0 );
	char date_string[64];
	strftime( date_string, sizeof(date_string)-1, "%a, %d %b %Y %H:%M:%S GMT", gmtime( &cache_now ) );

	fprintf( fd, "Server: ZoneMinder Stream Server\r\n" );

	fprintf( fd, "Expires: Mon, 26 Jul 1997 05:00:00 GMT\r\n" );
	fprintf( fd, "Last-Modified: %s\r\n", date_string );
	fprintf( fd, "Cache-Control: no-store, no-cache, must-revalidate\r\n" );
	fprintf( fd, "Cache-Control: post-check=0, pre-check=0\r\n" );
	fprintf( fd, "Pragma: no-cache\r\n");

	fprintf( fd, "Content-Type: multipart/x-mixed-replace;boundary=ZoneMinderFrame\r\n\r\n" );
	fprintf( fd, "--ZoneMinderFrame\n" );

	//int n_frames = mysql_num_rows( result );
	//Info(( "Got %d frames, at rate %d, scale %d", n_frames, rate, scale ));
	FILE *fdj = NULL;
	int n_bytes = 0;
	static unsigned char buffer[ZM_MAX_IMAGE_SIZE];
	double last_delta = 0;
	struct timeval now, last_now;
	struct DeltaTimeval delta_time;

	gettimeofday( &now, &dummy_tz );
	for( int i = 0; dbrow = mysql_fetch_row( result ); i++ )
	{
		if ( rate )
		{
			double this_delta = atof(dbrow[2]);
			if ( i )
			{
				gettimeofday( &now, &dummy_tz );

				double frame_delta = this_delta-last_delta;
				DELTA_TIMEVAL( delta_time, now, last_now, DT_PREC_6 );
				
				int delay = (int)((DT_GRAN_1000000*frame_delta))-delta_time.delta;

				delay = (delay * ZM_RATE_SCALE) / rate;

				//Info(( "FD:%lf, DDT:%d, D:%d, N:%d.%d, LN:%d.%d", frame_delta, delta_time.delta, delay, now.tv_sec, now.tv_usec, last_now.tv_sec, last_now.tv_usec ));
				if ( delay > 0 )
					usleep( delay );
			}
			last_delta = this_delta;
			gettimeofday( &last_now, &dummy_tz );
		}
		static char filepath[PATH_MAX];
		sprintf( filepath, "%s/%03d-capture.jpg", eventpath, atoi(dbrow[0]) );

		fprintf( fd, "Content-type: image/jpg\n\n" );
		if ( scale == 1 )
		{
			if ( (fdj = fopen( filepath, "r" )) )
			{
				while ( (n_bytes = fread( buffer, 1, sizeof(buffer), fdj )) )
				{
					//fwrite( buffer, 1, n_bytes, fd );
					write( fileno(fd), buffer, n_bytes );
				}
				fclose( fdj );
			}
			else
			{
				Error(( "Can't open %s: %s", filepath, strerror(errno) ));
			}
		}
		else
		{
			Image image( filepath );

			image.Scale( scale );

			image.EncodeJpeg( buffer, &n_bytes );

			write( fileno(fd), buffer, n_bytes );
		}
		fprintf( fd, "\n--ZoneMinderFrame\n" );
		fflush( fd );
	}
	if ( mysql_errno( &dbconn ) )
	{
		Error(( "Can't fetch row: %s", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}
	// Yadda yadda
	mysql_free_result( result );
}
