/*
 * ZoneMinder Monitor_Permission Class Interface
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



#ifndef ZM_MOnitor_PERMISSION_H
#define ZM_MOnitor_PERMISSION_H

#include "zm_db.h"
#include <vector>

class Monitor_Permission {
 public:
  typedef enum { PERM_UNKNOWN=0, PERM_INHERIT, PERM_NONE, PERM_VIEW, PERM_EDIT } Permission;

 protected:
  int id;
  int user_id;
  int monitor_id;
  Permission permission;

 public:
  Monitor_Permission();
  explicit Monitor_Permission(const MYSQL_ROW &dbrow);
  ~Monitor_Permission();
  Monitor_Permission(const Monitor_Permission &mp) { Copy(mp); }
  void Copy(const Monitor_Permission &mp);
  Monitor_Permission& operator=(const Monitor_Permission &mp) {
    Copy(mp);
    return *this;
  }

  int  Id() const { return id; }
  int  MonitorId() const { return monitor_id; }
  int  UserId() const { return user_id; }
  Permission getPermission() const { return permission; }

  static std::vector<Monitor_Permission> find(int p_user_id);
};

#endif // ZM_MOnitor_PERMISSION_H
