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
# Should probably verify that each monitor id is a valid monitor, that we have access to.
# However at the moment, you have to have System permissions to do this
if ( ! canEdit('Groups') ) {
  ZM\Warning('Need group edit permissions to edit groups');
  return;
}

if ( $action == 'Save' ) {
  $monitors = empty($_POST['newGroup']['MonitorIds']) ? '' : implode(',', $_POST['newGroup']['MonitorIds']);
  $group_id = null;
  if ( !empty($_POST['gid']) ) {
    $group_id = $_POST['gid'];
    dbQuery(
      'UPDATE Groups SET Name=?, ParentId=? WHERE Id=?',
      array(
        $_POST['newGroup']['Name'],
        ( $_POST['newGroup']['ParentId'] == '' ? null : $_POST['newGroup']['ParentId'] ),
        $group_id,
      )
    );
    dbQuery('DELETE FROM Groups_Monitors WHERE GroupId=?', array($group_id));
  } else {
    dbQuery(
      'INSERT INTO Groups (Name,ParentId) VALUES (?,?)',
      array(
        $_POST['newGroup']['Name'],
        ( $_POST['newGroup']['ParentId'] == '' ? null : $_POST['newGroup']['ParentId'] ),
      )
    );
    $group_id = dbInsertId();
  }
  if ( $group_id ) {
    foreach ( $_POST['newGroup']['MonitorIds'] as $mid ) {
      dbQuery('INSERT INTO Groups_Monitors (GroupId,MonitorId) VALUES (?,?)', array($group_id, $mid));
    }
  }
  $view = 'none';
  $refreshParent = true;
} 
?>
