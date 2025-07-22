/*
 * ZoneMinder User Class Interface, $Date$, $Revision$
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



#ifndef ZM_USER_H
#define ZM_USER_H

#include "zm_db.h"
#include "zm_group_permission.h"
#include "zm_monitor_permission.h"

#include <map>
#include <string>
#include <vector>

class User {
 public:
  typedef enum { PERM_NONE = 1, PERM_VIEW, PERM_EDIT } Permission;

 protected:
  int id;
  char username[32+1];
  char password[64+1];
  bool enabled;
  Permission stream;
  Permission events;
  Permission control;
  Permission monitors;
  Permission system;

  bool group_permissions_loaded;
  std::vector<Group_Permission> group_permissions;

  bool monitor_permissions_loaded;
  std::map<int, Monitor_Permission> monitor_permissions;

 public:
  User();
  explicit User(const MYSQL_ROW &dbrow);
  ~User();
  User(const User &u) { Copy(u); }
  void Copy(const User &u);
  User& operator=(const User &u) {
    Copy(u);
    return *this;
  }

  int  Id() const { return id; }
  const char *getUsername() const { return username; }
  const char *getPassword() const { return password; }
  bool isEnabled() const { return enabled; }
  Permission getStream() const { return stream; }
  Permission getEvents() const { return events; }
  Permission getControl() const { return control; }
  Permission getMonitors() const { return monitors; }
  Permission getSystem() const { return system; }
  bool canAccess(int monitor_id);

  void loadMonitorPermissions();
  void loadGroupPermissions();
};

User *zmLoadUser(const std::string&username, const std::string &password="");
User *zmLoadAuthUser(const std::string &auth, const std::string &user, bool use_remote_addr);
User *zmLoadTokenUser(const std::string &jwt, bool use_remote_addr);
bool checkUser(const std::string &username);
bool checkPass(const std::string &password);

#endif // ZM_USER_H
