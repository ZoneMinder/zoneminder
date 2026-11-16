/*
 * This file is part of the ZoneMinder Project. See AUTHORS file for Copyright information
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation; either version 2 of the License, or (at your
 * option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program. If not, see <http://www.gnu.org/licenses/>.
 */

#include "zm_catch2.h"

#include "zm_group.h"
#include "zm_group_permission.h"
#include "zm_user.h"

#include <cstring>
#include <string>

namespace {

class TestUser : public User {
 public:
  using User::monitor_permissions_loaded;
  using User::group_permissions_loaded;
  using User::role_monitor_permissions_loaded;
  using User::role_group_permissions_loaded;
};

}  // namespace

TEST_CASE("Group ParentId returns parent_id instead of group id") {
  const char *row[] = {"42", "7", "SubGroup"};
  Group group(const_cast<char **>(row));

  REQUIRE(group.Id() == 42);
  REQUIRE(group.ParentId() == 7);
  REQUIRE(group.Name() == "SubGroup");

  const char *root_row[] = {"10", nullptr, "RootGroup"};
  Group root_group(const_cast<char **>(root_row));

  REQUIRE(root_group.Id() == 10);
  REQUIRE(root_group.ParentId() == 0);
  REQUIRE(root_group.Name() == "RootGroup");
}

TEST_CASE("User default constructor initializes all loaded flags") {
  TestUser user;

  REQUIRE(user.Id() == 0);
  REQUIRE(user.isEnabled() == false);
  REQUIRE(user.getMonitors() == User::PERM_NONE);
  REQUIRE(user.monitor_permissions_loaded == false);
  REQUIRE(user.group_permissions_loaded == false);
  REQUIRE(user.role_monitor_permissions_loaded == false);
  REQUIRE(user.role_group_permissions_loaded == false);
}

TEST_CASE("User load permissions marks flags as loaded") {
  TestUser user;
  REQUIRE(user.monitor_permissions_loaded == false);
  REQUIRE(user.group_permissions_loaded == false);

  user.loadMonitorPermissions();
  REQUIRE(user.monitor_permissions_loaded == true);

  user.loadGroupPermissions();
  REQUIRE(user.group_permissions_loaded == true);
}

TEST_CASE("Group_Permission parsing from dbrow") {
  const char *row[] = {"5", "12", "3", "3"};  // id=5, user_id=12, group_id=3, perm=PERM_VIEW
  Group_Permission gp(const_cast<char **>(row));

  REQUIRE(gp.Id() == 5);
  REQUIRE(gp.UserId() == 12);
  REQUIRE(gp.GroupId() == 3);
  REQUIRE(gp.getPermission() == Group_Permission::PERM_VIEW);
}
