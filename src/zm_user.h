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

#include "soci/soci.h"

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
  explicit User(zmDbQuery &dbrow);
  ~User();
  User(const User &u) { Copy(u); }
  void Copy(const User &u);
  User& operator=(const User &u) {
    Copy(u); return *this;
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

User *zmLoadUser(const char *username, const char *password=0);
User *zmLoadAuthUser(const char *auth, bool use_remote_addr);
User *zmLoadTokenUser(const std::string &jwt, bool use_remote_addr);
bool checkUser(const char *username);
bool checkPass(const char *password);

namespace soci {
  // Database conversion specialization 
  // needed to be here because of issues with forward
  // declarations of various types, see zm_db_adapters.h

  template <> struct type_conversion<User::Permission>
  {
      typedef std::string base_type;
      static void from_base(const std::string & v, indicator & ind, User::Permission & p)
      {
          if (ind == i_null)
              throw soci_error("Null value not allowed for this type");

          if( v.compare("None") == 0 )
            p = User::Permission::PERM_NONE;
          else if( v.compare("View") == 0 )
            p = User::Permission::PERM_VIEW;
          else if( v.compare("Edit") == 0 )
            p = User::Permission::PERM_EDIT;
          else
            throw soci_error("Value not allowed for this type");
      }
      static void to_base(User::Permission & p, std::string & v, indicator & ind)
      {
          switch( p ) {
            case User::Permission::PERM_NONE:
              v = "None";
              ind = i_ok;
              return;
            case User::Permission::PERM_VIEW:
              v = "View";
              ind = i_ok;
              return;
            case User::Permission::PERM_EDIT:
              v = "Edit";
              ind = i_ok;
              return;
          }
          throw soci_error("Value not allowed for this type");
      }
  };

}

#endif // ZM_USER_H
