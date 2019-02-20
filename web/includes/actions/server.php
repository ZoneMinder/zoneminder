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

// System edit actions
if ( ! canEdit('System') ) {
  ZM\Warning('Need System permissions to add servers');
  return;
}

if ( $action == 'Save' ) {
  if ( !empty($_REQUEST['id']) ) {
    $dbServer = dbFetchOne(
      'SELECT * FROM Servers WHERE Id=?',
      NULL,
      array($_REQUEST['id']) );
  } else {
    $dbServer = array();
  }

  $types = array();
  $changes = getFormChanges($dbServer, $_REQUEST['newServer'], $types);

  if ( count($changes) ) {
    if ( !empty($_REQUEST['id']) ) {
      dbQuery('UPDATE Servers SET '.implode(', ', $changes).' WHERE Id = ?',
        array($_REQUEST['id']) );
    } else {
      dbQuery('INSERT INTO Servers SET '.implode(', ', $changes));
    }
    $refreshParent = true;
  }
  $view = 'none';
} else {
  ZM\Error("Unknown action $action in saving Server");
}
?>
