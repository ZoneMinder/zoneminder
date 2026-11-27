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
  # Compare resolved paths, so a trailing ".." can't climb out of the Storage area.
  $path = realpath($path);
  $is_ok_path = false;
  if ($path !== false) {
    foreach (ZM\Storage::find() as $storage) {
      # Must be the Storage path or below it: "/events2" is not below "/events".
      $base = realpath($storage->Path());
      if ($base !== false and ($path === $base or strpos($path, rtrim($base, '/').'/') === 0)) {
        $is_ok_path = true;
      }
    }
  }
  $path_parts = pathinfo($path);

  # $is_ok_path was computed above but never consulted, so a path outside every
  # Storage area still reached unlink(). detaintPathAllowAbsolute() permits
  # absolute paths, so this is what keeps the delete inside a Storage area.
  if (!$is_ok_path) {
    $error_message .= 'Path is not valid. Path must be below a designated Storage area.<br/>';
    ZM\Warning("Refusing to delete files under '$path': not below a Storage area");
    return;
  }

  foreach ($_REQUEST['files'] as $file) {
    $full_path = $path.'/'.detaintPath($file);
    if (is_file($full_path)) {
      if (!@unlink($full_path)) {
        $error_message .= 'Unable to delete '.validHtmlStr($file).': '.validHtmlStr(ZM\lastErrorForLog()).'<br/>';
      }
    } else {
      ZM\Debug("$full_path is not a file");
      $error_message .= 'We do not support deleting directories at this time.<br/>';
    }
  }
} // end if object == filter
?>
