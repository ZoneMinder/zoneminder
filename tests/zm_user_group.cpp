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
#include <new>
#include <string>

namespace {

class TestUser : public User {
 public:
  using User::monitor_permissions_loaded;
  using User::group_permissions_loaded;
  using User::role_monitor_permissions_loaded;
  using User::role_group_permissions_loaded;
  using User::group_permissions;
  using User::monitor_permissions;
  using User::role_id;
  using User::role_group_permissions;
};

class TestGroupPermission : public Group_Permission {
 public:
  TestGroupPermission(Group_Permission::Permission p, int monitor_id) {
    id = 0;
    group_id = 1;
    user_id = 1;
    permission = p;
    monitor_ids = {monitor_id};
    monitor_ids_loaded = true;
  }
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

TEST_CASE("User keeps the whole Username column") {
  // Users.Username is latin1 (cp1252) varchar(64); over a utf8mb4 connection that is up to 3 bytes a character.
  std::string name;
  for (int i = 0; i < 64; i++) name += "\xe2\x82\xac";
  const char *row[] = {"3", name.c_str(), "$2y$10$hash", "1", "1", "1", "1", "1", "1", "0"};
  // Dirty storage, so a missing terminator shows up.
  alignas(User) unsigned char storage[sizeof(User)];
  memset(storage, 'x', sizeof(storage));
  User *user = new (storage) User(const_cast<char **>(row));

  REQUIRE(std::strlen(user->getUsername()) == name.size());
  REQUIRE(std::memcmp(user->getUsername(), name.data(), name.size()) == 0);
  user->~User();
}

TEST_CASE("Group_Permission parsing from dbrow") {
  const char *row[] = {"5", "12", "3", "3"};  // id=5, user_id=12, group_id=3, perm=PERM_VIEW
  Group_Permission gp(const_cast<char **>(row));

  REQUIRE(gp.Id() == 5);
  REQUIRE(gp.UserId() == 12);
  REQUIRE(gp.GroupId() == 3);
  REQUIRE(gp.getPermission() == Group_Permission::PERM_VIEW);
}

TEST_CASE("checkUser accepts every name the Username column holds") {
  // Users.Username is latin1 varchar(64); over a utf8mb4 connection that is up to 3 bytes a character.
  std::string euro;
  for (int i = 0; i < 64; i++) euro += "\xe2\x82\xac";

  REQUIRE(checkUser(std::string(33, 'a')));
  REQUIRE(checkUser(std::string(64, 'a')));
  REQUIRE(checkUser(euro));

  REQUIRE_FALSE(checkUser(""));
  REQUIRE_FALSE(checkUser(euro + "a"));
}

TEST_CASE("User canAccess denies when any group says None, whatever the order") {
  {
    TestUser user;
    user.monitor_permissions_loaded = true;
    user.group_permissions_loaded = true;
    user.role_id = 0;
    user.group_permissions.push_back(TestGroupPermission(Group_Permission::PERM_VIEW, 5));
    user.group_permissions.push_back(TestGroupPermission(Group_Permission::PERM_NONE, 5));

    REQUIRE_FALSE(user.canAccess(5));
  }

  {
    TestUser user;
    user.monitor_permissions_loaded = true;
    user.group_permissions_loaded = true;
    user.role_id = 0;
    user.group_permissions.push_back(TestGroupPermission(Group_Permission::PERM_NONE, 5));
    user.group_permissions.push_back(TestGroupPermission(Group_Permission::PERM_VIEW, 5));

    REQUIRE_FALSE(user.canAccess(5));
  }

  {
    TestUser user;
    user.monitor_permissions_loaded = true;
    user.group_permissions_loaded = true;
    user.role_id = 0;
    user.group_permissions.push_back(TestGroupPermission(Group_Permission::PERM_VIEW, 5));

    REQUIRE(user.canAccess(5));
  }

  {
    TestUser user;
    user.monitor_permissions_loaded = true;
    user.group_permissions_loaded = true;
    user.role_id = 0;
    user.group_permissions.push_back(TestGroupPermission(Group_Permission::PERM_NONE, 6));
    user.group_permissions.push_back(TestGroupPermission(Group_Permission::PERM_VIEW, 5));

    REQUIRE(user.canAccess(5));
  }

  {
    TestUser user;
    user.monitor_permissions_loaded = true;
    user.group_permissions_loaded = true;
    user.role_id = 1;
    user.role_monitor_permissions_loaded = true;
    user.role_group_permissions_loaded = true;
    user.group_permissions.clear();
    user.role_group_permissions.push_back(TestGroupPermission(Group_Permission::PERM_VIEW, 5));
    user.role_group_permissions.push_back(TestGroupPermission(Group_Permission::PERM_NONE, 5));

    REQUIRE_FALSE(user.canAccess(5));
  }
}
