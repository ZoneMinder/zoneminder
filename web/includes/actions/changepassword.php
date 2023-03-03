<?php
//
// ZoneMinder web action file
// Copyright (C) 2023 ZoneMinder LLC
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

global $error_mesage;
if (!$user) {
  # Must login by magic magic
  if(empty($_REQUEST['magic'])) {
    $error_message .= 'You must either be logged in, or authenticate by magic link to change your password.<br/>';
    return;
  }

  if (!(isset($_REQUEST['magic']) and $_REQUEST['magic'])) {
    $error_message .= 'changepassword requires a magic link token.<br/>';
    return;
  }
  if (empty($_REQUEST['user_id'])) {
    $error_message .= 'You must specify a user.<br/>';
    return;
  }
  $u = ZM\User::find_one(['Id'=>$_REQUEST['user_id']]);
  if (!$u) {
    $error_message .= 'User not found.<br/>';
    return;
  }
  require_once('includes/MagicLink.php');
  $link = ZM\MagicLink::find_one(['UserId'=>$u->Id(), 'Token'=>$_REQUEST['magic']]);
  if (!$link) {
    $error_message .= 'Magic link invalid or expired.<br/>';
    return;
  }
  $link->delete();
  userLogout(); # clear out user stored in session
  $user = dbFetchOne('SELECT * FROM Users WHERE Id=?', NULL, [ $u->Id() ]);
  # user is global, so this effectively logs us in, but since we don't update session or anything else, it won't last.
}

if ('changepassword' == $action) {
  if (empty($_REQUEST['password'])) {
    $error_message .= 'Password cannot be empty.<br/>';
    return;
  }
  $User = new ZM\User($user);

  $bcrypt_hash = password_hash($_REQUEST['password'], PASSWORD_BCRYPT);
  if ($User->save(['Password'=>$bcrypt_hash]) and $User->Enabled()) {
    saveUserToSession($user);
  }
} # end if doing a login action
?>
