/*
 * ZoneMinder regular expression class implementation, $Date$, $Revision$
 * Copyright (C) 2003  Philip Coombes
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/ 

#include <string.h>
#include <stdlib.h>
#include <time.h>
#include <ctype.h>
#include <openssl/md5.h>

#include "zm.h"
#include "zm_db.h"

#include "zm_user.h"

User::User()
{
	username[0] = password[0] = 0;
	enabled = false;
	stream = events = monitors = system = PERM_NONE;
	monitor_ids = 0;
}

User::User( MYSQL_ROW &dbrow )
{
	strncpy( username, dbrow[0], sizeof(username) );
	strncpy( password, dbrow[1], sizeof(password) );
	enabled = (bool)atoi( dbrow[2] );
	stream = (Permission)atoi( dbrow[3] );
	events = (Permission)atoi( dbrow[4] );
	monitors = (Permission)atoi( dbrow[5] );
	system = (Permission)atoi( dbrow[6] );
	char *monitor_ids_str = dbrow[7];
	if ( monitor_ids_str && *monitor_ids_str )
	{
		int *monitor_ids = new int[strlen(monitor_ids_str)];
		int n_monitor_ids = 0;
		const char *ptr = monitor_ids_str;
		do
		{
			int id = 0;
			while( isdigit( *ptr ) )
			{
				id *= 10;
				id += *ptr-'0';
				ptr++;
			}
			if ( id )
			{
				monitor_ids[n_monitor_ids++] = id;
				if ( !*ptr )
				{
					break;
				}
			}
			while ( !isdigit( *ptr ) )
			{
				ptr++;
			}
		} while( *ptr );
		monitor_ids[n_monitor_ids] = 0;
	}
	else
	{
		monitor_ids = 0;
	}
}

User::~User()
{
	delete monitor_ids;
}

bool User::canAccess( int monitor_id )
{
	if ( !monitor_ids )
	{
		return( true );
	}
	for ( int i = 0; monitor_ids[i]; i++ )
	{
		if ( monitor_ids[i] == monitor_id )
		{
			return( true );
		}
	}
	return( false );
}

// Function to load a user from username and password
User *zmLoadUser( const char *username, const char *password )
{
	char sql[BUFSIZ] = "";
	snprintf( sql, sizeof(sql), "select Username, Password, Stream+0, Events+0, Monitors+0, System+0, MonitorIds from Users where Username = '%s' and Password = password('%s') and Enabled = 1", username, password );

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

	if ( n_users != 1 )
	{
		Warning(( "Unable to authenticate user %s", username ));
		return( 0 );
	}

	MYSQL_ROW dbrow = mysql_fetch_row( result );

	User *user = new User( dbrow );
	Info(( "Authenticated user '%s'", user->getUsername() ));

	return( user );
}

// Function to validate an authentication string
User *zmLoadAuthUser( const char *auth, bool use_remote_addr )
{
	const char *remote_addr = "";
	if ( use_remote_addr )
	{
		remote_addr = getenv( "REMOTE_ADDR" );
		if ( !remote_addr )
		{
			Warning(( "Can't determine remote address, using null" ));
			remote_addr = "";
		}
	}

	char sql[BUFSIZ] = "";
	snprintf( sql, sizeof(sql), "select Username, Password, Stream+0, Events+0, Monitors+0, System+0, MonitorIds from Users where Enabled = 1" );

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
		Warning(( "Unable to authenticate user" ));
		return( 0 );
	}

	while( MYSQL_ROW dbrow = mysql_fetch_row( result ) )
	{
		const char *user = dbrow[0];
		const char *pass = dbrow[1];

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
				pass,
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
				User *user = new User( dbrow );
				Info(( "Authenticated user '%s'", user->getUsername() ));
				return( user );
			}
		}
	}
	return( 0 );
}
