<?php
//
// ZoneMinder auth library, $Date$, $Revision$
// Copyright (C) 2001-2008 Philip Coombes
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
//
//
require_once('session.php');
require_once(__DIR__.'/../vendor/autoload.php');
use \Firebase\JWT\JWT;

function password_type($password) {
   if ( $password[0] == '*' ) {
    return 'mysql';
  } else if ( preg_match('/^\$2[ayb]\$.+$/', $password) ) {
    return 'bcrypt';
  } else if ( substr($password, 0,4) == '-ZM-' ) {
  // zmupdate.pl adds a '-ZM-' prefix to overlay encrypted passwords
  // this is done so that we don't spend cycles doing two bcrypt password_verify calls
  // for every wrong password entered. This will only be invoked for passwords zmupdate.pl has
  // overlay hashed
    return 'mysql+bcrypt';
  }
  return 'plain';
}

// this function migrates mysql hashing to bcrypt, if you are using PHP >= 5.5
// will be called after successful login, only if mysql hashing is detected
function migrateHash($username, $password) {
  if ( function_exists('password_hash') ) {
    global $user;
    ZM\Info("Migrating $username to bcrypt scheme");
    // let it generate its own salt, and ensure bcrypt as PASSWORD_DEFAULT may change later
    // we can modify this later to support argon2 etc as switch to its own password signature detection 
    $bcrypt_hash = password_hash($password, PASSWORD_BCRYPT);
    dbQuery('UPDATE Users SET Password=? WHERE Username=?', array($bcrypt_hash, $username));
    $user['Password'] = $bcrypt_hash;
    # Since password field has changed, existing auth_hash is no longer valid
    generateAuthHash(ZM_AUTH_HASH_IPS, true);
  } else {
    ZM\Info('Cannot migrate password scheme to bcrypt, as you are using PHP < 5.3');
    return;
  }
}

// core function used to load a User record by username and password
function validateUser($username='', $password='') {
  if (ZM_CASE_INSENSITIVE_USERNAMES) {
      $sql = 'SELECT * FROM Users WHERE Enabled=1 AND LOWER(Username)=LOWER(?)';
  } else {
      $sql = 'SELECT * FROM Users WHERE Enabled=1 AND Username=?';
  }
  // local user, shouldn't affect the global user
  $user = dbFetchOne($sql, NULL, array($username)); // Not global
  if (!$user) {
    return array(false, "Could not retrieve user $username details");
  }

  switch (password_type($user['Password'])) {
  case 'mysql' : 
    // We assume we don't need to support mysql < 4.1
    // Starting MY SQL 4.1, mysql concats a '*' in front of its password hash
    // https://blog.pythian.com/hashing-algorithm-in-mysql-password-2/
    ZM\Debug('Saved password is using MYSQL password function');
    $input_password_hash = '*'.strtoupper(sha1(sha1($password, true)));
    $password_correct = ($user['Password'] == $input_password_hash);
    break;
  case 'bcrypt' :
    ZM\Debug('bcrypt signature found, assumed bcrypt password');
    $password_correct = password_verify($password, $user['Password']);
    break;
  case 'mysql+bcrypt' : 
    // zmupdate.pl adds a '-ZM-' prefix to overlay encrypted passwords
    // this is done so that we don't spend cycles doing two bcrypt password_verify calls
    // for every wrong password entered. This will only be invoked for passwords zmupdate.pl has
    // overlay hashed
    ZM\Debug("Detected bcrypt overlay hashing for $username");
    $bcrypt_hash = substr($user['Password'], 4);
    $mysql_encoded_password = '*'.strtoupper(sha1(sha1($password, true)));
    ZM\Debug("Comparing password $mysql_encoded_password to bcrypt hash: $bcrypt_hash");
    $password_correct = password_verify($mysql_encoded_password, $bcrypt_hash);
    break;
  default:
    // we really should nag the user not to use plain
    ZM\Warning('assuming plain text password as signature is not known. Please do not use plain, it is very insecure');
    $password_correct = ($user['Password'] == $password);
  } // switch password_type

  if ($password_correct) {
    return array($user, 'OK');
  }
  return array(false, "Login denied for user \"$username\"");
} # end function validateUser

function userLogout() {
  global $user;
  ZM\Info('User "'.($user?$user['Username']:'no one').'" logged out');
  $user = null;// unset only clears the local variable
  zm_session_clear();
}

