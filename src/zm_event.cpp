//
// ZoneMinder Evnet Class Implementation, $Date$, $Revision$
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

#include "zm.h"
#include "zm_db.h"
#include "zm_event.h"
#include "zm_monitor.h"

Event::Event( Monitor *p_monitor, struct timeval p_start_time ) : monitor( p_monitor ), start_time( p_start_time )
{
	static char sql[256];
	static char start_time_str[32];

	strftime( start_time_str, sizeof(start_time_str), "%Y-%m-%d %H:%M:%S", localtime( &start_time.tv_sec ) );
	sprintf( sql, "insert into Events ( MonitorId, Name, StartTime ) values ( %d, 'Event', '%s' )", monitor->Id(), start_time_str );
	if ( mysql_query( &dbconn, sql ) )
	{
		Error(( "Can't insert event: %s", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}
	id = mysql_insert_id( &dbconn );
	frames = 0;
	alarm_frames = 0;
	tot_score = 0;
	max_score = 0;
	sprintf( path, ZM_DIR_EVENTS "/%s/%d", monitor->Name(), id );
	
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
	static char sql[256];
	static char end_time_str[32];

	struct DeltaTimeval delta_time;
	DELTA_TIMEVAL( delta_time, end_time, start_time );

	strftime( end_time_str, sizeof(end_time_str), "%Y-%m-%d %H:%M:%S", localtime( &end_time.tv_sec ) );

	sprintf( sql, "update Events set Name='Event-%d', EndTime = '%s', Length = %s%ld.%02ld, Frames = %d, AlarmFrames = %d, TotScore = %d, AvgScore = %d, MaxScore = %d where Id = %d", id, end_time_str, delta_time.positive?"":"-", delta_time.tv_sec, delta_time.tv_usec/10000, frames, alarm_frames, tot_score, (int)(tot_score/alarm_frames), max_score, id );
	if ( mysql_query( &dbconn, sql ) )
	{
		Error(( "Can't update event: %s", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}
}

void Event::AddFrames( int n_frames, struct timeval **timestamps, const Image **images )
{
	static char sql[4096];
	strcpy( sql, "insert into Frames ( EventId, FrameId, ImagePath, Delta ) values " );
	for ( int i = 0; i < n_frames; i++ )
	{
		frames++;
		Debug( 1, ( "Writing pre-capture frame %d", frames ));
		static char event_file[PATH_MAX];
		sprintf( event_file, "%s/capture-%03d.jpg", path, frames );
		images[i]->WriteJpeg( event_file );

		struct DeltaTimeval delta_time;
		DELTA_TIMEVAL( delta_time, *(timestamps[i]), start_time );

		sprintf( sql+strlen(sql), "( %d, %d, '%s', %s%ld.%02ld ), ", id, frames, event_file, delta_time.positive?"":"-", delta_time.tv_sec, delta_time.tv_usec/10000 );
	}

	Debug( 1, ( "Adding %d frames to DB", n_frames ));
	*(sql+strlen(sql)-2) = '\0';
	if ( mysql_query( &dbconn, sql ) )
	
	{
		Error(( "Can't insert frames: %s", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}
}

void Event::AddFrame( struct timeval timestamp, const Image *image, const Image *alarm_image, unsigned int score )
{
	frames++;

	Debug( 1, ( "Writing capture frame %d", frames ));
	static char event_file[PATH_MAX];
	sprintf( event_file, "%s/capture-%03d.jpg", path, frames );
	image->WriteJpeg( event_file );

	struct DeltaTimeval delta_time;
	DELTA_TIMEVAL( delta_time, timestamp, start_time );

	Debug( 1, ( "Adding frame %d to DB", frames ));
	static char sql[256];
	sprintf( sql, "insert into Frames ( EventId, FrameId, AlarmFrame, ImagePath, Delta, Score ) values ( %d, %d, %d, '%s', %s%ld.%02ld, %d )", id, frames, alarm_image!=0, event_file, delta_time.positive?"":"-", delta_time.tv_sec, delta_time.tv_usec/10000, score );
	if ( mysql_query( &dbconn, sql ) )
	{
		Error(( "Can't insert frame: %s", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}

	if ( alarm_image )
	{
		Debug( 1, ( "Writing analysis frame %d", frames ));
		end_time = timestamp;

		alarm_frames++;
		sprintf( event_file, "%s/analyse-%03d.jpg", path, frames );
		alarm_image->WriteJpeg( event_file );
		tot_score += score;
		if ( score > max_score )
			max_score = score;
	}
}

void Event::StreamEvent( const char *path, int event_id, unsigned long refresh, FILE *fd )
{
	static char sql[256];
	sprintf( sql, "select Id, EventId, ImagePath, TimeStamp from Frames where EventId = %d order by Id", event_id );
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

	setbuf( fd, 0 );
	fprintf( fd, "Server: ZoneMinder Stream Server\r\n" );
	fprintf( fd, "Pragma: no-cache\r\n" );
	fprintf( fd, "Cache-Control: no-cache\r\n" );
	fprintf( fd, "Expires: Thu, 01 Dec 1994 16:00:00 GMT\r\n" );
	fprintf( fd, "Content-Type: multipart/x-mixed-replace;boundary=ZoneMinderFrame\r\n\r\n" );
	fprintf( fd, "--ZoneMinderFrame\r\n" );

	int n_frames = mysql_num_rows( result );
	Info(( "Got %d frames", n_frames ));
	FILE *fdj = NULL;
	int n_bytes = 0;
	static unsigned char buffer[0x10000];
	for( int i = 0; MYSQL_ROW dbrow = mysql_fetch_row( result ); i++ )
	{
		char filepath[PATH_MAX];
		sprintf( filepath, "%s/%s", path, dbrow[2] );
		if ( (fdj = fopen( filepath, "r" )) )
		{
			fprintf( fd, "Content-type: image/jpg\r\n\r\n" );
			while ( (n_bytes = fread( buffer, 1, sizeof(buffer), fdj )) )
			{
				fwrite( buffer, 1, n_bytes, fd );
			}
			fprintf( fd, "\r\n--ZoneMinderFrame\r\n" );
			fclose( fdj );
		}
		else
		{
			Error(( "Can't open %s: %s", filepath, strerror(errno) ));
		}
		usleep( refresh*1000 );
	}
	if ( mysql_errno( &dbconn ) )
	{
		Error(( "Can't fetch row: %s", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}
	// Yadda yadda
	mysql_free_result( result );
}
