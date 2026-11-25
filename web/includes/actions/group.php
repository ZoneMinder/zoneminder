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

// Group edit actions
# Changing a group's monitors also needs edit on each monitor moved, checked below.
if ( !canEdit('Groups') ) {
  ZM\Warning('Need group edit permissions to edit groups');
  return;
}

if ( $action == 'save' ) {
  $group_id = null;
  if ( !empty($_REQUEST['gid']) )
    $group_id = $_REQUEST['gid'];
  $group = new ZM\Group($group_id);
  // A group deleted in another tab would be saved here as a new one
  if ($group_id and !$group->Id()) {
    ZM\Warning('Group not found for save');
    $error_message .= 'Group not found.<br/>';
    $view = 'groups';
    return;
  }

  $new_ids = isset($_REQUEST['newGroup']['MonitorIds']) ? array_map('intval', (array)$_REQUEST['newGroup']['MonitorIds']) : array();
  $old_ids = $group->Id() ? dbFetchAll('SELECT `MonitorId` FROM `Groups_Monitors` WHERE `GroupId`=?', 'MonitorId', array($group->Id())) : array();
  $moved = array_merge(array_diff($old_ids, $new_ids), array_diff($new_ids, $old_ids));
  $new_parent = (($_REQUEST['newGroup']['ParentId'] ?? '') == '' ? null : $_REQUEST['newGroup']['ParentId']);
  $raw_name = $_REQUEST['newGroup']['Name'] ?? '';
  $new_name = is_array($raw_name) ? '' : trim((string)$raw_name);
  if ($new_name === '') {
    ZM\Warning('Group save with empty name for group '.$group->Id());
    $error_message .= 'A group needs a name.<br/>';
    $view = 'groups';
    return;
  }
  // The group modal posts view=group, which has no view file, so failures are
  // shown on the Groups page; otherwise index.php sends the user to the console.
  if (!ZM\Group::validParent($group->Id(), $new_parent)) {
    ZM\Warning('Invalid parent group for group '.$group->Id());
    $error_message .= 'A group cannot be placed under itself or one of its own groups.<br/>';
    $view = 'groups';
    return;
  }
  // Re-parenting moves every monitor of this group and its children between ancestors.
  if ($group->Id() and ($new_parent != $group->ParentId())) $moved = array_merge($moved, $group->MonitorIds());
  if (!ZM\Group::canEditMembership($moved)) {
    ZM\Warning('Need edit permission on every monitor moved into or out of a group');
    $error_message .= 'You need edit permission on every monitor moved into or out of the group.<br/>';
    $view = 'groups';
    return;
  }

  if (!$group->save(
      array(
      'Name'=>  $new_name,
      'ParentId'=>$new_parent,
      )
    )) {
    $error_message .= validHtmlStr($group->get_last_error());
    $view = 'groups';
    return;
  }
  $group_id = $group->Id();
  if (dbQuery('DELETE FROM `Groups_Monitors` WHERE `GroupId`=?', array($group_id)) === NULL) {
    $error_message .= 'Error clearing group monitors: '.htmlspecialchars(dbLastError()).'<br/>';
    $view = 'groups';
    return;
  }
  if ($group_id and isset($_REQUEST['newGroup']['MonitorIds'])) {
    $failed = false;
    foreach ( $new_ids as $mid ) {
      if (dbQuery('INSERT INTO `Groups_Monitors` (`GroupId`,`MonitorId`) VALUES (?,?)', array($group_id, $mid)) === NULL) {
        $error_message .= 'Error adding monitor '.$mid.' to group: '.htmlspecialchars(dbLastError()).'<br/>';
        $failed = true;
        break;
      }
    }
    if ($failed) {
      $view = 'groups';
      return;
    }
  }
  ZM\AuditAction((!empty($_REQUEST['gid']) ? 'update' : 'create'), 'group', $group->Id(), 'Name: '.$_REQUEST['newGroup']['Name']);
  $redirect = '?view=groups';
}
?>
