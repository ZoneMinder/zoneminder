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

Monitor_Permission::Monitor_Permission(const MYSQL_ROW &dbrow) {
  int index = 0;
  id = atoi(dbrow[index++]);
  user_id = atoi(dbrow[index++]);
  monitor_id = atoi(dbrow[index++]);
  permission = static_cast<Permission>(atoi(dbrow[index++]));
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
  std::string sql = stringtf("SELECT `Id`,`UserId`,`MonitorId`,`Permission`+0 FROM Monitors_Permissions WHERE `UserId`='%d'", p_user_id);

  MYSQL_RES *result = zmDbFetch(sql.c_str());

  if (result) {
    results.reserve(mysql_num_rows(result));
    while (MYSQL_ROW dbrow = mysql_fetch_row(result)) {
      results.push_back(Monitor_Permission(dbrow));
    }
    mysql_free_result(result);
  }
  return results;
}
