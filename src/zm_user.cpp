/*
 * ZoneMinder regular expression class implementation, $Date$, $Revision$
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

User::User() {
  id = 0;
  username[0] = password[0] = 0;
  enabled = false;
  stream = events = control = monitors = system = PERM_NONE;
}

User::User(const MYSQL_ROW &dbrow) {
  int index = 0;
  id = atoi(dbrow[index++]);
  strncpy(username, dbrow[index++], sizeof(username)-1);
  strncpy(password, dbrow[index++], sizeof(password)-1);
  enabled = static_cast<bool>(atoi(dbrow[index++]));
  stream = (Permission)atoi(dbrow[index++]);
  events = (Permission)atoi(dbrow[index++]);
  control = (Permission)atoi(dbrow[index++]);
  monitors = (Permission)atoi(dbrow[index++]);
  system = (Permission)atoi(dbrow[index++]);
  char *monitor_ids_str = dbrow[index++];
  if ( monitor_ids_str && *monitor_ids_str ) {
    StringVector ids = Split(monitor_ids_str, ",");
    for ( StringVector::iterator i = ids.begin(); i < ids.end(); ++i ) {
      monitor_ids.push_back(atoi((*i).c_str()));
    }
  }
}

User::~User() {
  monitor_ids.clear();
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
  monitor_ids = u.monitor_ids;
}

bool User::canAccess(int monitor_id) {
  if ( monitor_ids.empty() )
    return true;

  for ( std::vector<int>::iterator i = monitor_ids.begin();
      i != monitor_ids.end(); ++i ) {
    if ( *i == monitor_id ) {
      return true;
    }
  }
  return false;
}

// Function to load a user from username and password
// Please note that in auth relay mode = none, password is NULL
User *zmLoadUser(const char *username, const char *password) {
  std::string escaped_username = zmDbEscapeString(username);

  std::string sql = stringtf("SELECT `Id`, `Username`, `Password`, `Enabled`,"
                             " `Stream`+0, `Events`+0, `Control`+0, `Monitors`+0, `System`+0,"
                             " `MonitorIds`"
                             " FROM `Users` WHERE `Username` = '%s' AND `Enabled` = 1",
                             escaped_username.c_str());

  MYSQL_RES *result = zmDbFetch(sql);
  if (!result)
    return nullptr;

  if ( mysql_num_rows(result) == 1 ) {
    MYSQL_ROW dbrow = mysql_fetch_row(result);
    User *user = new User(dbrow);

    if ( 
        (! password )  // relay type must be none
        ||
        verifyPassword(username, password, user->getPassword()) ) {
      mysql_free_result(result);
      Info("Authenticated user '%s'", user->getUsername());
      return user;
    } 
  }  // end if 1 result from db
  mysql_free_result(result);

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

  std::string sql = stringtf("SELECT `Id`, `Username`, `Password`, `Enabled`, `Stream`+0, `Events`+0,"
                             " `Control`+0, `Monitors`+0, `System`+0, `MonitorIds`, `TokenMinExpiry`"
                             " FROM `Users` WHERE `Username` = '%s' AND `Enabled` = 1", username.c_str());

  MYSQL_RES *result = zmDbFetch(sql);
  if (!result)
    return nullptr;

  int n_users = mysql_num_rows(result);
  if ( n_users != 1 ) {
    mysql_free_result(result);
    Error("Unable to authenticate user '%s'", username.c_str());
    return nullptr;
  }

  MYSQL_ROW dbrow = mysql_fetch_row(result);
  User *user = new User(dbrow);
  unsigned int stored_iat = strtoul(dbrow[10], nullptr, 0);

  if ( stored_iat > iat ) { // admin revoked tokens
    mysql_free_result(result);
    Error("Token was revoked for '%s'", username.c_str());
    return nullptr;
  }

  Debug(1, "Authenticated user '%s' via token with last revoke time: %u",
      username.c_str(), stored_iat);
  mysql_free_result(result);
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
  std::string sql = "SELECT `Id`, `Username`, `Password`, `Enabled`,"
                    " `Stream`+0, `Events`+0, `Control`+0, `Monitors`+0, `System`+0,"
                    " `MonitorIds` FROM `Users` WHERE `Enabled` = 1";

  MYSQL_RES *result = zmDbFetch(sql);
  if (!result)
    return nullptr;

  int n_users = mysql_num_rows(result);
  if (n_users < 1) {
    mysql_free_result(result);
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

  while (MYSQL_ROW dbrow = mysql_fetch_row(result)) {
    const char *username = dbrow[1];
    const char *password = dbrow[2];

    SystemTimePoint our_now = now;
    tm now_tm = {};
    for (Hours i = Hours(0); i < hours; i++, our_now -= Hours(1)) {
      time_t our_now_t = std::chrono::system_clock::to_time_t(our_now);
      localtime_r(&our_now_t, &now_tm);

      std::string auth_key = stringtf("%s%s%s%s%d%d%d%d",
                                      config.auth_hash_secret,
                                      username,
                                      password,
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
        User *user = new User(dbrow);
        Debug(1, "Authenticated user '%s'", user->getUsername());
        mysql_free_result(result);
        return user;
      } else {
        Debug(1, "No match for %s", auth);
      }
    }  // end foreach hour
  }  // end foreach user
  mysql_free_result(result);

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
