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
  explicit Group_Permission(zmDbQuery &dbrow);
  ~Group_Permission();
  Group_Permission(const Group_Permission &gp) { Copy(gp); }
  void Copy(const Group_Permission &u);
  Group_Permission& operator=(const Group_Permission &gp) {
    Copy(gp); return *this;
  }

  int  Id() const { return id; }
  int  GroupId() const { return group_id; }
  int  UserId() const { return user_id; }
  Permission getPermission() const { return permission; }
  Permission getPermission(int mid);
  void loadMonitorIds();

  static std::vector<Group_Permission> find(int p_user_id);
};

namespace soci {
  template<> struct type_conversion<Group_Permission::Permission>
  {
      typedef std::string base_type;
      static void from_base(const std::string & v, indicator & ind, Group_Permission::Permission & p)
      {
          if (ind == i_null) {
            p = Group_Permission::Permission::PERM_UNKNOWN;
            return;
          }

          if( v.compare("Inherit") == 0 )
            p = Group_Permission::Permission::PERM_INHERIT;
          else if( v.compare("None") == 0 )
            p = Group_Permission::Permission::PERM_NONE;
          else if( v.compare("View") == 0 )
            p = Group_Permission::Permission::PERM_VIEW;
          else if( v.compare("Edit") == 0 )
            p = Group_Permission::Permission::PERM_EDIT;
          else
            p = Group_Permission::Permission::PERM_UNKNOWN;
      }
      static void to_base(Group_Permission::Permission & p, std::string & v, indicator & ind)
      {
          switch( p ) {
            case Group_Permission::Permission::PERM_INHERIT:
              v = "Inherit";
              ind = i_ok;
              return;
            case Group_Permission::Permission::PERM_NONE:
              v = "None";
              ind = i_ok;
              return;
            case Group_Permission::Permission::PERM_VIEW:
              v = "View";
              ind = i_ok;
              return;
            case Group_Permission::Permission::PERM_EDIT:
              v = "Edit";
              ind = i_ok;
              return;

            default:
            case Group_Permission::Permission::PERM_UNKNOWN:
              v = "Unknown";
              ind = i_ok;
              return;
          }
      }
  };
};

#endif // ZM_GROUP_PERMISSION_H
