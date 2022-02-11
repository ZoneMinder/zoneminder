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

global $error_message;

if ($action == 'Save') {
  require_once('includes/User.php');
  $uid = isset($_REQUEST['uid']) ? validInt($_REQUEST['uid']) : 0;
  $dbUser = new ZM\User($uid);

  if (canEdit('System')) {
    # Need to check for uniqueness of Username
    $user_with_my_username = ZM\User::find_one(array('Username'=>$_REQUEST['newUser']['Username']));
    if ($user_with_my_username and 
      ( ( $uid and ($user_with_my_username->Id() != $uid) ) or !$uid)
    ) {
      $error_message = 'There already exists a user with this Username<br/>';
      unset($_REQUEST['redirect']);
      return;
    }
    # What other tests should we do?

    if (isset($_REQUEST['newUser']['MonitorIds']) and is_array($_REQUEST['newUser']['MonitorIds']))
      $_REQUEST['newUser']['MonitorIds'] = implode(',', $_REQUEST['newUser']['MonitorIds']);
    if (!empty($_REQUEST['newUser']['Password'])) {
      $_REQUEST['newUser']['Password'] = password_hash($_REQUEST['newUser']['Password'], PASSWORD_BCRYPT);
    } else {
      unset($_REQUEST['newUser']['Password']);
    }
    if (isset($_REQUEST['newUser']['Language']) and $_REQUEST['newUser']['Language']) {
      # Verify that the language file exists in the lang directory.
      if (!file_exists(ZM_PATH_WEB.'/lang/'.$_REQUEST['newUser']['Language'].'.php')) {
        $error_message .= 'Error setting Language. New value ' .$_REQUEST['newUser']['Language'].' not saved because '.ZM_PATH_WEB.'/lang/'.$_REQUEST['newUser']['Language'].'.php doesn\'t exist.<br/>';
        ZM\Error($error_message);
        unset($_REQUEST['newUser']['Language']);
        unset($_REQUEST['redirect']);
      }
    }
    $changes = $dbUser->changes($_REQUEST['newUser']);
    ZM\Debug('Changes: ' . print_r($changes, true));

    if (count($changes)) {
      if (!$dbUser->save($changes)) {
        $error_message .= $dbUser->get_last_error().'<br/>';
        unset($_REQUEST['redirect']);
        return;
      }

      if ($uid) {
        if ($user and ($dbUser->Username() == $user['Username'])) {
          # We are the logged in user, need to update the $user object and generate a new auth_hash
          $sql = 'SELECT * FROM Users WHERE Enabled=1 AND Id=?';
          $user = dbFetchOne($sql, NULL, array($uid));

          # Have to update auth hash in session
          zm_session_start();
          generateAuthHash(ZM_AUTH_HASH_IPS, true);
          session_write_close();
        }
      }
    } # end if changes
  } else if (ZM_USER_SELF_EDIT and ($uid == $user['Id'])) {
    if (!empty($_REQUEST['newUser']['Password'])) {
      $_REQUEST['newUser']['Password'] = password_hash($_REQUEST['newUser']['Password'], PASSWORD_BCRYPT);
    } else {
      unset($_REQUEST['newUser']['Password']);
    }
    if (isset($_REQUEST['newUser']['Language']) and $_REQUEST['newUser']['Language']) {
      # Verify that the language file exists in the lang directory.
      if (!file_exists(ZM_PATH_WEB.'/lang/'.$_REQUEST['newUser']['Language'].'.php')) {
        $error_message .= 'Error setting Language. New value ' .$_REQUEST['newUser']['Language'].' not saved because '.ZM_PATH_WEB.'/lang/'.$_REQUEST['newUser']['Language'].'.php doesn\'t exist.<br/>';
        ZM\Error($error_message);
        unset($_REQUEST['newUser']['Language']);
        unset($_REQUEST['redirect']);
      }
    }
    $fields = array('Password'=>'', 'Language'=>'', 'HomeView'=>'');
    ZM\Debug("changes: ".print_r(array_intersect_key($_REQUEST['newUser'], $fields),true));
    $changes = $dbUser->changes(array_intersect_key($_REQUEST['newUser'], $fields));
    ZM\Debug("changes: ".print_r($changes, true));

    if (count($changes)) {
      if (!$dbUser->save($changes)) {
        $error_message .= $dbUser->get_last_error();
        unset($_REQUEST['redirect']);
        return;
      }

      # We are the logged in user, need to update the $user object and generate a new auth_hash
      $sql = 'SELECT * FROM Users WHERE Enabled=1 AND Id=?';
      $user = dbFetchOne($sql, NULL, array($uid));
      
      zm_session_start();
      generateAuthHash(ZM_AUTH_HASH_IPS, true);
      session_write_close();
    }
  }
} // end if $action == user
?>
