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
#include "zm_mpeg.h"
#include "zm_event.h"
#include "zm_monitor.h"

#include "zmf.h"

bool Event::initialised = false;
bool Event::timestamp_on_capture;
int Event::bulk_frame_interval;

Event::Event( Monitor *p_monitor, struct timeval p_start_time ) : monitor( p_monitor ), start_time( p_start_time )
{
	if ( !initialised )
		Initialise();

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

	int socket_buffer_size = (int)config.Item( ZM_FRAME_SOCKET_SIZE ); 
	if ( socket_buffer_size > 0 )
	{
		if ( setsockopt( sd, SOL_SOCKET, SO_SNDBUF, &socket_buffer_size, sizeof(socket_buffer_size) ) < 0 )
		{
			Error(( "Can't get socket buffer size to %d, error = %s", socket_buffer_size, strerror(errno) ));
			close( sd );
			sd = -1;
			return( false );
		}
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

bool Event::WriteFrameImage( Image *image, struct timeval timestamp, const char *event_file, bool alarm_frame )
{
	if ( timestamp_on_capture )
	{
		if ( !(bool)config.Item( ZM_OPT_FRAME_SERVER ) || !SendFrameImage( image, alarm_frame) )
		{
			image->WriteJpeg( event_file );
		}
	}
	else
	{
		Image ts_image( *image );
		monitor->TimestampImage( &ts_image, timestamp.tv_sec );
		if ( !(bool)config.Item( ZM_OPT_FRAME_SERVER ) || !SendFrameImage( &ts_image, alarm_frame) )
		{
			ts_image.WriteJpeg( event_file );
		}
	}
	return( true );
}

void Event::AddFrames( int n_frames, Image **images, struct timeval **timestamps )
{
	static char sql[BUFSIZ];
	strcpy( sql, "insert into Frames ( EventId, FrameId, Delta ) values " );
	for ( int i = 0; i < n_frames; i++ )
	{
		frames++;

		static char event_file[PATH_MAX];
		sprintf( event_file, "%s/%03d-capture.jpg", path, frames );
		
		Debug( 1, ( "Writing pre-capture frame %d", frames ));
		WriteFrameImage( images[i], *(timestamps[i]), event_file );

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

void Event::AddFrame( Image *image, struct timeval timestamp, int score, Image *alarm_image )
{
	frames++;

	static char event_file[PATH_MAX];
	sprintf( event_file, "%s/%03d-capture.jpg", path, frames );
		
	Debug( 1, ( "Writing capture frame %d", frames ));
	WriteFrameImage( image, timestamp, event_file );

	struct DeltaTimeval delta_time;
	DELTA_TIMEVAL( delta_time, timestamp, start_time, DT_PREC_2 );

	bool db_frame = (score>=0) || ((frames%bulk_frame_interval)==0) || !frames;

	if ( db_frame )
	{
		const char *frame_type = score>0?"Alarm":(score<0?"Bulk":"Normal");

		Debug( 1, ( "Adding frame %d to DB", frames ));
		static char sql[BUFSIZ];
		sprintf( sql, "insert into Frames ( EventId, FrameId, Type, Delta, Score ) values ( %d, %d, '%s', %s%ld.%02ld, %d )", id, frames, frame_type, delta_time.positive?"":"-", delta_time.sec, delta_time.fsec, score );
		if ( mysql_query( &dbconn, sql ) )
		{
			Error(( "Can't insert frame: %s", mysql_error( &dbconn ) ));
			exit( mysql_errno( &dbconn ) );
		}
	}

	if ( score > 0 )
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
			WriteFrameImage( alarm_image, timestamp, event_file, true );
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

void Event::StreamEvent( int event_id, int rate, int scale )
{
	static char sql[BUFSIZ];
	static char eventpath[PATH_MAX];
	
	sprintf( sql, "select M.Id, M.Name, E.Frames from Events as E inner join Monitors as M on E.MonitorId = M.Id where E.Id = %d", event_id );
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
	int frames = atoi(dbrow[2] );

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

	fprintf( stdout, "Content-Type: multipart/x-mixed-replace;boundary=ZoneMinderFrame\r\n\r\n" );
	fprintf( stdout, "--ZoneMinderFrame\n" );

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

		fprintf( stdout, "Content-type: image/jpeg\n\n" );
		if ( scale == 100 )
		{
			if ( (fdj = fopen( filepath, "r" )) )
			{
				while ( (n_bytes = fread( buffer, 1, sizeof(buffer), fdj )) )
				{
					write( fileno(stdout), buffer, n_bytes );
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

			write( fileno(stdout), buffer, n_bytes );
		}
		fprintf( stdout, "\n--ZoneMinderFrame\n" );
		fflush( stdout );
	}
	if ( mysql_errno( &dbconn ) )
	{
		Error(( "Can't fetch row: %s", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}
	// Yadda yadda
	mysql_free_result( result );
}

#if HAVE_LIBAVCODEC     

void Event::StreamMpeg( int event_id, const char *format, int bitrate, int rate, int scale )
{
	static char sql[BUFSIZ];
	static char eventpath[PATH_MAX];
	
	//sprintf( sql, "select M.Id, M.Name,max(F.Delta)-min(F.Delta) as Duration, count(F.Id) as Frames from Events as E inner join Monitors as M on E.MonitorId = M.Id inner join Frames as F on F.EventId = E.Id where E.Id = %d group by F.EventId", event_id );
	sprintf( sql, "select M.Id, M.Name, E.Length, E.Frames from Events as E inner join Monitors as M on E.MonitorId = M.Id where E.Id = %d", event_id );
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
	int duration = atoi(dbrow[2]);
	int frames = atoi(dbrow[3]);

	int min_fps = 1;
	int max_fps = 30;
	int base_fps = frames/duration;
	int effective_fps = (base_fps*rate)/ZM_RATE_SCALE;

	int frame_mod = 1;
	// Min frame repeat?
	while( effective_fps > max_fps )
	{
		effective_fps /= 2;
		frame_mod *= 2; 
	}

	Info(( "Duration:%d, Frames:%d, BFPS:%d, EFPS:%d, FM:%d", atoi(dbrow[2]), atoi(dbrow[3]), base_fps, effective_fps, frame_mod ));

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

	fprintf( stdout, "Content-type: video/x-ms-asf\r\n\r\n");

	VideoStream *vid_stream = 0;
	int id = 1, last_id = 0;
	double base_delta, last_delta = 0.0L;
	unsigned int delta_ms =0;
	while( (id <= frames) && (dbrow = mysql_fetch_row( result )) )
	{
		if ( id == 1 )
		{
			base_delta = last_delta = atof(dbrow[2]);
		}

		int db_id = atoi( dbrow[0] );
		double db_delta = atof( dbrow[2] )-base_delta;
		while( db_id >= id )
		{
			if ( (frame_mod == 1) || (((id-1)%frame_mod) == 0) )
			{
				static char filepath[PATH_MAX];
				sprintf( filepath, "%s/%03d-capture.jpg", eventpath, id );

				Image image( filepath );

				if ( !vid_stream )
				{
					vid_stream = new VideoStream( "pipe:", format, bitrate, effective_fps, image.Colours(), (image.Width()*scale)/ZM_SCALE_SCALE, (image.Height()*scale)/ZM_SCALE_SCALE );
				}

				if ( scale != 100 )
				{
					image.Scale( scale );
				}

				double temp_delta = ((id-last_id)*(db_delta-last_delta))/(db_id-last_id);
				delta_ms = (unsigned int)((last_delta+temp_delta)*1000);
				if ( rate != ZM_RATE_SCALE )
					delta_ms = (delta_ms*ZM_RATE_SCALE)/rate;
				double pts = vid_stream->EncodeFrame( image.Buffer(), image.Size(), true, delta_ms );

				//Info(( "I:%d, DI:%d, LI:%d, DD:%lf, LD:%lf, TD:%lf, DM:%d, PTS:%lf", id, db_id, last_id, db_delta, last_delta, temp_delta, delta_ms, pts ));
			}
			id++;
		}
		last_id = db_id;
		last_delta = db_delta;
	}
	if ( mysql_errno( &dbconn ) )
	{
		Error(( "Can't fetch row: %s", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}
	// Yadda yadda
	mysql_free_result( result );

	delete vid_stream;
}

#endif // HAVE_LIBAVCODEC     

