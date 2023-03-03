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

if ('login' == $action) {
  if (isset($_REQUEST['username']) && ( ZM_AUTH_TYPE == 'remote' || isset($_REQUEST['password']))) {
    // if true, a popup will display after login
    // lets validate reCaptcha if it exists

    // if captcha existed, it was passed

    zm_session_start();
    if (!isset($user) ) {
      $_SESSION['loginFailed'] = true;
    } else {
      unset($_SESSION['loginFailed']);
      $view = 'postlogin';
    }
    unset($_SESSION['postLoginQuery']);
    session_write_close();
  } else {

  }
} else if ('forgotpassword' == $action) {
  global $error_mesage;
  if ($user) {
    $error_message .= 'You are already logged in. Not doing password recovery.<br/>';
    return;
  }
  require_once('includes/MagicLink.php');
  if (empty($_REQUEST['username'])) {
    $error_message .= 'You must specify a user by username or email address.<br/>';
    return;
  }
  $u = ZM\User::find_one(['Username'=>$_REQUEST['username']]);
  if (!$u) {
    $u = ZM\User::find_one(['Email'=>$_REQUEST['username']]);
    if (!$u) {
      $error_message .= 'No user found for that username/email.<br/>';
      return;
    }
  }
  if (!$u->Email()) {
    $error_message .= 'User does not have an email address assigned. We will not be able to send a magic link. Please have an admin reset your password.<br/>';
    return;
  }
  userLogout(); # clear out user stored in session
  global $user;
  $user = dbFetchOne('SELECT * FROM Users WHERE Id=?', NULL, [ $u->Id() ]);
  ZM\Debug("User". print_r($user, true));

  $link = new ZM\MagicLink();
  $link->UserId($u->Id());
  if (!$link->GenerateToken()) {
    $error_message .= 'There was a system error generating the magic link. Please contact support.<br/>';
    return;
  }
  if (!$link->save()) {
    $error_message .= 'There was a system error generating the magic link. Please contact support.<br/>';
    return;
  }
  $error_message .= 'Please check your email for a link to change your password.<br/>';

  $email_content = get_include_contents($_SERVER['DOCUMENT_ROOT'].'/email_content/forgotten_password.php');
  ZM\Debug("Email content $email_content");
  $email_content = get_include_contents($_SERVER['DOCUMENT_ROOT'].'/email_content/template.php');
  ZM\Debug("Email content $email_content");
  if (!$email_content) {
    $email_content .= '
<html>
  <head>
    <title>Account Recovery Forgotten Password</title>
  </head>
<body>
<p>
Use the following link to login at '.ZM_URL.'
</p>
<p>
<a href="'.$link->url().'">Click here to login and reset your password.<a/>
</body>
</html>
';
  }
  # Send an email
  $subject = 'Account Recovery Forgotten Password';

  send_email($u->Email(), ZM_FROM_EMAIL, $subject, $email_content);
} # end if doing a login action
?>
