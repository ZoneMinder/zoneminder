//
// ZoneMinder Streaming Server, $Date$, $Revision$
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
#include "zm_monitor.h"

int main(void )
{
	enum { ZMS_JPEG, ZMS_MPEG } mode = ZMS_JPEG;
	char format[32] = "";
	int id = 1;
	int event = 0;
	unsigned int bitrate = 100000;
	unsigned int rate = 100;
	unsigned int scale = 100;
	unsigned int buffer = 0;
	unsigned int ttl = 0;
	unsigned long idle = 5000;
	unsigned long refresh = 50;

	const char *query = getenv( "QUERY_STRING" );
	if ( query )
	{
		 Info(( "Query: %s", query ));
	
		char temp_query[1024];
		strcpy( temp_query, query );
		char *q_ptr = temp_query;
		char *parms[16]; // Shouldn't be more than this
		int parm_no = 0;
		while( (parms[parm_no] = strtok( q_ptr, "&" )) )
		{
			parm_no++;
			q_ptr = NULL;
		}
	
		for ( int p = 0; p < parm_no; p++ )
		{
			char *name = strtok( parms[p], "=" );
			char *value = strtok( NULL, "=" );
			if ( !strcmp( name, "mode" ) )
				mode = !strcmp( value, "jpeg" )?ZMS_JPEG:ZMS_MPEG;
			else if ( !strcmp( name, "monitor" ) )
				id = atoi( value );
			else if ( !strcmp( name, "event" ) )
				event = strtoull( value, (char **)NULL, 10 );
			else if ( !strcmp( name, "format" ) )
				strncpy( format, value, sizeof(format) );
			else if ( !strcmp( name, "bitrate" ) )
				bitrate = atoi( value );
			else if ( !strcmp( name, "rate" ) )
				rate = atoi( value );
			else if ( !strcmp( name, "scale" ) )
				scale = atoi( value );
			else if ( !strcmp( name, "buffer" ) )
				buffer = atol( value );
			else if ( !strcmp( name, "ttl" ) )
				ttl = atoi(value);
			else if ( !strcmp( name, "refresh" ) )
				refresh = atol( value );
			else if ( !strcmp( name, "idle" ) )
				idle = atol( value );
		}
	}

	zm_dbg_name = "zms";

	zmDbgInit();

	zmDbConnect( ZM_DB_USERA, ZM_DB_PASSA );

	setbuf( stdout, 0 );
	fprintf( stdout, "Server: ZoneMinder Video Server/%s\r\n", ZM_VERSION );
		        
	time_t now = time( 0 );
	char date_string[64];
	strftime( date_string, sizeof(date_string)-1, "%a, %d %b %Y %H:%M:%S GMT", gmtime( &now ) );

	fprintf( stdout, "Expires: Mon, 26 Jul 1997 05:00:00 GMT\r\n" );
	fprintf( stdout, "Last-Modified: %s\r\n", date_string );
	fprintf( stdout, "Cache-Control: no-store, no-cache, must-revalidate\r\n" );
	fprintf( stdout, "Cache-Control: post-check=0, pre-check=0\r\n" );
	fprintf( stdout, "Pragma: no-cache\r\n");

	if ( !event )
	{
		Monitor *monitor = Monitor::Load( id );

		if ( monitor )
		{
			if ( mode == ZMS_JPEG )
			{
				monitor->StreamImages( idle, refresh, ttl, scale );
			}
			else
			{
#if HAVE_LIBAVCODEC
				 monitor->StreamMpeg( format, bitrate, scale, buffer );
#else // HAVE_LIBAVCODEC
				Error(( "MPEG streaming of '%s' attempted while disabled", query ));
				fprintf( stderr, "MPEG streaming is disabled.\nYou should configure with the --with-ffmpeg option and rebuild to use this functionality.\n" );
				return( -1 );
#endif // HAVE_LIBAVCODEC
			}
		}
	}
	else
	{
		if ( mode == ZMS_JPEG )
		{
			Event::StreamEvent( event, rate, scale );
		}
		else
		{
#if HAVE_LIBAVCODEC
			Event::StreamMpeg( event, format, bitrate, rate, scale );
#else // HAVE_LIBAVCODEC
			Error(( "MPEG streaming of '%s' attempted while disabled", query ));
			fprintf( stderr, "MPEG streaming is disabled.\nYou should configure with the --with-ffmpeg option and rebuild to use this functionality.\n" );
			return( -1 );
#endif // HAVE_LIBAVCODEC
		}
	}
	return( 0 );
}
