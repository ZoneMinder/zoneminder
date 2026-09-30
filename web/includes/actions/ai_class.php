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

// AI Object Class edit actions
if (!canEdit('System')) {
  ZM\Warning('Need System permissions to manage AI object classes');
  return;
}

if ($action == 'save') {
  if (!empty($_REQUEST['id'])) {
    $dbClass = dbFetchOne('SELECT * FROM AI_Object_Classes WHERE Id=?', NULL, [$_REQUEST['id']]);
  } else {
    $dbClass = array();
  }

  $types = array();
  $changes = getFormChanges($dbClass, $_REQUEST['newClass'], $types);

  if (count($changes)) {
    if (!empty($_REQUEST['id'])) {
      dbQuery('UPDATE AI_Object_Classes SET '.implode(', ', $changes).' WHERE Id = ?',
        array($_REQUEST['id']) );
    } else {
      dbQuery('INSERT INTO AI_Object_Classes SET '.implode(', ', $changes));
    }
    $refreshParent = true;
  }
  $redirect = '?view=options&tab=ai_classes';
} else if ($action == 'delete') {
  if ( !empty($_REQUEST['markIds']) ) {
    foreach ($_REQUEST['markIds'] as $Id) {
      dbQuery('DELETE FROM AI_Object_Classes WHERE Id=?', array($Id));
    }
    $refreshParent = true;
  }
  $redirect = '?view=options&tab=ai_classes';
} else {
  ZM\Error("Unknown action $action in saving AI Object Class");
}
?>
