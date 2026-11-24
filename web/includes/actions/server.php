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
if (!canEdit('System')) {
  ZM\Warning('Need System permissions to add servers');
  return;
}

global $error_message;

if ($action == 'save') {
  if (!empty($_REQUEST['id'])) {
    $dbServer = dbFetchOne('SELECT * FROM Servers WHERE Id=?', NULL, [$_REQUEST['id']]);
    // A server deleted in another tab matches no row, so an UPDATE would report a save that never happened
    if (!$dbServer) {
      ZM\Warning('Server not found for save');
      $error_message .= 'Server not found<br/>';
      $view = 'options';
      $_REQUEST['tab'] = 'servers';
      return;
    }
  } else {
    $dbServer = array();
  }

  $newServer = (isset($_REQUEST['newServer']) and is_array($_REQUEST['newServer'])) ? $_REQUEST['newServer'] : array();

  $types = array();
  $changes = getFormChanges($dbServer, $newServer, $types);

  $name = array_key_exists('Name', $newServer) ? $newServer['Name'] : ($dbServer['Name'] ?? '');
  if (!is_string($name) or (trim($name) === '')) {
    ZM\Warning('Refusing to save server with an empty Name');
    $error_message .= 'Server Name is required<br/>';
    $changes = array();
  }

  if (count($changes)) {
    if (!empty($_REQUEST['id'])) {
      $result = dbQuery('UPDATE Servers SET '.implode(', ', $changes).' WHERE Id = ?',
        array($_REQUEST['id']) );
    } else {
      $result = dbQuery('INSERT INTO Servers SET '.implode(', ', $changes));
    }
    if ($result === null) {
      $error_message .= 'Error saving server: '.htmlspecialchars(dbLastError()).'<br/>';
    } else {
      ZM\AuditAction((!empty($_REQUEST['id']) ? 'update' : 'create'), 'server',
        !empty($_REQUEST['id']) ? $_REQUEST['id'] : dbInsertId(),
        'Changed: '.implode(', ', array_keys($changes)));
      $refreshParent = true;
    }
  }
  if (!$error_message) {
    $redirect = '?view=options&tab=servers';
  } else {
    // The modal form posts view=server, which has no view file. Render the
    // options page instead so that the error is shown.
    $view = 'options';
    $_REQUEST['tab'] = 'servers';
  }
} else {
  ZM\Error("Unknown action $action in saving Server");
}
?>
