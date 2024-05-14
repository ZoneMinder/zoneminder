/*
 * ZoneMinder Group_Permission Class Interface
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



#ifndef ZM_GROUP_PERMISSION_H
#define ZM_GROUP_PERMISSION_H

#include "zm_db.h"
#include "zm_group.h"
#include <vector>

class Group_Permission {
 public:
  typedef enum { PERM_UNKNOWN=0, PERM_INHERIT, PERM_NONE, PERM_VIEW, PERM_EDIT } Permission;

 protected:
  int id;
  int group_id;
  int user_id;
  Permission permission;
  bool monitor_ids_loaded;
  std::vector<int> monitor_ids;

 public:
  Group_Permission();
  explicit Group_Permission(const MYSQL_ROW &dbrow);
  ~Group_Permission();
  Group_Permission(const Group_Permission &gp) { Copy(gp); }
  void Copy(const Group_Permission &u);
  Group_Permission& operator=(const Group_Permission &gp) {
    Copy(gp);
    return *this;
  }

  int  Id() const { return id; }
  int  GroupId() const { return group_id; }
  int  UserId() const { return user_id; }
  Permission getPermission() const { return permission; }
  Permission getPermission(int mid);
  void loadMonitorIds();

  static std::vector<Group_Permission> find(int p_user_id);
};

#endif // ZM_GROUP_PERMISSION_H
