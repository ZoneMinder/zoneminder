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

// Monitor edit actions, monitor id derived, require edit permissions for that monitor
if (!canEdit('Monitors')) {
  ZM\Warning("Monitor actions require Monitors Permissions");
  return;
}

if ($action == 'save') {
  global $error_message;
  // Fields the form left as "Unchanged" because the monitors differ
  $newMonitor = array_filter($_REQUEST['newMonitor'], function($value) {
    return $value !== 'unchanged';
  });
  foreach ($_REQUEST['mids'] as $mid) {
    $mid = ValidInt($mid);
    if (!canEdit('Monitors', $mid)) {
      ZM\Warning('Cannot edit monitor '.$mid);
      continue;
    }
    $Monitor = new ZM\Monitor($mid);
    // Saving a monitor that failed to load would insert a new one, and a deleted one is not edited.
    if (!$Monitor->Id() or $Monitor->Deleted()) {
      ZM\Warning('Monitor '.$mid.' not found');
      $error_message .= 'Monitor '.validHtmlStr($mid).' no longer exists.<br/>';
      continue;
    }
    // Before stopping it, so a refused save leaves the monitor running.
    $refused = $Monitor->commandFieldChangesNotAllowed($newMonitor);
    if (count($refused)) {
      $error_message .= 'Changing '.implode(', ', $refused).' requires System edit permission.<br/>';
      continue;
    }
    // The bulk form sends every field for every monitor; a monitor it changes
    // nothing on is left alone instead of having its capture stopped and restarted.
    if (!count($Monitor->changes($newMonitor))) continue;
    if ($Monitor->Type() != 'WebSite') {
      $Monitor->zmcControl('stop');
    }
    if (!$Monitor->save($newMonitor)) {
      $error_message .= 'Error saving monitor: ' . $Monitor->get_last_error().'<br/>';
    } else {
      ZM\AuditAction('update', 'monitor', $mid, 'Bulk update: '.implode(', ', array_keys($newMonitor)));
    }
    if ($Monitor->Capturing() != 'None' && $Monitor->Type() != 'WebSite') {
      $Monitor->zmcControl('start');
      $Monitor->zmtrackControl('start');
    }
  } // end foreach mid

  if ($error_message) {
    $view = 'console';
  } else {
    $redirect = '?view=console';
  }
} else {
  ZM\Warning("Unknown action $action in Monitor");
} // end if action == Delete
?>
