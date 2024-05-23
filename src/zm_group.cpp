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

Group::Group() :
  id(0),
  parent_id(0),
  name("Default") {
  Warning("Instantiating default Group Object. Should not happen.");
}

// The order of columns is: Id, ParentId, Name
Group::Group(const MYSQL_ROW &dbrow) {
  unsigned int index = 0;
  id = atoi(dbrow[index++]);
  parent_id = dbrow[index] ? atoi(dbrow[index]): 0;
  index++;
  name = dbrow[index++];
}

Group::Group(unsigned int p_id) : id(0) {
  if (p_id) {
    std::string sql = stringtf("SELECT `Id`, `ParentId`, `Name` FROM `Groups` WHERE `Id`=%u", p_id);
    Debug(2, "Loading Group for %u using %s", p_id, sql.c_str());
    zmDbRow dbrow;
    if (!dbrow.fetch(sql)) {
      Error("Unable to load group for id %u: %s", p_id, mysql_error(&dbconn));
    } else {
      unsigned int index = 0;
      id = atoi(dbrow[index++]);
      parent_id = dbrow[index] ? atoi(dbrow[index]) : 0;
      index++;
      name = dbrow[index++];
      Debug(1, "Loaded Group area %d '%s'", id, name.c_str());
    }
  }
  if (!id) {
    Debug(1, "No id passed to Group constructor.");
    name = "Default";
  }
}

Group::~Group() {
}

std::vector<int> Group::MonitorIds() {
  std::vector<int> monitor_ids;
  if (!id) {
    Warning("Calling MoniotorIds on a group with no id");
    return monitor_ids;
  }

  std::string sql = stringtf("SELECT `MonitorId` FROM Groups_Monitors WHERE `GroupId`=%d", id);
  MYSQL_RES *result = zmDbFetch(sql.c_str());
  if (!result) {
    Error("Error loading MonitorIds from %s", sql.c_str());
    return monitor_ids;
  }

  monitor_ids.reserve(mysql_num_rows(result));
  while (MYSQL_ROW dbrow = mysql_fetch_row(result)) {
    monitor_ids.push_back(atoi(dbrow[0]));
  }
  mysql_free_result(result);

  sql = stringtf("SELECT `Id` FROM `Groups` WHERE `ParentId`=%d", id);
  result = zmDbFetch(sql.c_str());
  if (result) {
    while (MYSQL_ROW dbrow = mysql_fetch_row(result)) {
      Group child(atoi(dbrow[0]));

      std::vector<int> child_monitor_ids = child.MonitorIds();
      if (!child_monitor_ids.empty()) {
        monitor_ids.insert(
          monitor_ids.end(),
          std::make_move_iterator(child_monitor_ids.begin()),
          std::make_move_iterator(child_monitor_ids.end())
        );
      }
    }  // end foreach child
    mysql_free_result(result);
  } else {
    Error("Error loading Ids from %s", sql.c_str());
  }
  return monitor_ids;
}
