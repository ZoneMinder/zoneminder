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

if (!canEdit('System')) {
  ZM\Warning('Must have System permissions to perform role actions');
  return;
}

if ($action == 'Save') {
  require_once('includes/User_Role.php');
  require_once('includes/Role_Group_Permission.php');
  require_once('includes/Role_Monitor_Permission.php');
  require_once('includes/Group.php');
  require_once('includes/Monitor.php');

  $rid = isset($_REQUEST['rid']) ? validInt($_REQUEST['rid']) : 0;
  $dbRole = new ZM\User_Role($rid);

  # Need to check for uniqueness of Name
  if (isset($_REQUEST['role']['Name']) && $_REQUEST['role']['Name']) {
    $role_with_my_name = ZM\User_Role::find_one(array('Name'=>$_REQUEST['role']['Name']));
    if ($role_with_my_name and
      (($rid and ($role_with_my_name->Id() != $rid)) or !$rid)
    ) {
      $error_message = 'There already exists a role with this Name<br/>';
      unset($_REQUEST['redirect']);
      return;
    }
  } else {
    $error_message = 'Role name is required<br/>';
    unset($_REQUEST['redirect']);
    return;
  }

  $changes = $dbRole->changes($_REQUEST['role']);
  if (count($changes)) {
    if (!$dbRole->save($changes)) {
      $error_message .= $dbRole->get_last_error().'<br/>';
      unset($_REQUEST['redirect']);
      return;
    }
  }

  # Save group permissions
  if (isset($_POST['group_permission'])) {
    foreach (ZM\Group::find() as $g) {
      $permission = $dbRole->Group_Permission($g->Id());
      $new_permission = isset($_POST['group_permission'][$g->Id()]) ? $_POST['group_permission'][$g->Id()] : 'Inherit';
      if ($permission->Permission() != $new_permission) {
        $permission->RoleId($dbRole->Id());
        $permission->save(array('Permission'=>$new_permission));
      }
    }
  }

  # Save monitor permissions
  if (isset($_POST['monitor_permission'])) {
    foreach (ZM\Monitor::find(['Deleted'=>false]) as $m) {
      if (isset($_POST['monitor_permission'][$m->Id()])) {
        $permission = $dbRole->Monitor_Permission($m->Id());
        $new_permission = $_POST['monitor_permission'][$m->Id()];
        if ($permission->Permission() != $new_permission) {
          $permission->RoleId($dbRole->Id());
          $permission->save(['Permission'=>$new_permission]);
        }
      }
    }
  }
} // end if $action == Save
?>
