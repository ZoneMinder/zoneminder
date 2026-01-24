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

// AI Dataset edit actions
if (!canEdit('System')) {
  ZM\Warning('Need System permissions to manage AI datasets');
  return;
}

if ($action == 'save') {
  if (!empty($_REQUEST['id'])) {
    $dbDataset = dbFetchOne('SELECT * FROM AI_Datasets WHERE Id=?', NULL, [$_REQUEST['id']]);
  } else {
    $dbDataset = array();
  }

  $types = array();
  $changes = getFormChanges($dbDataset, $_REQUEST['newDataset'], $types);

  if (count($changes)) {
    if (!empty($_REQUEST['id'])) {
      dbQuery('UPDATE AI_Datasets SET '.implode(', ', $changes).' WHERE Id = ?',
        array($_REQUEST['id']) );
    } else {
      dbQuery('INSERT INTO AI_Datasets SET '.implode(', ', $changes));
    }
    $refreshParent = true;
  }
  $redirect = '?view=options&tab=ai_datasets';
} else if ($action == 'delete') {
  if ( !empty($_REQUEST['markIds']) ) {
    foreach ($_REQUEST['markIds'] as $Id) {
      dbQuery('DELETE FROM AI_Datasets WHERE Id=?', array($Id));
    }
    $refreshParent = true;
  }
  $redirect = '?view=options&tab=ai_datasets';
} else {
  ZM\Error("Unknown action $action in saving AI Dataset");
}
?>
