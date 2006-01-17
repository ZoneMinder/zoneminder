//
// ZoneMinder Streaming Server, $Date$, $Revision$
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

#include "zm.h"
#include "zm_db.h"
#include "zm_user.h"
#include "zm_monitor.h"

bool ValidateAccess( User *user, int mon_id )
{
	bool allowed = true;

	if ( mon_id > 0 )
	{
		if ( user->getStream() < User::PERM_VIEW )
			allowed = false;
		if ( !user->canAccess( mon_id ) )
			allowed = false;
	}
	else
	{
		if ( user->getEvents() < User::PERM_VIEW )
			allowed = false;
	}
	if ( !allowed )
	{
		Error(( "Error, insufficient privileges for requested action" ));
		exit( -1 );
	}
	return( allowed );
}

int main( int argc, const char *argv[] )
{
	enum { ZMS_JPEG, ZMS_MPEG, ZMS_RAW, ZMS_SINGLE } mode = ZMS_JPEG;
	char format[32] = "";
	int id = 0;
	int event = 0;
	int frame = 1;
	unsigned int scale = 100;
	unsigned int rate = 100;
	unsigned int maxfps = 10;
	unsigned int bitrate = 100000;
	unsigned int ttl = 0;
	char username[64] = "";
	char password[64] = "";
	char auth[64] = "";

	bool nph = false;
	const char *basename = strrchr( argv[0], '/' );
	const char *nph_prefix = "nph-";
	if ( basename && !strncmp( basename+1, nph_prefix, strlen(nph_prefix) ) )
	{
		nph = true;
	}
	
	zmDbgInit( "zms", "", -1 );

	zmLoadConfig();

	const char *query = getenv( "QUERY_STRING" );
	if ( query )
	{
		Debug( 1, ( "Query: %s", query ));
	
		char temp_query[1024];
		strncpy( temp_query, query, sizeof(temp_query) );
		char *q_ptr = temp_query;
		char *parms[16]; // Shouldn't be more than this
		int parm_no = 0;
		while( (parm_no < 16) && (parms[parm_no] = strtok( q_ptr, "&" )) )
		{
			parm_no++;
			q_ptr = NULL;
		}
	
		for ( int p = 0; p < parm_no; p++ )
		{
			char *name = strtok( parms[p], "=" );
			char *value = strtok( NULL, "=" );
			if ( !strcmp( name, "mode" ) )
			{
				mode = !strcmp( value, "jpeg" )?ZMS_JPEG:ZMS_MPEG;
				mode = !strcmp( value, "raw" )?ZMS_RAW:mode;
				mode = !strcmp( value, "single" )?ZMS_SINGLE:mode;
			}
			else if ( !strcmp( name, "monitor" ) )
				id = atoi( value );
			else if ( !strcmp( name, "event" ) )
				event = strtoull( value, (char **)NULL, 10 );
			else if ( !strcmp( name, "frame" ) )
				frame = strtoull( value, (char **)NULL, 10 );
			else if ( !strcmp( name, "format" ) )
				strncpy( format, value, sizeof(format) );
			else if ( !strcmp( name, "scale" ) )
				scale = atoi( value );
			else if ( !strcmp( name, "rate" ) )
				rate = atoi( value );
			else if ( !strcmp( name, "maxfps" ) )
				maxfps = atoi( value );
			else if ( !strcmp( name, "bitrate" ) )
				bitrate = atoi( value );
			else if ( !strcmp( name, "ttl" ) )
				ttl = atoi(value);
			else if ( config.opt_use_auth )
			{
				if ( strcmp( config.auth_relay, "none" ) == 0 )
				{
					if ( !strcmp( name, "user" ) )
					{
						strncpy( username, value, sizeof(username) );
					}
				}
				else
				{
					//if ( strcmp( config.auth_relay, "hashed" ) == 0 )
					{
						if ( !strcmp( name, "auth" ) )
						{
							strncpy( auth, value, sizeof(auth) );
						}
					}
					//else if ( strcmp( config.auth_relay, "plain" ) == 0 )
					{
						if ( !strcmp( name, "user" ) )
						{
							strncpy( username, value, sizeof(username) );
						}
						if ( !strcmp( name, "pass" ) )
						{
							strncpy( password, value, sizeof(password) );
						}
					}
				}
			}
		}
	}

	if ( config.opt_use_auth )
	{
		User *user = 0;

		if ( strcmp( config.auth_relay, "none" ) == 0 )
		{
			if ( *username )
			{
				user = zmLoadUser( username );
			}
		}
		else
		{
			//if ( strcmp( config.auth_relay, "hashed" ) == 0 )
			{
				if ( *auth )
				{
					user = zmLoadAuthUser( auth, config.auth_hash_ips );
				}
			}
			//else if ( strcmp( config.auth_relay, "plain" ) == 0 )
			{
				if ( *username && *password )
				{
					user = zmLoadUser( username, password );
				}
			}
		}
		if ( !user )
		{
			Error(( "Unable to authenticate user" ));
			return( -1 );
		}
		ValidateAccess( user, id );
	}

	setbuf( stdout, 0 );
	if ( nph )
	{
		fprintf( stdout, "HTTP/1.0 200 OK\r\n" );
	}
	fprintf( stdout, "Server: ZoneMinder Video Server/%s\r\n", ZM_VERSION );
		        
	time_t now = time( 0 );
	char date_string[64];
	strftime( date_string, sizeof(date_string)-1, "%a, %d %b %Y %H:%M:%S GMT", gmtime( &now ) );

	fprintf( stdout, "Expires: Mon, 26 Jul 1997 05:00:00 GMT\r\n" );
	fprintf( stdout, "Last-Modified: %s\r\n", date_string );
	fprintf( stdout, "Cache-Control: no-store, no-cache, must-revalidate\r\n" );
	fprintf( stdout, "Cache-Control: post-check=0, pre-check=0\r\n" );
	fprintf( stdout, "Pragma: no-cache\r\n");
	// Removed as causing more problems than it fixed.
	//if ( !nph )
	//{
		//fprintf( stdout, "Content-Length: 0\r\n");
	//}

	if ( !event )
	{
		Monitor *monitor = Monitor::Load( id );

		if ( monitor )
		{
			if ( mode == ZMS_JPEG )
			{
				monitor->StreamImages( scale, maxfps, ttl );
			}
			else if ( mode == ZMS_RAW )
			{
				monitor->StreamImagesRaw( scale, maxfps, ttl );
			}
			else if ( mode == ZMS_SINGLE )
			{
				monitor->SingleImage( scale );
			} 
			else
			{
#if HAVE_LIBAVCODEC
				 monitor->StreamMpeg( format, scale, maxfps, bitrate );
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
			Event::StreamEvent( event, frame, scale, rate, maxfps );
		}
		else
		{
#if HAVE_LIBAVCODEC
			Event::StreamMpeg( event, frame, format, scale, rate, maxfps, bitrate );
#else // HAVE_LIBAVCODEC
			Error(( "MPEG streaming of '%s' attempted while disabled", query ));
			fprintf( stderr, "MPEG streaming is disabled.\nYou should configure with the --with-ffmpeg option and rebuild to use this functionality.\n" );
			return( -1 );
#endif // HAVE_LIBAVCODEC
		}
	}
	return( 0 );
}
