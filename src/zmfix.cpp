//
// ZoneMinder Video Device Fixer, $Date$, $Revision$
// Copyright (C) 2002  Philip Coombes
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
#include <errno.h>
#include <fcntl.h>
#include <limits.h>
#include <sys/stat.h>
#include <sys/types.h>
#include <mysql/mysql.h>

extern "C"
{
#include "zmcfg.h"
#include "zmdbg.h"
}

MYSQL dbconn;

int main( int argc, char *argv[] )
{
	char dbg_name_string[16] = "zmfix";
	dbg_name = dbg_name_string;

	DbgInit();

	if ( !mysql_init( &dbconn ) )
	{
		fprintf( stderr, "Can't initialise structure: %s\n", mysql_error( &dbconn ) );
		exit( mysql_errno( &dbconn ) );
	}
	if ( !mysql_connect( &dbconn, "", ZM_DB_USERA, ZM_DB_PASSA ) )
	{
		fprintf( stderr, "Can't connect to server: %s\n", mysql_error( &dbconn ) );
		exit( mysql_errno( &dbconn ) );
	}
	if ( mysql_select_db( &dbconn, ZM_DATABASE ) )
	{
		fprintf( stderr, "Can't select database: %s\n", mysql_error( &dbconn ) );
		exit( mysql_errno( &dbconn ) );
	}

	static char sql[256];
	sprintf( sql, "select Device from Monitors where Function != 'None'" );
	if ( mysql_query( &dbconn, sql ) )
	{
		Error(( "Can't run query: %s\n", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}

	MYSQL_RES *result = mysql_store_result( &dbconn );
	if ( !result )
	{
		Error(( "Can't use query result: %s\n", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}

	int n_devices = mysql_num_rows( result );
	int *devices = new int [n_devices];
	for( int i = 0; MYSQL_ROW dbrow = mysql_fetch_row( result ); i++ )
	{
		int device = atoi(dbrow[0]);
		char device_path[64];

    	sprintf( device_path, "/dev/video%d", device );

		struct stat stat_buf;

		if ( stat( device_path, &stat_buf ) < 0 )
		{
			Error(( "Can't stat %s: %s\n", device_path, strerror(errno)));
			exit( 1 );
		}

		uid_t uid = getuid();
		gid_t gid = getgid();

		mode_t mask = 0; 
		if ( uid == stat_buf.st_uid )
		{
			// If we are the owner
			mask = 00400;
		}
		else if ( gid == stat_buf.st_gid )
		{
			// If we are in the owner group
			mask = 00040;
		}
		else
		{
			// We are neither the owner nor in the group
			mask = 00004;
		}

		mode_t mode = stat_buf.st_mode;
		if ( mode & mask )
		{
			continue;
		}
		mode |= mask;

		if ( chmod( device_path, mode ) < 0 )
		{
			Error(( "Can't chmod %s to %o: %s\n", device_path, mode, strerror(errno)));
			exit( 1 );
		}
	}

	if ( mysql_errno( &dbconn ) )
	{
		Error(( "Can't fetch row: %s\n", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}
	// Yadda yadda
	mysql_free_result( result );
	return( 0 );
}
