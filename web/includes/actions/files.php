<?php
//
// ZoneMinder web action file
// Copyright (C) 2023 ZoneMinder Inc
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
// Event scope actions, view permissions only required
if (!canView('Events')) {
	$error_message = 'You do not have permission to view Events.';
	ZM\Warning($error_message);
  return;
}

global $error_message;

if ($action == 'delete') {
  if (!canEdit('System')) {
    $error_message .= 'You do not have System Edit permissions, you cannot delete files.<br/>';
    return;
  } // end if canEdit(System)

  $path = (!empty($_REQUEST['path'])) ? detaintPathAllowAbsolute($_REQUEST['path']) : ZM_DIR_EVENTS;
  $is_ok_path = false;
  foreach (ZM\Storage::find() as $storage) {
    $rc = strstr($path, $storage->Path(), true);
    if ((false !== $rc) and ($rc == '')) {
      # Must be at the beginning
      $is_ok_path = true;
    }
  }
  $path_parts = pathinfo($path);

  foreach ($_REQUEST['files'] as $file) {
    $full_path = $path.'/'.detaintPath($file);
    if (is_file($full_path)) {
      unlink($full_path);
    } else {
      ZM\Debug("$full_path is not a file");
      $error_message .= 'We do not support deleting directories at this time.<br/>';
    }
  }
} // end if object == filter
?>
