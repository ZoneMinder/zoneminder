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
#include "zm_utils.h"
#include <cassert>
#include <cstring>

#if HAVE_GNUTLS_GNUTLS_H
#include <gnutls/gnutls.h>
#endif

#if HAVE_GCRYPT_H
#include <gcrypt.h>
#elif HAVE_LIBCRYPTO
#include <openssl/md5.h>
#endif  // HAVE_GCRYPT_H || HAVE_LIBCRYPTO

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
  int username_length = strlen(username);

  // According to docs, size of safer_whatever must be 2*length+1
  // due to unicode conversions + null terminator.
  std::string escaped_username((username_length * 2) + 1, '\0');


  size_t escaped_len = mysql_real_escape_string(&dbconn, &escaped_username[0], username, username_length);
  escaped_username.resize(escaped_len);

  std::string sql = stringtf("SELECT `Id`, `Username`, `Password`, `Enabled`,"
                             " `Stream`+0, `Events`+0, `Control`+0, `Monitors`+0, `System`+0,"
                             " `MonitorIds`"
                             " FROM `Users` WHERE `Username` = '%s' AND `Enabled` = 1",
                             escaped_username.c_str());

  MYSQL_RES *result = zmDbFetch(sql.c_str());
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
  std::string remote_addr = "";

  if ( use_remote_addr ) {
    remote_addr = std::string(getenv("REMOTE_ADDR"));
    if ( remote_addr == "" ) {
      Warning("Can't determine remote address, using null");
      remote_addr = "";
    }
    key += remote_addr;
  }

  Debug(1, "Inside zmLoadTokenUser, formed key=%s", key.c_str());

  std::pair<std::string, unsigned int> ans = verifyToken(jwt_token_str, key);
  std::string username = zmDbEscapeString(ans.first);
  unsigned int iat = ans.second;
  Debug(1, "retrieved user '%s' from token", username.c_str());

  if ( username == "" ) {
    return nullptr;
  }

  std::string sql = stringtf("SELECT `Id`, `Username`, `Password`, `Enabled`, `Stream`+0, `Events`+0,"
                             " `Control`+0, `Monitors`+0, `System`+0, `MonitorIds`, `TokenMinExpiry`"
                             " FROM `Users` WHERE `Username` = '%s' AND `Enabled` = 1", username.c_str());

  MYSQL_RES *result = zmDbFetch(sql.c_str());
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
#if HAVE_DECL_MD5 || HAVE_DECL_GNUTLS_FINGERPRINT
#ifdef HAVE_GCRYPT_H
  // Special initialisation for libgcrypt
  if ( !gcry_check_version(GCRYPT_VERSION) ) {
    Fatal("Unable to initialise libgcrypt");
  }
  gcry_control(GCRYCTL_DISABLE_SECMEM, 0);
  gcry_control(GCRYCTL_INITIALIZATION_FINISHED, 0);
#endif  // HAVE_GCRYPT_H

  const char *remote_addr = "";
  if ( use_remote_addr ) {
    remote_addr = getenv("REMOTE_ADDR");
    if ( !remote_addr ) {
      Warning("Can't determine remote address, using null");
      remote_addr = "";
    }
  }

  Debug(1, "Attempting to authenticate user from auth string '%s', remote addr(%s)", auth, remote_addr);
  std::string sql = "SELECT `Id`, `Username`, `Password`, `Enabled`,"
                    " `Stream`+0, `Events`+0, `Control`+0, `Monitors`+0, `System`+0,"
                    " `MonitorIds` FROM `Users` WHERE `Enabled` = 1";

  MYSQL_RES *result = zmDbFetch(sql.c_str());
  if (!result)
    return nullptr;

  int n_users = mysql_num_rows(result);
  if ( n_users < 1 ) {
    mysql_free_result(result);
    Warning("Unable to authenticate user");
    return nullptr;
  }

  // getting the time is expensive, so only do it once.
  time_t now = time(nullptr);
  unsigned int hours = config.auth_hash_ttl;
  if ( !hours ) {
    Warning("No value set for ZM_AUTH_HASH_TTL. Defaulting to 2.");
    hours = 2;
  } else {
    Debug(1, "AUTH_HASH_TTL is %d, time is %" PRIi64, hours, static_cast<int64>(now));
  }
  char auth_key[512] = "";
  char auth_md5[32+1] = "";
  constexpr size_t md5len = 16;
  uint8 md5sum[md5len];

  const char * hex = "0123456789abcdef";
  while ( MYSQL_ROW dbrow = mysql_fetch_row(result) ) {
    const char *username = dbrow[1];
    const char *password = dbrow[2];

    time_t our_now = now;
    tm now_tm = {};
    for ( unsigned int i = 0; i < hours; i++, our_now -= 3600 ) {
      localtime_r(&our_now, &now_tm);

      snprintf(auth_key, sizeof(auth_key)-1, "%s%s%s%s%d%d%d%d",
        config.auth_hash_secret,
        username,
        password,
        remote_addr,
        now_tm.tm_hour,
        now_tm.tm_mday,
        now_tm.tm_mon,
        now_tm.tm_year);

#if HAVE_DECL_MD5
      MD5((unsigned char *)auth_key, strlen(auth_key), md5sum);
#elif HAVE_DECL_GNUTLS_FINGERPRINT
      gnutls_datum_t md5data = {(unsigned char *) auth_key, (unsigned int) strlen(auth_key)};
      size_t md5_len_tmp = md5len;
      gnutls_fingerprint(GNUTLS_DIG_MD5, &md5data, md5sum, &md5_len_tmp);
      assert(md5_len_tmp == md5len);
#endif
      unsigned char *md5sum_ptr = md5sum;
      char *auth_md5_ptr = auth_md5;

      for ( unsigned int j = 0; j < md5len; j++ ) {
        *auth_md5_ptr++ = hex[(*md5sum_ptr>>4)&0xf];
        *auth_md5_ptr++ = hex[(*md5sum_ptr++)&0xf];
      }
      *auth_md5_ptr = 0;

      Debug(1, "Checking auth_key '%s' -> auth_md5 '%s' == '%s'",
          auth_key, auth_md5, auth);

      if ( !strcmp(auth, auth_md5) ) {
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
#else  // HAVE_DECL_MD5 || HAVE_DECL_GNUTLS_FINGERPRINT
  Error("You need to build with gnutls or openssl to use hash based auth");
#endif  // HAVE_DECL_MD5 || HAVE_DECL_GNUTLS_FINGERPRINT
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
