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
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/

#include "zm_group_permission.h"

#include "zm_db.h"
#include "zm_logger.h"
#include "zm_utils.h"
#include <cstring>

Group_Permission::Group_Permission() : id(0), group_id(0), user_id(0), permission(PERM_INHERIT), monitor_ids_loaded(false) {
}

Group_Permission::Group_Permission(zmDbQuery &dbrow) {
  id = dbrow.get<int>("Id");
  user_id = dbrow.get<int>("UserId");
  group_id = dbrow.get<int>("GroupId");
  permission = dbrow.get<Permission>("Permission");
  Debug(1, "Loaded permission %d from user %d group %d", permission, user_id, group_id);
  monitor_ids_loaded = false;
}

Group_Permission::~Group_Permission() {
}

void Group_Permission::Copy(const Group_Permission &gp) {
  id = gp.id;
  user_id = gp.user_id;
  group_id = gp.group_id;
  permission = gp.permission;
  monitor_ids = gp.monitor_ids;
}

Group_Permission::Permission Group_Permission::getPermission(int monitor_id) {
  if (!monitor_ids_loaded) {
    loadMonitorIds();
  }
  if (monitor_ids.empty()) return PERM_INHERIT;

  for (std::vector<int>::iterator i = monitor_ids.begin();
      i != monitor_ids.end(); ++i ) {
    if ( *i == monitor_id ) {
      return permission;
    }
  }
  return PERM_INHERIT;
}

std::vector<Group_Permission> Group_Permission::find(int p_user_id) {
  std::vector<Group_Permission> results;

  zmDbQuery query( SELECT_GROUP_PERMISSIONS_FOR_USERID );
  query.bind<int>("id", p_user_id);
  query.run(true);

  if( query.affectedRows() == 0 )
    return results;

  while( query.next() ) {
    results.push_back(Group_Permission(query));
  }
  return results;
}

void Group_Permission::loadMonitorIds() {
  zmDbQuery query( SELECT_MONITOR_FOR_GROUPID );
  query.bind<int>("id", group_id);
  query.run(true);

  while( query.next() ) {
    monitor_ids.push_back( query.get<int>("GroupId") );
  }
}  // end loadMonitorsIds()
