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
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
// 

#include <stdlib.h>
#include <string.h>

#include "zm.h"
#include "zm_db.h"

MYSQL dbconn;
RecursiveMutex db_mutex;

bool zmDbConnected = false;

bool zmDbConnect() {
  // For some reason having these lines causes memory corruption and crashing on newer debian/ubuntu
	// But they really need to be here in order to prevent a double open of mysql
  if ( zmDbConnected )  {
    //Warning("Calling zmDbConnect when already connected");
    return true;
  }

  if ( !mysql_init(&dbconn) ) {
    Error("Can't initialise database connection: %s", mysql_error(&dbconn));
    return false;
  }
  bool reconnect = 1;
  if ( mysql_options(&dbconn, MYSQL_OPT_RECONNECT, &reconnect) )
    Error("Can't set database auto reconnect option: %s", mysql_error(&dbconn));
  if ( !staticConfig.DB_SSL_CA_CERT.empty() )
    mysql_ssl_set(&dbconn,
        staticConfig.DB_SSL_CLIENT_KEY.c_str(),
        staticConfig.DB_SSL_CLIENT_CERT.c_str(),
        staticConfig.DB_SSL_CA_CERT.c_str(),
        NULL, NULL);
  std::string::size_type colonIndex = staticConfig.DB_HOST.find(":");
  if ( colonIndex == std::string::npos ) {
    if ( !mysql_real_connect(&dbconn, staticConfig.DB_HOST.c_str(), staticConfig.DB_USER.c_str(), staticConfig.DB_PASS.c_str(), NULL, 0, NULL, 0) ) {
      Error( "Can't connect to server: %s", mysql_error(&dbconn));
      return false;
    }
  } else {
    std::string dbHost = staticConfig.DB_HOST.substr( 0, colonIndex );
    std::string dbPortOrSocket = staticConfig.DB_HOST.substr( colonIndex+1 );
    if ( dbPortOrSocket[0] == '/' ) {
      if ( !mysql_real_connect(&dbconn, NULL, staticConfig.DB_USER.c_str(), staticConfig.DB_PASS.c_str(), NULL, 0, dbPortOrSocket.c_str(), 0) ) {
        Error("Can't connect to server: %s", mysql_error(&dbconn));
        return false;
      }
    } else {
      if ( !mysql_real_connect( &dbconn, dbHost.c_str(), staticConfig.DB_USER.c_str(), staticConfig.DB_PASS.c_str(), NULL, atoi(dbPortOrSocket.c_str()), NULL, 0 ) ) {
        Error( "Can't connect to server: %s", mysql_error( &dbconn ) );
        return false;
      }
    }
  }
  if ( mysql_select_db( &dbconn, staticConfig.DB_NAME.c_str() ) ) {
    Error( "Can't select database: %s", mysql_error( &dbconn ) );
    return false;
  }
  zmDbConnected = true;
  return zmDbConnected;
}

void zmDbClose() {
  if ( zmDbConnected ) {
    db_mutex.lock();
    mysql_close( &dbconn );
    // mysql_init() call implicitly mysql_library_init() but
    // mysql_close() does not call mysql_library_end()
    mysql_library_end();
    zmDbConnected = false;
    db_mutex.unlock();
  }
}

MYSQL_RES * zmDbFetch(const char * query) {
  if ( !zmDbConnected ) {
    Error("Not connected.");
    return NULL;
  }
  db_mutex.lock();

  if ( mysql_query(&dbconn, query) ) {
    db_mutex.unlock();
    Error("Can't run query: %s", mysql_error(&dbconn));
    return NULL;
  }
  Debug(4, "Success running query: %s", query);
  MYSQL_RES *result = mysql_store_result(&dbconn);
  if ( !result ) {
    Error("Can't use query result: %s for query %s", mysql_error(&dbconn), query);
  }
  db_mutex.unlock();
  return result;
} // end MYSQL_RES * zmDbFetch(const char * query);

zmDbRow *zmDbFetchOne(const char *query) {
  zmDbRow *row = new zmDbRow();
  if ( row->fetch(query) ) {
    return row;
  } 
  delete row;
  return NULL;
}

MYSQL_RES *zmDbRow::fetch(const char *query) {
  result_set = zmDbFetch(query);
  if ( ! result_set ) return result_set;

  int n_rows = mysql_num_rows(result_set);
  if ( n_rows != 1 ) {
    Error("Bogus number of lines return from query, %d returned for query %s.", n_rows, query);
    mysql_free_result(result_set);
    result_set = NULL;
    return result_set;
  }

  row = mysql_fetch_row(result_set);
  if ( ! row ) {
    mysql_free_result(result_set);
    result_set = NULL;
    Error("Error getting row from query %s. Error is %s", query, mysql_error(&dbconn));
  } else {
    Debug(5, "Success");
  }
  return result_set;
}

zmDbRow::~zmDbRow() {
  if ( result_set ) {
    mysql_free_result(result_set);
    result_set = NULL;
  }
}
