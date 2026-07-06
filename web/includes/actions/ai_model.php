<?php
//
// ZoneMinder web action file
// Copyright (C) 2024 ZoneMinder LLC
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

// AI Model edit actions
if (!canEdit('System')) {
  ZM\Warning('Need System permissions to manage AI models');
  return;
}

if ($action == 'save') {
  if (!empty($_REQUEST['id'])) {
    $dbModel = dbFetchOne('SELECT * FROM AI_Models WHERE Id=?', NULL, [$_REQUEST['id']]);
  } else {
    $dbModel = array();
  }

  $types = array();
  $changes = getFormChanges($dbModel, $_REQUEST['newModel'], $types);

  if (count($changes)) {
    if (!empty($_REQUEST['id'])) {
      dbQuery('UPDATE AI_Models SET '.implode(', ', $changes).' WHERE Id = ?',
        array($_REQUEST['id']) );
    } else {
      dbQuery('INSERT INTO AI_Models SET '.implode(', ', $changes));
    }
    $refreshParent = true;
  }
  $redirect = '?view=options&tab=ai_models';
} else if ($action == 'delete') {
  if ( !empty($_REQUEST['markIds']) ) {
    foreach ($_REQUEST['markIds'] as $Id) {
      dbQuery('DELETE FROM AI_Models WHERE Id=?', array($Id));
    }
    $refreshParent = true;
  }
  $redirect = '?view=options&tab=ai_models';
} else {
  ZM\Error("Unknown action $action in saving AI Model");
}
?>
