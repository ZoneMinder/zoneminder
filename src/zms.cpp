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

#include <openssl/md5.h>

#include "zm.h"
#include "zm_db.h"
#include "zm_monitor.h"

bool validateAuth( const char *user, char *auth, int id )
{
#ifdef HAVE_DECL_MD5
	if ( !(bool)config.Item( ZM_OPT_USE_AUTH ) )
	{
		return( true );
	}

	char **env_ptr = environ;
	const char *remote_addr = "";
	const char *remote_addr_str = "REMOTE_ADDR=";
	while ( *env_ptr )
	{
		if ( !strncasecmp( remote_addr_str, *env_ptr, strlen(remote_addr_str) ) )
		{
			remote_addr = strchr( *env_ptr, '=' )+1;
			break;
		}
		env_ptr++;
	}
	if ( !*remote_addr )
	{
		Warning(( "Can't determine remote address, using null" ));
	}

	char sql[BUFSIZ] = "";
	if ( id > 0 )
	{
		snprintf( sql, sizeof(sql), "select Username, Password from Users where Username = '%s' and Enabled = 1 and Stream = 'View' and ( MonitorIds = '' or find_in_set( '%d', MonitorIds ) )", user, id );
	}
	else
	{
		snprintf( sql, sizeof(sql), "select Username, Password from Users where Username = '%s' and Enabled = 1 and Events != 'None'", user );
	}

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
	int n_users = mysql_num_rows( result );

	if ( n_users < 1 )
	{
		Warning(( "Unable to authenticate user %s", user ));
		return( false );
	}

	MYSQL_ROW dbrow = mysql_fetch_row( result );

	char auth_key[512] = "";
	char auth_md5[32+1] = "";
	unsigned char md5sum[64] = "";

	time_t now = time( 0 );
	int max_tries = 2;

	for ( int i = 0; i < max_tries; i++, now -= (60*60) )
	{
		struct tm *now_tm = localtime( &now );

		snprintf( auth_key, sizeof(auth_key), "%s%s%s%s%d%d%d%d", 
			(const char *)config.Item( ZM_AUTH_SECRET ),
			user,
			dbrow[1],
			remote_addr,
			now_tm->tm_hour,
			now_tm->tm_mday,
			now_tm->tm_mon,
			now_tm->tm_year
		);

		MD5( (unsigned char *)auth_key, strlen(auth_key), md5sum );
		auth_md5[0] = '\0';
		for ( int j = 0; j < strlen((const char *)md5sum); j++ )
		{
			sprintf( auth_md5+strlen(auth_md5), "%02x", md5sum[j] );
		}

		if ( !strcmp( auth, auth_md5 ) )
		{
			// We have a match
			return( true );
		}
	}
	return( false );
#else // HAVE_DECL_MD5
	return( true );
#endif // HAVE_DECL_MD5
}

int main( int argc, const char *argv[] )
{
	enum { ZMS_JPEG, ZMS_MPEG, ZMS_SINGLE } mode = ZMS_JPEG;
	char format[32] = "";
	int id = 0;
	int event = 0;
	unsigned int scale = 100;
	unsigned int rate = 100;
	unsigned int maxfps = 10;
	unsigned int bitrate = 100000;
	unsigned int ttl = 0;
	char auth[64] = "";
	char user[64] = "";

	bool nph = false;
	const char *basename = strrchr( argv[0], '/' );
	const char *nph_prefix = "nph-";
	if ( basename && !strncmp( basename+1, nph_prefix, strlen(nph_prefix) ) )
	{
		nph = true;
	}
	
	zmDbgInit( "zms", "", 0 );

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
				mode = !strcmp( value, "single" )?ZMS_SINGLE:mode;
			}
			else if ( !strcmp( name, "monitor" ) )
				id = atoi( value );
			else if ( !strcmp( name, "event" ) )
				event = strtoull( value, (char **)NULL, 10 );
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
			else if ( (bool)config.Item( ZM_OPT_USE_AUTH ) )
			{
				if ( !strcmp( name, "auth" ) )
				{
					strncpy( auth, value, sizeof(auth) );
				}
				else if ( !strcmp( name, "user" ) )
				{
					strncpy( user, value, sizeof(user) );
				}
			}
		}
	}

	if ( !validateAuth( user, auth, id ) )
	{
		Error(( "Unable to validate authentication on '%s'", query ));
		fprintf( stderr, "Unable to validate authentication on '%s'\n", query );
		return( -1 );
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
			Event::StreamEvent( event, scale, rate, maxfps );
		}
		else
		{
#if HAVE_LIBAVCODEC
			Event::StreamMpeg( event, format, scale, rate, maxfps, bitrate );
#else // HAVE_LIBAVCODEC
			Error(( "MPEG streaming of '%s' attempted while disabled", query ));
			fprintf( stderr, "MPEG streaming is disabled.\nYou should configure with the --with-ffmpeg option and rebuild to use this functionality.\n" );
			return( -1 );
#endif // HAVE_LIBAVCODEC
		}
	}
	return( 0 );
}
