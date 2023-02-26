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
} # end if doing a login action
?>