function validateToken($token, $allowed_token_type='access') {
  global $user;
  $key = ZM_AUTH_HASH_SECRET;
  //if (ZM_AUTH_HASH_IPS) $key .= $_SERVER['REMOTE_ADDR'];
  try {
    $decoded_token = JWT::decode($token, $key, array('HS256'));
  } catch (Exception $e) {
    ZM\Error("Unable to authenticate user. error decoding JWT token:".$e->getMessage());
    return array(false, $e->getMessage());
  }

  // convert from stdclass to array
  $jwt_payload = json_decode(json_encode($decoded_token), true);
  if ($allowed_token_type != 'any') {
    $type = $jwt_payload['type'];
    if ( $type != $allowed_token_type ) {
      ZM\Error("Token type mismatch. Expected $allowed_token_type but got $type");
      return array(false, 'Incorrect token type');
    }
  }
  
  $username = $jwt_payload['user'];
  if (ZM_CASE_INSENSITIVE_USERNAMES) {
    $sql = 'SELECT * FROM Users WHERE Enabled=1 AND LOWER(Username)=LOWER(?)';
  } else {
    $sql = 'SELECT * FROM Users WHERE Enabled=1 AND Username=?';
  }
  $saved_user_details = dbFetchOne($sql, NULL, array($username));

  if ($saved_user_details) {
    $issuedAt =  $jwt_payload['iat'];
    $minIssuedAt = $saved_user_details['TokenMinExpiry'];

    if ($issuedAt < $minIssuedAt) {
      ZM\Error("Token revoked for $username. Please generate a new token");
      $user = null;// unset only clears the local variable
      return array(false, 'Token revoked. Please re-generate');
    }
    $user = $saved_user_details;
    return array($user, 'OK');
  }
  ZM\Error("Could not retrieve user $username details");
  $user = null;// unset only clears the local variable
  return array(false, 'No such user/credentials');
} // end function validateToken($token, $allowed_token_type='access')

function getAuthUser($auth) {
  if (ZM_OPT_USE_AUTH && (ZM_AUTH_RELAY == 'hashed') && !empty($auth)) {
    $remoteAddr = '';
    if (ZM_AUTH_HASH_IPS) {
      $remoteAddr = $_SERVER['REMOTE_ADDR'];
      if ( !$remoteAddr ) {
        ZM\Error("Can't determine remote address for authentication, using empty string");
        $remoteAddr = '';
      }
    }

    $sql = 'SELECT * FROM Users WHERE Enabled = 1';
    $values = array();
    if (isset($_SESSION['username'])) {
      # Most of the time we will be logged in already and the session will have our username, so we can significantly speed up our hash testing by only looking at our user.
      # Only really important if you have a lot of users.
      $sql .= ' AND Username=?';
      array_push($values, $_SESSION['username']);
    }

    foreach (dbFetchAll($sql, NULL, $values) as $user) {
      $now = time();
      for ($i = 0; $i < ZM_AUTH_HASH_TTL; $i++, $now -= 3600) { // Try for last TTL hours
        $time = localtime($now);
        $authKey = ZM_AUTH_HASH_SECRET.$user['Username'].$user['Password'].$remoteAddr.$time[2].$time[3].$time[4].$time[5];
        $authHash = md5($authKey);

        if ($auth == $authHash) {
          return $user;
        } // end if $auth == $authHash
      } // end foreach hour
    } // end foreach user

    if (isset($_SESSION['username'])) {
      # In a multi-server case, we might be logged in as another user and so the auth hash didn't work
      if (ZM_CASE_INSENSITIVE_USERNAMES) {
        $sql = 'SELECT * FROM Users WHERE Enabled = 1 AND LOWER(Username) != LOWER(?)';
      } else {
        $sql = 'SELECT * FROM Users WHERE Enabled = 1 AND Username != ?';
      }

      foreach (dbFetchAll($sql, NULL, $values) as $user) {
        $now = time();
        for ($i = 0; $i < ZM_AUTH_HASH_TTL; $i++, $now -= 3600) { // Try for last TTL hours
          $time = localtime($now);
          $authKey = ZM_AUTH_HASH_SECRET.$user['Username'].$user['Password'].$remoteAddr.$time[2].$time[3].$time[4].$time[5];
          $authHash = md5($authKey);

          if ($auth == $authHash) {
            return $user;
          } // end if $auth == $authHash
        } // end foreach hour
      } // end foreach user
    } // end if 
  } // end if using auth hash

  ZM\Error("Unable to authenticate user from auth hash '$auth'");
  return null;
} // end getAuthUser($auth)

