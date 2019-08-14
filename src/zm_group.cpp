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

#include "zm_group.h"

#include <string.h>
#include <stdlib.h>

Group::Group() {
	Warning("Instantiating default Group Object. Should not happen.");
	id = 0;
  parent_id = 0;
	strcpy(name, "Default");
}

// The order of columns is: Id, ParentId, Name
Group::Group(MYSQL_ROW &dbrow) {
	unsigned int index = 0;
	id = atoi(dbrow[index++]);
	parent_id = dbrow[index] ? atoi(dbrow[index]): 0; index++;
	strncpy(name, dbrow[index++], sizeof(name)-1);
}

/* If a zero or invalid p_id is passed, then the old default path will be assumed.  */
Group::Group(unsigned int p_id) {
	id = 0;

	if ( p_id ) {
		char sql[ZM_SQL_SML_BUFSIZ];
		snprintf(sql, sizeof(sql), "SELECT `Id`, `ParentId`, `Name` FROM `Group` WHERE `Id`=%d", p_id);
		Debug(2,"Loading Group for %d using %s", p_id, sql);
		zmDbRow dbrow;
		if ( !dbrow.fetch(sql) ) {
			Error("Unable to load group for id %d: %s", p_id, mysql_error(&dbconn));
		} else {
			unsigned int index = 0;
			id = atoi(dbrow[index++]);
      parent_id = dbrow[index] ? atoi(dbrow[index]): 0; index++;
			strncpy(name, dbrow[index++], sizeof(name)-1);
			Debug(1, "Loaded Group area %d '%s'", id, this->Name());
		}
	}
	if ( ! id ) {
		Debug(1,"No id passed to Group constructor.");
		strcpy(name, "Default");
	}
}

Group::~Group() {
}
