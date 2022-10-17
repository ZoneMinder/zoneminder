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
  // if captcha existed, it was passed

  if (!isset($user)) {
    ZM\Debug("Setting session loginFailed: " .$_SESSION['loginFailed']);
    zm_session_start();
    $_SESSION['loginFailed'] = true;
    session_write_close();
  } else {
    if (isset($_SESSION['loginFailed'])) {
      ZM\Debug("Clearing session loginFailed: " .$_SESSION['loginFailed']);
      zm_session_start();
      unset($_SESSION['loginFailed']);
      session_write_close();
    }
    $view = 'postlogin';
  }
} # end if doing a login action
?>
