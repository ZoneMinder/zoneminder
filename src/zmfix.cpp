//
// ZoneMinder Video Device Fixer, $Date$, $Revision$
// Copyright (C) 2001-2008 Philip Coombes
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

#include "zm.h"
#include "zm_db.h"

// Determine if we are a member of the group
int inGroup( gid_t gid )
{
    // Get how many groups we are in
    int n_gids = getgroups( 0, NULL );
    if ( n_gids < 0 )
    {
        Error( "getgroups:%s", strerror(errno) );
        return( -1 );
    }

    // Not in any groups
    if ( !n_gids )
    {
        return( 0 );
    }

    // Allocate space to hold groups
    gid_t *gids = new gid_t[n_gids * sizeof(gid_t)];
    if ( !gids )
    {
        Error( "Unable to allocate groups: %s", strerror(errno) );
        return( -1 );
    }

    // Get list of groups
    if ( getgroups( n_gids, gids ) != n_gids )
    {
        Error( "getgroups:%s", strerror(errno) );
        delete[] gids;
        return( -1 );
    }

    // See if gid in list of groups we belong to
    int in_gid = 0;
    for ( int i = 0; i < n_gids; i++ )
    {
        if ( gids[i] == gid )
        {
            in_gid = 1;
        }
    }
    delete[] gids;
    return( in_gid );
}
 
bool fixDevice( const char *device_path )
{
    struct stat stat_buf;

    if ( stat( device_path, &stat_buf ) < 0 )
    {
        Error( "Can't stat %s: %s", device_path, strerror(errno));
        return( false );
    }

    uid_t uid = getuid();
    gid_t gid = getgid();

    int in_gid;
    if ( (in_gid = inGroup( stat_buf.st_gid )) < 0 )
    {
        return( false );
    }
 
    mode_t mask = 0; 
    if ( uid == stat_buf.st_uid )
    {
        // If we are the owner
        mask = 00600;
    }
    else if ( gid == stat_buf.st_gid || in_gid )
    {
        // If we are in the owner group
        mask = 00060;
    }
    else
    {
        // We are neither the owner nor in the group
        mask = 00006;
    }

    mode_t mode = stat_buf.st_mode;
    if ( (mode & mask) == mask )
    {
        Debug( 1, "Permissions on %s are ok at %o", device_path, mode );
        return( true );
    }
    mode |= mask;

    Info( "Resetting permissions on %s to %o", device_path, mode );
    if ( chmod( device_path, mode ) < 0 )
    {
        Error( "Can't chmod %s to %o: %s", device_path, mode, strerror(errno));
        return( false );
    }
    return( true );
}

int main( int argc, char *argv[] )
{
    zmDbgInit( "zmfix", "", -1 );

    zmLoadConfig();

    // Only do registered devices
    static char sql[BUFSIZ];
    snprintf( sql, sizeof(sql), "select distinct Device from Monitors where not isnull(Device) and Type = 'Local'" );
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

    for( int i = 0; MYSQL_ROW dbrow = mysql_fetch_row( result ); i++ )
    {
        fixDevice( dbrow[0] );
    }

    if ( mysql_errno( &dbconn ) )
    {
        Error( "Can't fetch row: %s", mysql_error( &dbconn ) );
        exit( mysql_errno( &dbconn ) );
    }
    // Yadda yadda
    mysql_free_result( result );

    snprintf( sql, sizeof(sql), "select distinct ControlDevice from Monitors where not isnull(ControlDevice)" );
    if ( mysql_query( &dbconn, sql ) )
    {
        Error( "Can't run query: %s", mysql_error( &dbconn ) );
        exit( mysql_errno( &dbconn ) );
    }

    result = mysql_store_result( &dbconn );
    if ( !result )
    {
        Error( "Can't use query result: %s", mysql_error( &dbconn ) );
        exit( mysql_errno( &dbconn ) );
    }

    for( int i = 0; MYSQL_ROW dbrow = mysql_fetch_row( result ); i++ )
    {
        fixDevice( dbrow[0] );
    }

    if ( mysql_errno( &dbconn ) )
    {
        Error( "Can't fetch row: %s", mysql_error( &dbconn ) );
        exit( mysql_errno( &dbconn ) );
    }
    // Yadda yadda
    mysql_free_result( result );

    if ( config.opt_x10 )
    {
        if ( config.x10_device )
        {
            fixDevice( config.x10_device );
        }
    }

    return( 0 );
}
