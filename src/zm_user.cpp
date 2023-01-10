/*
 * ZoneMinder user class implementation, $Date$, $Revision$
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

#include "zm_user.h"

#include "zm_crypt.h"
#include "zm_logger.h"
#include "zm_time.h"
#include "zm_utils.h"
#include <cstring>

User::User() : id(0), enabled(false) {
  username[0] = password[0] = 0;
  stream = events = control = monitors = system = PERM_NONE;
}

User::User(zmDbQuery &dbrow) {
  id = dbrow.get<long long>("Id");
  strncpy(username, dbrow.get<std::string>("Username").c_str(), sizeof(username)-1);
  strncpy(password, dbrow.get<std::string>("Password").c_str(), sizeof(password)-1);
  enabled = (dbrow.get<int>("Enabled") == 1);
  stream = dbrow.get<Permission>("Stream");
  events = dbrow.get<Permission>("Events");
  control = dbrow.get<Permission>("Control");
  monitors = dbrow.get<Permission>("Monitors");
  system = dbrow.get<Permission>("System");
}

User::~User() {
}

void User::Copy(const User &u) {
  id = u.id;
  strncpy(username, u.username, sizeof(username));
  strncpy(password, u.password, sizeof(password));
  enabled = u.enabled;
  stream = u.stream;
  events = u.events;
  control = u.control;
  monitors = u.monitors;
  system = u.system;
  monitor_permissions_loaded = u.monitor_permissions_loaded;
  monitor_permissions = u.monitor_permissions;
  group_permissions_loaded = u.monitor_permissions_loaded;
  group_permissions = u.group_permissions;
}

void User::loadMonitorPermissions() {
  for (const Monitor_Permission &p : Monitor_Permission::find(id) ) {
    monitor_permissions[p.MonitorId()] = p;
  }
  Debug(1, "# of Monitor_Permissions %zu", monitor_permissions.size());
}

void User::loadGroupPermissions() {
  group_permissions = Group_Permission::find(id);
  Debug(1, "# of Group_Permissions %zu", group_permissions.size());
}

bool User::canAccess(int monitor_id) {
  if (!monitor_permissions_loaded) loadMonitorPermissions();
  auto it = monitor_permissions.find(monitor_id);

  if (it != monitor_permissions.end()) {
    auto permission = it->second.getPermission();
    switch (permission) {
      case Monitor_Permission::PERM_NONE :
        Debug(1, "Returning None from monitor_permission");
        return false;
      case Monitor_Permission::PERM_VIEW :
        Debug(1, "Returning true because VIEW from monitor_permission");
        return true;
      case Monitor_Permission::PERM_EDIT :
        Debug(1, "Returning true because EDIT from monitor_permission");
        return true;
      case Monitor_Permission::PERM_INHERIT :
        Debug(1, "INHERIT from monitor_permission");
        break;
      default:
        Warning("UNKNOWN permission %d from monitor_permission", permission);
        break;
    }
  }

  if (!group_permissions_loaded) loadGroupPermissions();

  for (Group_Permission &gp : group_permissions) {
    auto permission = gp.getPermission(monitor_id);
    switch (permission) {
      case Group_Permission::PERM_NONE :
        Debug(1, "Returning None from group_permission");
        return false;
      case Group_Permission::PERM_VIEW :
        Debug(1, "Returning true because VIEW from group_permission");
        return true;
      case Group_Permission::PERM_EDIT :
        Debug(1, "Returning true because EDIT from group_permission");
        return true;
      case Group_Permission::PERM_INHERIT :
        Debug(1, "INHERIT from group_permission %d", gp.GroupId());
        break;
      default :
        Warning("UNKNOWN permission %d from group_permission %d", permission, gp.GroupId());
        break;
    }
  }  // end foreach Group_Permission

  return (monitors != PERM_NONE);
}

// Function to load a user from username and password
// Please note that in auth relay mode = none, password is NULL
User *zmLoadUser(const char *username, const char *password) {
  // the username was escaped here, however now with prepared statement it
  // is not necessary anymore
  zmDbQuery query = zmDbQuery( SELECT_USER_AND_DATA_WITH_USERNAME_ENABLED );
  query.bind<std::string>( "username", username );
  query.fetchOne();

  if ( query.affectedRows() == 1 ) {
    User *user = new User(query);

    if ( 
        (! password )  // relay type must be none
        ||
        verifyPassword(username, password, user->getPassword()) ) 
    {
      Info("Authenticated user '%s'", user->getUsername());
      return user;
    } 
  }  // end if 1 result from db

  Warning("Unable to authenticate user %s", username);
  return nullptr;
}  // end User *zmLoadUser(const char *username, const char *password)

User *zmLoadTokenUser(const std::string &jwt_token_str, bool use_remote_addr) {
  std::string key = config.auth_hash_secret;
  std::string remote_addr;

  if ( use_remote_addr ) {
    remote_addr = std::string(getenv("REMOTE_ADDR"));
    if (remote_addr == "") {
      Warning("Can't determine remote address, using null");
    } else {
      key += remote_addr;
    }
  }

  Debug(1, "Inside zmLoadTokenUser, formed key=%s", key.c_str());

  std::pair<std::string, unsigned int> ans = verifyToken(jwt_token_str, key);
  std::string username = ans.first;
  unsigned int iat = ans.second;
  Debug(1, "retrieved user '%s' from token", username.c_str());

  if ( username == "" ) {
    return nullptr;
  }

  zmDbQuery query = zmDbQuery( SELECT_USER_AND_DATA_PLUS_TOKEN_WITH_USERNAME_ENABLED );
  query.bind<std::string>( "username", username );
  query.fetchOne();

  if ( query.affectedRows() != 1 ) {
    Error("Unable to authenticate user '%s'", username.c_str());
    return nullptr;
  }

  User *user = new User(query);
  unsigned int stored_iat = strtoul(query.get<std::string>( "TokenMinExpiry" ).c_str(), nullptr, 0);

  if ( stored_iat > iat ) { // admin revoked tokens
    Error("Token was revoked for '%s'", username.c_str());
    return nullptr;
  }

  Debug(1, "Authenticated user '%s' via token with last revoke time: %u",
      username.c_str(), stored_iat);
  return user;
}  // User *zmLoadTokenUser(std::string jwt_token_str, bool use_remote_addr)
 
// Function to validate an authentication string
User *zmLoadAuthUser(const char *auth, bool use_remote_addr) {
  const char *remote_addr = "";
  if (use_remote_addr) {
    remote_addr = getenv("REMOTE_ADDR");
    if (!remote_addr) {
      Warning("Can't determine remote address, using null");
      remote_addr = "";
    }
  }

  Debug(1, "Attempting to authenticate user from auth string '%s', remote addr(%s)", auth, remote_addr);
  zmDbQuery query = zmDbQuery( SELECT_ALL_USERS_AND_DATA_ENABLED );;

  query.run( true );

  int n_users = query.affectedRows();
  if (n_users < 1) {
    Warning("Unable to authenticate user");
    return nullptr;
  }

  SystemTimePoint now = std::chrono::system_clock::now();
  Hours hours = Hours(config.auth_hash_ttl);

  if (hours == Hours(0)) {
    Warning("No value set for ZM_AUTH_HASH_TTL. Defaulting to 2.");
    hours = Hours(2);
  } else {
    Debug(1, "AUTH_HASH_TTL is %" PRIi64 " h, time is %" PRIi64 " s",
          static_cast<int64>(Hours(hours).count()),
          static_cast<int64>(std::chrono::duration_cast<Seconds>(now.time_since_epoch()).count()));
  }

  while ( query.next() ) {
    std::string username = query.get<std::string>("Username");
    std::string password = query.get<std::string>("Password");

    SystemTimePoint our_now = now;
    tm now_tm = {};
    for (Hours i = Hours(0); i < hours; i++, our_now -= Hours(1)) {
      time_t our_now_t = std::chrono::system_clock::to_time_t(our_now);
      localtime_r(&our_now_t, &now_tm);

      std::string auth_key = stringtf("%s%s%s%s%d%d%d%d",
                                      config.auth_hash_secret,
                                      username.c_str(),
                                      password.c_str(),
                                      remote_addr,
                                      now_tm.tm_hour,
                                      now_tm.tm_mday,
                                      now_tm.tm_mon,
                                      now_tm.tm_year);

      zm::crypto::MD5::Digest md5_digest = zm::crypto::MD5::GetDigestOf(auth_key);
      std::string auth_md5 = ByteArrayToHexString(md5_digest);

      Debug(1, "Checking auth_key '%s' -> auth_md5 '%s' == '%s'", auth_key.c_str(), auth_md5.c_str(), auth);

      if (!strcmp(auth, auth_md5.c_str())) {
        // We have a match
        User *user = new User(query);
        Debug(1, "Authenticated user '%s'", user->getUsername());
        return user;
      } else {
        Debug(1, "No match for %s", auth);
      }
    }  // end foreach hour
  }  // end foreach user

  Debug(1, "No user found for auth_key %s", auth);
  return nullptr;
}  // end User *zmLoadAuthUser( const char *auth, bool use_remote_addr )

// Function to check Username length
bool checkUser(const char *username) {
  if ( !username )
    return false;
  if ( strlen(username) > 32 )
    return false;

  return true;
}

// Function to check password length
bool checkPass(const char *password) {
  if ( !password )
    return false;
  if ( strlen(password) > 64 )
    return false;

  return true;
}
