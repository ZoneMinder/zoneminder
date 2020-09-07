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
#include <stdlib.h>
#include <stdio.h>

Storage::Storage() {
	Warning("Instantiating default Storage Object. Should not happen.");
	id = 0;
	strcpy(name, "Default");
  if ( staticConfig.DIR_EVENTS[0] != '/' ) {
    // not using an absolute path. Make it one by appending ZM_PATH_WEB
    snprintf( path, sizeof (path), "%s/%s", staticConfig.PATH_WEB.c_str( ), staticConfig.DIR_EVENTS.c_str() );
  } else {
    strncpy(path, staticConfig.DIR_EVENTS.c_str(), sizeof(path)-1 );
  }
  scheme = MEDIUM;
  scheme_str = "Medium";
}

Storage::Storage( MYSQL_ROW &dbrow ) {
	unsigned int index = 0;
	id = atoi( dbrow[index++] );
	strncpy( name, dbrow[index++], sizeof(name)-1 );
	strncpy( path, dbrow[index++], sizeof(path)-1 );
  type_str = std::string(dbrow[index++]);
  scheme_str = std::string(dbrow[index++]);
  if ( scheme_str == "Deep" ) {
    scheme = DEEP;
  } else if ( scheme_str == "Medium" ) {
    scheme = MEDIUM;
  } else {
    scheme = SHALLOW;
  }
}

/* If a zero or invalid p_id is passed, then the old default path will be assumed.  */
Storage::Storage( unsigned int p_id ) {
	id = 0;

	if ( p_id ) {
		char sql[ZM_SQL_SML_BUFSIZ];
		snprintf(sql, sizeof(sql), "SELECT `Id`, `Name`, `Path`, `Type`, `Scheme` FROM `Storage` WHERE `Id`=%d", p_id);
		Debug(2,"Loading Storage for %d using %s", p_id, sql );
		zmDbRow dbrow;
		if ( !dbrow.fetch(sql) ) {
			Error("Unable to load storage area for id %d: %s", p_id, mysql_error(&dbconn));
		} else {
			unsigned int index = 0;
			id = atoi(dbrow[index++]);
			strncpy(name, dbrow[index++], sizeof(name)-1);
			strncpy(path, dbrow[index++], sizeof(path)-1);
      type_str = std::string(dbrow[index++]);
      scheme_str = std::string(dbrow[index++]);
      if ( scheme_str == "Deep" ) {
        scheme = DEEP;
      } else if ( scheme_str == "Medium" ) {
        scheme = MEDIUM;
      } else {
        scheme = SHALLOW;
      }
			Debug(1, "Loaded Storage area %d '%s'", id, this->Name());
		}
	}
	if ( !id ) {
    if ( staticConfig.DIR_EVENTS[0] != '/' ) {
      // not using an absolute path. Make it one by appending ZM_PATH_WEB
      snprintf(path, sizeof (path), "%s/%s", staticConfig.PATH_WEB.c_str(), staticConfig.DIR_EVENTS.c_str());
    } else {
      strncpy(path, staticConfig.DIR_EVENTS.c_str(), sizeof(path)-1);
    }
		Debug(1,"No id passed to Storage constructor.  Using default path %s instead", path);
		strcpy(name, "Default");
    scheme = MEDIUM;
    scheme_str = "Medium";
	}
}

Storage::~Storage() {
}