function calculateAuthHash($remoteAddr) {
  global $user;
  $local_time = localtime();
  $authKey = ZM_AUTH_HASH_SECRET.$user['Username'].$user['Password'].$remoteAddr.$local_time[2].$local_time[3].$local_time[4].$local_time[5];
  #ZM\Debug("Generated using hour:".$local_time[2] . ' mday:' . $local_time[3] . ' month:'.$local_time[4] . ' year: ' . $local_time[5] );
  return md5($authKey);
}

function generateAuthHash($useRemoteAddr, $force=false) {
  global $user;
  if (ZM_OPT_USE_AUTH and (ZM_AUTH_RELAY == 'hashed') and isset($user['Username']) and isset($user['Password']) and isset($_SESSION)) {
    $time = time();

    # We use 1800 so that we regenerate the hash at half the TTL
    $mintime = $time - (ZM_AUTH_HASH_TTL * 1800);

    # Appending the remoteAddr prevents us from using an auth hash generated for a different ip
    if ($force or ( !isset($_SESSION['AuthHash'.$_SESSION['remoteAddr']]) ) or ( $_SESSION['AuthHashGeneratedAt'] < $mintime )) {
      $auth = calculateAuthHash($useRemoteAddr?$_SESSION['remoteAddr']:'');
      # Don't both regenerating Auth Hash if an hour hasn't gone by yet
      $_SESSION['AuthHash'.$_SESSION['remoteAddr']] = $auth;
      $_SESSION['AuthHashGeneratedAt'] = $time;
      # Because we don't write out the session, it shouldn't actually get written out to disk.  However if it does, the GeneratedAt should protect us.
    } # end if AuthHash is not cached
    return $_SESSION['AuthHash'.$_SESSION['remoteAddr']];
  } # end if using AUTH and AUTH_RELAY
  return '';
}

function visibleMonitor($mid) {
  global $user;

  return ( $user && empty($user['MonitorIds']) || in_array($mid, explode(',', $user['MonitorIds'])) );
}

function canView($area, $mid=false) {
  global $user;

  return ( $user && ($user[$area] == 'View' || $user[$area] == 'Edit') && ( !$mid || visibleMonitor($mid) ) );
}

function canEdit($area, $mid=false) {
  global $user;

  return ( $user && ($user[$area] == 'Edit') && ( !$mid || visibleMonitor($mid) ));
}

function userFromSession() {
  $user = null; // Not global
  if (isset($_SESSION['username'])) {
    if (ZM_AUTH_HASH_LOGINS and (ZM_AUTH_RELAY == 'hashed')) {
      # Extra validation, if logged in, then the auth hash will be set in the session, so we can validate it.
      # This prevent session modification to switch users
      if (isset($_SESSION['AuthHash'.$_SESSION['remoteAddr']]))
        $user = getAuthUser($_SESSION['AuthHash'.$_SESSION['remoteAddr']]);
      else
        ZM\Debug('No auth hash in session, there should have been');
    } else {
      # Need to refresh permissions and validate that the user still exists
      if (ZM_CASE_INSENSITIVE_USERNAMES) {
        $sql = 'SELECT * FROM Users WHERE Enabled=1 AND LOWER(Username)=LOWER(?)';
      } else {
        $sql = 'SELECT * FROM Users WHERE Enabled=1 AND Username=?';
      }
      $user = dbFetchOne($sql, NULL, array($_SESSION['username']));
    }
  }
  return $user;
}

function get_auth_relay() {
  if (ZM_OPT_USE_AUTH) {
    if (ZM_AUTH_RELAY == 'hashed') {
      return 'auth='.generateAuthHash(ZM_AUTH_HASH_IPS);
    } else if (ZM_AUTH_RELAY == 'plain') {
      // password probably needs to be escaped
      return 'username='.(isset($_SESSION['username'])?$_SESSION['username']:'').'&password='.urlencode(isset($_SESSION['password']) ? $_SESSION['password'] : '');
    } else if (ZM_AUTH_RELAY == 'none') {
      return 'username='.$_SESSION['username'];
    } else {
      ZM\Error('Unknown value for ZM_AUTH_RELAY ' . ZM_AUTH_RELAY);
    }
  }
  return '';
} // end function get_auth_relay

