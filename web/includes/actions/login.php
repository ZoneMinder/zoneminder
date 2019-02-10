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


if ( $action == 'login' && isset($_REQUEST['username']) && ( ZM_AUTH_TYPE == 'remote' || isset($_REQUEST['password']) ) ) {

  $refreshParent = true;
  // User login is automatically performed in includes/auth.php So we don't need to perform a login here,
  // just handle redirects.  This is the action that comes from the login view, so the logical thing to
  // do on successful auth is redirect to console, otherwise loop back to login.
  if ( !$user ) {
    $view = 'login';
  } else {
    $view = 'postlogin';
    $redirect = '?view=console';
  }
}
?>
