<?php
//
// ZoneMinder web action file
// Copyright (C) 2019 ZoneMinder LLC
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


if ( ('login' == $action) && isset($_REQUEST['username']) && ( ZM_AUTH_TYPE == 'remote' || isset($_REQUEST['password']) ) ) {

  // if true, a popup will display after login
  // lets validate reCaptcha if it exists
  if (
    defined('ZM_OPT_USE_GOOG_RECAPTCHA')
    && defined('ZM_OPT_GOOG_RECAPTCHA_SECRETKEY')
    && defined('ZM_OPT_GOOG_RECAPTCHA_SITEKEY')
    && ZM_OPT_USE_GOOG_RECAPTCHA
    && ZM_OPT_GOOG_RECAPTCHA_SECRETKEY
    && ZM_OPT_GOOG_RECAPTCHA_SITEKEY )
  {
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
    if ( ! (empty($_REQUEST['username']) or empty($_REQUEST['password'])) ) {
      $ret = validateUser($_REQUEST['username'], $_REQUEST['password']);
      if ( !$ret[0] ) {
        ZM\Error($ret[1]);
        unset($user); // unset should be ok here because we aren't in a function
      } else {
        $user = $ret[0];
      }
    } # end if have username and password
  } // end if using reCaptcha

  // if captcha existed, it was passed

  if ( ! isset($user) ) {
    $_SESSION['loginFailed'] = true;
    return;
  }

  $close_session = 0;
  if ( !is_session_started() ) {
    zm_session_start();
    $close_session = 1;
  }

  $username = $_REQUEST['username'];
  $password = $_REQUEST['password'];

  ZM\Info("Login successful for user \"$username\"");
  $password_type = password_type($password);

  if ( $password_type == 'mysql' or $password_type == 'mysql+bcrypt' ) {
    ZM\Info('Migrating password, if possible for future logins');
    migrateHash($username, $password);
  }
  unset($_SESSION['loginFailed']);
  if ( ZM_AUTH_TYPE == 'builtin' ) {
    $_SESSION['passwordHash'] = $user['Password'];
  }
  $_SESSION['username'] = $user['Username'];
  if ( ZM_AUTH_RELAY == 'plain' ) {
    // Need to save this in session, can't use the value in User because it is hashed
    $_SESSION['password'] = $_REQUEST['password'];
  }
  zm_session_regenerate_id();
  generateAuthHash(ZM_AUTH_HASH_IPS, true);
  if ( $close_session )
    session_write_close();

  $view = 'postlogin';
} # end if doing a login action
?>
