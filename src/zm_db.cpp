//
// ZoneMinder MySQL Implementation, $Date$, $Revision$
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

#include <stdlib.h>
#include <string.h>

#include "zm.h"
#include "zm_db.h"

MYSQL dbconn;

int zmDbConnected = false;

void zmDbConnect()
{
  if ( !mysql_init( &dbconn ) )
  {
    Error( "Can't initialise database connection: %s", mysql_error( &dbconn ) );
    exit( mysql_errno( &dbconn ) );
  }
  my_bool reconnect = 1;
  if ( mysql_options( &dbconn, MYSQL_OPT_RECONNECT, &reconnect ) )
    Fatal( "Can't set database auto reconnect option: %s", mysql_error( &dbconn ) );
  std::string::size_type colonIndex = staticConfig.DB_HOST.find( ":/" );
  if ( colonIndex != std::string::npos )
  {
    std::string dbHost = staticConfig.DB_HOST.substr( 0, colonIndex );
    std::string dbPort = staticConfig.DB_HOST.substr( colonIndex+1 );
    if ( !mysql_real_connect( &dbconn, dbHost.c_str(), staticConfig.DB_USER.c_str(), staticConfig.DB_PASS.c_str(), 0, atoi(dbPort.c_str()), 0, 0 ) )
    {
      Error( "Can't connect to server: %s", mysql_error( &dbconn ) );
      exit( mysql_errno( &dbconn ) );
    }
  }
  else
  {
    if ( !mysql_real_connect( &dbconn, staticConfig.DB_HOST.c_str(), staticConfig.DB_USER.c_str(), staticConfig.DB_PASS.c_str(), 0, 0, 0, 0 ) )
    {
      Error( "Can't connect to server: %s", mysql_error( &dbconn ) );
      exit( mysql_errno( &dbconn ) );
    }
  }
  if ( mysql_select_db( &dbconn, staticConfig.DB_NAME.c_str() ) )
  {
    Error( "Can't select database: %s", mysql_error( &dbconn ) );
    exit( mysql_errno( &dbconn ) );
  }
  zmDbConnected = true;
}

void zmDbClose()
{
  if ( zmDbConnected )
  {
    mysql_close( &dbconn );
    // mysql_init() call implicitly mysql_library_init() but
    // mysql_close() does not call mysql_library_end()
    mysql_library_end();
    zmDbConnected = false;
  }
}

MYSQL_RES * zmDbFetch( const char * query ) {
  if ( ! zmDbConnected ) {
    Error( "Not connected." );
    return NULL;
  }

  if ( mysql_query( &dbconn, query ) ) {
    Error( "Can't run query: %s", mysql_error( &dbconn ) );
    return NULL;
  }
  Debug( 4, "Success running query: %s", query );
  MYSQL_RES *result = mysql_store_result( &dbconn );
  if ( !result ) {
    Error( "Can't use query result: %s for query %s", mysql_error( &dbconn ), query );
    return NULL;
  }
  return result;
} // end MYSQL_RES * zmDbFetch( const char * query );

MYSQL_ROW zmDbFetchOne( const char *query ) {
  MYSQL_RES *result = zmDbFetch( query );
  int n_rows = mysql_num_rows( result );
  if ( n_rows != 1 ) {
    Error( "Bogus number of lines return from query, %d returned for query %s.", n_rows, query );
    return NULL;
  }

  MYSQL_ROW dbrow = mysql_fetch_row( result );
  mysql_free_result( result );
  if ( ! dbrow ) {
    Error("Error getting row from query %s. Error is %s", query, mysql_error( &dbconn ) );
    return NULL;
  }
  return dbrow;
}
