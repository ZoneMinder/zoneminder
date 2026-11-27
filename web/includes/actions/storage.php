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
if ( !canEdit('System') ) {
  ZM\Warning('Need System permission to edit Storage');
  return;
}

global $error_message;

if ($action == 'save') {
  if (!isset($_REQUEST['id']) || !isset($_REQUEST['newStorage']) || !is_array($_REQUEST['newStorage'])) {
    ZM\Error('Storage save requires an id and newStorage');
    $error_message .= 'Invalid storage request';
    $changes = array();
  } else {
    $storage = new ZM\Storage(validCardinal($_REQUEST['id']));

    # Storage::Path() falls back to Name when Path is empty, which would turn the
    # Name into a filesystem path, so neither may be blank.
    $name = array_key_exists('Name', $_REQUEST['newStorage']) ? $_REQUEST['newStorage']['Name'] : ($storage->{'Name'} ?? '');
    $path = array_key_exists('Path', $_REQUEST['newStorage']) ? $_REQUEST['newStorage']['Path'] : ($storage->{'Path'} ?? '');
    # Saving a storage area that failed to load would insert a new one.
    if (validCardinal($_REQUEST['id']) and !$storage->Id()) {
      ZM\Warning('Storage area '.validCardinal($_REQUEST['id']).' not found');
      $error_message .= 'Storage area not found<br/>';
      $changes = array();
    } else if (!is_string($name) or (trim($name) === '')) {
      ZM\Warning('Refusing to save storage area with an empty Name');
      $error_message .= 'Storage area Name is required<br/>';
      $changes = array();
    } else if (!is_string($path) or (trim($path) === '')) {
      ZM\Warning('Refusing to save storage area with an empty Path');
      $error_message .= 'Storage area Path is required<br/>';
      $changes = array();
    } else {
      $changes = $storage->changes($_REQUEST['newStorage']);

      # Storage::changes() filters Name and Path down to letters, numbers and - . ( ) : / space.
      # If that strips every character, Storage::Name()/Path() ignore the empty value and the
      # save silently does nothing (or falls back to the default events path for a new area).
      if ((array_key_exists('Name', $changes) and ($changes['Name'] === ''))
        or (array_key_exists('Path', $changes) and ($changes['Path'] === ''))) {
        ZM\Warning('Refusing to save storage area whose Name or Path is empty after filtering');
        $error_message .= 'Storage area Name and Path may only contain letters, numbers and - . ( ) : / space<br/>';
        $changes = array();
      }
    }
  }

  if (count($changes)) {
    if ($storage->save($changes)) {
      ZM\AuditAction(($_REQUEST['id'] ? 'update' : 'create'), 'storage', $storage->Id(), 'Changed: '.implode(', ', array_keys($changes)));
    } else {
      $error_message .= $storage->get_last_error();
    } // end if successful save
  }
  if (!$error_message) {
    // there is no view=storage, so need to redirect somewhere useful
    $redirect = '?view=options&tab=storage';
    $refreshParent = true;
  } else {
    // The modal form posts view=storage, which has no view file. Render the
    // options page instead so that the error is shown.
    $view = 'options';
    $_REQUEST['tab'] = 'storage';
  }
} else {
  ZM\Error("Unknown action $action in saving Storage");
}
?>