if (ZM_OPT_USE_AUTH) {
  if (!empty($_REQUEST['token'])) {
    // we only need to get the username here
    // don't know the token type. That will
    // be checked later 
    $ret = validateToken($_REQUEST['token'], 'any');
    $user = $ret[0];
  } else {
    // Non token based auth

    if (ZM_AUTH_HASH_LOGINS && empty($user) && !empty($_REQUEST['auth'])) {
      $user = getAuthUser($_REQUEST['auth']);
    } else if (!(empty($_REQUEST['username']) or empty($_REQUEST['password']))) {
      $ret = validateUser($_REQUEST['username'], $_REQUEST['password']);
      if (!$ret[0]) {
        ZM\Error($ret[1]);
        unset($user); // unset should be ok here because we aren't in a function
        return;
      }
      $user = $ret[0];

      if (
        defined('ZM_OPT_USE_GOOG_RECAPTCHA') && ZM_OPT_USE_GOOG_RECAPTCHA
        && defined('ZM_OPT_GOOG_RECAPTCHA_SECRETKEY') && ZM_OPT_GOOG_RECAPTCHA_SECRETKEY
        && defined('ZM_OPT_GOOG_RECAPTCHA_SITEKEY') && ZM_OPT_GOOG_RECAPTCHA_SITEKEY
      ) {
        if ( !isset($_REQUEST['g-recaptcha-response']) ) {
          ZM\Error('reCaptcha authentication failed. No g-recpatcha-response in REQUEST: ');
          unset($user); // unset should be ok here because we aren't in a function
          return;
        }
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $fields = array (
          'secret'    => ZM_OPT_GOOG_RECAPTCHA_SECRETKEY,
          'response'  => $_REQUEST['g-recaptcha-response'],
          'remoteip'  => $_SERVER['REMOTE_ADDR']
        );
        $res = do_post_request($url, http_build_query($fields));
        $responseData = json_decode($res, true);
        // credit: https://github.com/google/recaptcha/blob/master/src/ReCaptcha/Response.php
        // if recaptcha resulted in error, we might have to deny login
        if ( isset($responseData['success']) && ($responseData['success'] == false) ) {
          // PP - before we deny auth, let's make sure the error was not 'invalid secret'
          // because that means the user did not configure the secret key correctly
          // in this case, we prefer to let him login in and display a message to correct
          // the key. Unfortunately, there is no way to check for invalid site key in code
          // as it produces the same error as when you don't answer a recaptcha
          if ( isset($responseData['error-codes']) && is_array($responseData['error-codes']) ) {
            if ( !in_array('invalid-input-secret', $responseData['error-codes']) ) {
              ZM\Error('reCaptcha authentication failed. response was: ' . print_r($responseData['error-codes'],true));
              unset($user); // unset should be ok here because we aren't in a function
              return;
            } else {
              ZM\Error('Invalid recaptcha secret detected');
            }
          }
        } // end if success==false
      } // end if using reCaptcha

      zm_session_clear(); # Closes session
      zm_session_regenerate_id(); # starts session

      $username = $_REQUEST['username'];
      $password = $_REQUEST['password'];

      ZM\Info("Login successful for user \"$username\"");
      $password_type = password_type($user['Password']);

      if ( $password_type == 'mysql' or $password_type == 'mysql+bcrypt' ) {
        ZM\Info('Migrating password, if possible for future logins');
        migrateHash($username, $password);
      }

      if (ZM_AUTH_TYPE == 'builtin') {
        $_SESSION['passwordHash'] = $user['Password'];
      }

      $_SESSION['username'] = $user['Username'];
      if (ZM_AUTH_RELAY == 'plain') {
        // Need to save this in session, can't use the value in User because it is hashed
        $_SESSION['password'] = $_REQUEST['password'];
      }
    } else if ((ZM_AUTH_TYPE == 'remote') and !empty($_SERVER['REMOTE_USER'])) {
      if (ZM_CASE_INSENSITIVE_USERNAMES) {
        $sql = 'SELECT * FROM Users WHERE Enabled=1 AND LOWER(Username)=LOWER(?)';
      } else {
        $sql = 'SELECT * FROM Users WHERE Enabled=1 AND Username=?';
      }
      // local user, shouldn't affect the global user
      $user = dbFetchOne($sql, NULL, array($_SERVER['REMOTE_USER']));
    } else {
      $user = userFromSession();
    }

    if (!empty($user)) {
      // generate it once here, while session is open.  Value will be cached in session and return when called later on
      generateAuthHash(ZM_AUTH_HASH_IPS);
    }
  } # end if token based auth
} else {
  $user = $defaultUser;
} # end if ZM_OPT_USE_AUTH
?>
