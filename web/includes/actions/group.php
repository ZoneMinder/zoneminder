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

  $new_ids = isset($_REQUEST['newGroup']['MonitorIds']) ? array_map('intval', (array)$_REQUEST['newGroup']['MonitorIds']) : array();
  $old_ids = $group->Id() ? dbFetchAll('SELECT `MonitorId` FROM `Groups_Monitors` WHERE `GroupId`=?', 'MonitorId', array($group->Id())) : array();
  $moved = array_merge(array_diff($old_ids, $new_ids), array_diff($new_ids, $old_ids));
  $new_parent = ($_REQUEST['newGroup']['ParentId'] == '' ? null : $_REQUEST['newGroup']['ParentId']);
  // Re-parenting moves every monitor of this group and its children between ancestors.
  if ($group->Id() and ($new_parent != $group->ParentId())) $moved = array_merge($moved, $group->MonitorIds());
  if (!ZM\Group::canEditMembership($moved)) {
    ZM\Warning('Need edit permission on every monitor moved into or out of a group');
    return;
  }

  $group->save(
      array(
      'Name'=>  $_REQUEST['newGroup']['Name'],
      'ParentId'=>( $_REQUEST['newGroup']['ParentId'] == '' ? null : $_REQUEST['newGroup']['ParentId'] ),
      )
    );
  dbQuery('DELETE FROM `Groups_Monitors` WHERE `GroupId`=?', array($group_id));
  $group_id = $group->Id();
  if ($group_id and isset($_REQUEST['newGroup']['MonitorIds'])) {
    foreach ( $new_ids as $mid ) {
      dbQuery('INSERT INTO `Groups_Monitors` (`GroupId`,`MonitorId`) VALUES (?,?)', array($group_id, $mid));
    }
  }
  ZM\AuditAction((!empty($_REQUEST['gid']) ? 'update' : 'create'), 'group', $group->Id(), 'Name: '.$_REQUEST['newGroup']['Name']);
  $redirect = '?view=groups';
}
?>
