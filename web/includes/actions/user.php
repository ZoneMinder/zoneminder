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
    $user_with_my_username = ZM\User::find_one(array('Username'=>$_REQUEST['user']['Username']));
    if ($user_with_my_username and 
      ( ( $uid and ($user_with_my_username->Id() != $uid) ) or !$uid)
    ) {
      $error_message = 'There already exists a user with this Username<br/>';
      unset($_REQUEST['redirect']);
      return;
    }
    # What other tests should we do?

    if (isset($_REQUEST['user']['MonitorIds']) and is_array($_REQUEST['user']['MonitorIds']))
      $_REQUEST['user']['MonitorIds'] = implode(',', $_REQUEST['user']['MonitorIds']);
    if (!empty($_REQUEST['user']['Password'])) {
      $_REQUEST['user']['Password'] = password_hash($_REQUEST['user']['Password'], PASSWORD_BCRYPT);
    } else {
      unset($_REQUEST['user']['Password']);
    }
    if (isset($_REQUEST['user']['Language']) and $_REQUEST['user']['Language']) {
      # Verify that the language file exists in the lang directory.
      if (!file_exists(ZM_PATH_WEB.'/lang/'.$_REQUEST['user']['Language'].'.php')) {
        $error_message .= 'Error setting Language. New value ' .$_REQUEST['user']['Language'].' not saved because '.ZM_PATH_WEB.'/lang/'.$_REQUEST['user']['Language'].'.php doesn\'t exist.<br/>';
        ZM\Error($error_message);
        unset($_REQUEST['user']['Language']);
        unset($_REQUEST['redirect']);
      }
    }
    $changes = $dbUser->changes($_REQUEST['user']);
    if (count($changes)) {
      if (!$dbUser->save($changes)) {
        $error_message .= $dbUser->get_last_error().'<br/>';
        unset($_REQUEST['redirect']);
        return;
      }

      if ($uid) {
        if ($user and ($dbUser->Username() == $user->Username())) {
          # We are the logged in user, need to update the $user object and generate a new auth_hash
          $user = ZM\User::find_one(['Enabled'=>1, 'Id'=>$uid]);

          # Have to update auth hash in session
          zm_session_start();
          generateAuthHash(ZM_AUTH_HASH_IPS, true);
          session_write_close();
        }
      }
    } # end if changes

    foreach (ZM\Group::find() as $g) {
      if (isset($_POST['group_permission'])) {
        $permission = $g->Group_Permission($dbUser->Id());
        if ($permission->Permission() != $_POST['group_permission'][$g->Id()]) {
          $permission->save(array('Permission'=>$_POST['group_permission'][$g->Id()]));
          $g->Permissions(null); # reload
        }
      }
    }

    if (isset($_POST['monitor_permission'])) {
      foreach (ZM\Monitor::find(['Deleted'=>false]) as $m) {
        if (isset($_POST['monitor_permission'][$m->Id()])) {
          $permission = $dbUser->Monitor_Permission($m->Id());
          $new_permission = $_POST['monitor_permission'][$m->Id()];
          if ($permission->Permission() != $new_permission) {
            $permission->save(['Permission'=>$new_permission]);
          }
        }
      }
    } # end if isset monitor_permission
    $dbUser->Monitor_Permissions(null); # reload
  } else if (ZM_USER_SELF_EDIT and ($uid == $user->Id())) {
    if (!empty($_REQUEST['user']['Password'])) {
      $_REQUEST['user']['Password'] = password_hash($_REQUEST['user']['Password'], PASSWORD_BCRYPT);
    } else {
      unset($_REQUEST['user']['Password']);
    }
    if (isset($_REQUEST['user']['Language']) and $_REQUEST['user']['Language']) {
      # Verify that the language file exists in the lang directory.
      if (!file_exists(ZM_PATH_WEB.'/lang/'.$_REQUEST['user']['Language'].'.php')) {
        $error_message .= 'Error setting Language. New value ' .$_REQUEST['user']['Language'].' not saved because '.ZM_PATH_WEB.'/lang/'.$_REQUEST['user']['Language'].'.php doesn\'t exist.<br/>';
        ZM\Error($error_message);
        unset($_REQUEST['user']['Language']);
        unset($_REQUEST['redirect']);
      }
    }
    $fields = array('Password'=>'', 'Language'=>'', 'HomeView'=>'');
    $changes = $dbUser->changes(array_intersect_key($_REQUEST['user'], $fields));

    if (count($changes)) {
      if (!$dbUser->save($changes)) {
        $error_message .= $dbUser->get_last_error();
        unset($_REQUEST['redirect']);
        return;
      }

      # We are the logged in user, need to update the $user object and generate a new auth_hash
      $user = ZM\User::find_one(['Enabled'=>1, 'Id'=>$uid]);
      
      zm_session_start();
      generateAuthHash(ZM_AUTH_HASH_IPS, true);
      session_write_close();
    } # end if changes
  } # canEdit(System) or self edit
} // end if $action == user
?>
