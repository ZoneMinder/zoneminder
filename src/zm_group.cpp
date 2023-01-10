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

#include "zm_group.h"

#include "zm_logger.h"
#include "zm_utils.h"
#include <cstring>

Group::Group() {
	Warning("Instantiating default Group Object. Should not happen.");
	id = 0;
  parent_id = 0;
	strcpy(name, "Default");
}

// The order of columns is: Id, ParentId, Name
Group::Group(zmDbQuery &dbrow) {
	id = dbrow.get<unsigned int>( "Id" );
  parent_id = dbrow.has( "ParentId" ) ? dbrow.get<unsigned int>( "ParentId" ) : 0;

	strncpy(name, dbrow.get<std::string>( "Name" ).c_str(), sizeof(name)-1);
}

/* If a zero or invalid p_id is passed, then the old default path will be assumed.  */
Group::Group(unsigned int p_id) {
  id = 0;

  if (p_id) {
    Debug(2, "Loading Group for %u", p_id);
    zmDbQuery groupQuery = zmDbQuery( SELECT_GROUP_WITH_ID );
    groupQuery.bind("id", p_id);
    groupQuery.fetchOne();

    if (groupQuery.affectedRows() != 1) {
      Error("Unable to load group for id %u", p_id);

    } else {
      id = groupQuery.get<long long>("Id");
      parent_id = groupQuery.has("ParentId") ? groupQuery.get<long long>("ParentId") : 0;
      strncpy(name, groupQuery.get<std::string>("Name").c_str(), sizeof(name) - 1);
      Debug(1, "Loaded Group area %d '%s'", id, this->Name());
    }
  }
  if (!id) {
    Debug(1, "No id passed to Group constructor.");
    strcpy(name, "Default");
  }
}

Group::~Group() {
}
