/*
 * ZoneMinder Monitor Permission class implementation
 * Copyright (C) 2022 ZoneMinder Inc
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

#include "zm_monitor_permission.h"

#include "zm_db.h"
#include "zm_utils.h"

#include <cstring>

Monitor_Permission::Monitor_Permission() : id(0), user_id(0), monitor_id(0), permission(PERM_INHERIT) {
}

Monitor_Permission::Monitor_Permission(zmDbQuery &dbrow) {
  id = dbrow.get<int>("Id");
  user_id = dbrow.get<int>("UserId");
  monitor_id = dbrow.get<int>("MonitorId");
  permission = dbrow.get<Permission>("Permission");
}

Monitor_Permission::~Monitor_Permission() {
}

void Monitor_Permission::Copy(const Monitor_Permission &mp) {
  id = mp.id;
  user_id = mp.user_id;
  monitor_id = mp.monitor_id;
  permission = mp.permission;
}

std::vector<Monitor_Permission> Monitor_Permission::find(int p_user_id) {
  std::vector<Monitor_Permission> results;

  zmDbQuery query( SELECT_MONITOR_PERMISSIONS_FOR_USERID );
  query.bind<int>("id", p_user_id);
  query.run(true);

  if( query.affectedRows() == 0 )
    return results;

  while( query.next() ) {
    results.push_back(Monitor_Permission(query));
  }

  return results;
}
