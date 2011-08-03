/*
 * ZoneMinder regular expression class implementation, $Date$, $Revision$
 * Copyright (C) 2001-2008 Philip Coombes
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

#include "zm.h"
#include "zm_db.h"

#include "zm_user.h"

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <time.h>

User::User()
{
	username[0] = password[0] = 0;
	enabled = false;
	stream = events = control = monitors = system = PERM_NONE;
	monitor_ids = 0;
}

User::User( MYSQL_ROW &dbrow )
{
	int index = 0;
	strncpy( username, dbrow[index++], sizeof(username) );
	strncpy( password, dbrow[index++], sizeof(password) );
	enabled = (bool)atoi( dbrow[index++] );
	stream = (Permission)atoi( dbrow[index++] );
	events = (Permission)atoi( dbrow[index++] );
	control = (Permission)atoi( dbrow[index++] );
	monitors = (Permission)atoi( dbrow[index++] );
	system = (Permission)atoi( dbrow[index++] );
	monitor_ids = 0;
	char *monitor_ids_str = dbrow[index++];
	if ( monitor_ids_str && *monitor_ids_str )
	{
		monitor_ids = new int[strlen(monitor_ids_str)];
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
					break;
			}
			while ( !isdigit( *ptr ) )
				ptr++;
		} while( *ptr );
		monitor_ids[n_monitor_ids] = 0;
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
    char sql[ZM_SQL_SML_BUFSIZ] = "";

	if ( password )
	{
		snprintf( sql, sizeof(sql), "select Username, Password, Enabled, Stream+0, Events+0, Control+0, Monitors+0, System+0, MonitorIds from Users where Username = '%s' and Password = password('%s') and Enabled = 1", username, password );
	}
	else
	{
		snprintf( sql, sizeof(sql), "select Username, Password, Enabled, Stream+0, Events+0, Control+0, Monitors+0, System+0, MonitorIds from Users where Username = '%s' and Enabled = 1", username );
	}

	if ( mysql_query( &dbconn, sql ) )
	{
		Error( "Can't run query: %s", mysql_error( &dbconn ) );
		exit( mysql_errno( &dbconn ) );
	}

	MYSQL_RES *result = mysql_store_result( &dbconn );
	if ( !result )
	{
		Error( "Can't use query result: %s", mysql_error( &dbconn ) );
		exit( mysql_errno( &dbconn ) );
	}
	int n_users = mysql_num_rows( result );

	if ( n_users != 1 )
	{
		Warning( "Unable to authenticate user %s", username );
		return( 0 );
	}

	MYSQL_ROW dbrow = mysql_fetch_row( result );

	User *user = new User( dbrow );
	Info( "Authenticated user '%s'", user->getUsername() );

	mysql_free_result( result );

	return( user );
}

// Function to validate an authentication string
User *zmLoadAuthUser( const char *auth, bool use_remote_addr )
{
#if HAVE_DECL_MD5
#ifdef HAVE_GCRYPT_H
    // Special initialisation for libgcrypt
    if ( !gcry_check_version( GCRYPT_VERSION ) )
    {
        Fatal( "Unable to initialise libgcrypt" );
    }
    gcry_control( GCRYCTL_DISABLE_SECMEM, 0 );
    gcry_control( GCRYCTL_INITIALIZATION_FINISHED, 0 );
#endif // HAVE_GCRYPT_H

	const char *remote_addr = "";
	if ( use_remote_addr )
	{
		remote_addr = getenv( "REMOTE_ADDR" );
		if ( !remote_addr )
		{
			Warning( "Can't determine remote address, using null" );
			remote_addr = "";
		}
	}

	Debug( 1, "Attempting to authenticate user from auth string '%s'", auth );
    char sql[ZM_SQL_SML_BUFSIZ] = "";
	snprintf( sql, sizeof(sql), "select Username, Password, Enabled, Stream+0, Events+0, Control+0, Monitors+0, System+0, MonitorIds from Users where Enabled = 1" );

	if ( mysql_query( &dbconn, sql ) )
	{
		Error( "Can't run query: %s", mysql_error( &dbconn ) );
		exit( mysql_errno( &dbconn ) );
	}

	MYSQL_RES *result = mysql_store_result( &dbconn );
	if ( !result )
	{
		Error( "Can't use query result: %s", mysql_error( &dbconn ) );
		exit( mysql_errno( &dbconn ) );
	}
	int n_users = mysql_num_rows( result );

	if ( n_users < 1 )
	{
		Warning( "Unable to authenticate user" );
		return( 0 );
	}

	while( MYSQL_ROW dbrow = mysql_fetch_row( result ) )
	{
		const char *user = dbrow[0];
		const char *pass = dbrow[1];

		char auth_key[512] = "";
		char auth_md5[32+1] = "";
		unsigned char md5sum[MD5_DIGEST_LENGTH];

		time_t now = time( 0 );
		int max_tries = 2;

		for ( int i = 0; i < max_tries; i++, now -= (60*60) )
		{
			struct tm *now_tm = localtime( &now );

			snprintf( auth_key, sizeof(auth_key), "%s%s%s%s%d%d%d%d", 
				config.auth_hash_secret,
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
			for ( int j = 0; j < MD5_DIGEST_LENGTH; j++ )
			{
				sprintf( &auth_md5[2*j], "%02x", md5sum[j] );
			}
			Debug( 1, "Checking auth_key '%s' -> auth_md5 '%s'", auth_key, auth_md5 );

			if ( !strcmp( auth, auth_md5 ) )
			{
				// We have a match
				User *user = new User( dbrow );
				Info( "Authenticated user '%s'", user->getUsername() );
				return( user );
			}
		}
	}
#else // HAVE_DECL_MD5
	Error( "You need to build with gnutls or openssl installed to use hash based authentication" );
#endif // HAVE_DECL_MD5
	return( 0 );
}
