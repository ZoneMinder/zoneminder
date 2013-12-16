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

#include "zm_storage.h"

#include <string.h>

Storage::Storage() {
	id = name[0] = path[0] = 0;
}

Storage::Storage( MYSQL_ROW &dbrow ) {
	unsigned int index = 0;
	id = dbrow[index++];
	strncpy( name, dbrow[index++], sizeof(name) );
	strncpy( path, dbrow[index++], sizeof(path) );
}

Storage::Storage( unsigned int p_id ) {
    char sql[ZM_SQL_SML_BUFSIZ] = "";
	snprintf( sql, sizeof(sql), "SELECT Id, Name, Path from Storage WHERE Id=%d", p_id );
	if ( mysql_query( &dbconn, sql ) ) {
		Error( "Can't run query: %s", mysql_error( &dbconn ) );
		return;
	}
    MYSQL_RES *result = mysql_store_result( &dbconn );
    if ( !result ) {
        Error( "Can't use query result: %s", mysql_error( &dbconn ) );
		return;
    }
    int n_rows = mysql_num_rows( result );

    if ( n_rows != 1 ) {
        Warning( "Should not have returned more than 1 row for %d", p_id );
        return;
    }

    MYSQL_ROW dbrow = mysql_fetch_row( result );

    Storage *storage = new Storage( dbrow );
    Info( "Loaded Storage area '%s'", storage->getName() );

    mysql_free_result( result );
	return (storage);
}

Storage::~Storage() {
}
