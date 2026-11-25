<?php
//
// ZoneMinder web action file, $Date$, $Revision$
// Copyright (C) 2001-2008 Philip Coombes
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

// Restart the daemons of each selected monitor the user may edit. Actions are
// dispatched from POST only, but check here too so the action cannot be run
// from a GET if it ever reaches this file another way.
function restartMonitors($monitor_ids) {
  global $error_message;

  if (!isset($_SERVER['REQUEST_METHOD']) or $_SERVER['REQUEST_METHOD'] != 'POST') {
    $error_message .= 'Restart requires a POST request.<br/>';
    return false;
  }

  foreach ((array)$monitor_ids as $monitor_id) {
    $monitor_id = validCardinal($monitor_id);
    if (!$monitor_id) {
      $error_message .= 'Invalid monitor id.<br/>';
      continue;
    }
    $monitor = ZM\Monitor::find_one(array('Id'=>$monitor_id));
    if (!$monitor) {
      $error_message .= 'Monitor '.validHtmlStr($monitor_id).' not found.<br/>';
      continue;
    }
    if (!$monitor->canEdit()) {
      $error_message .= 'You do not have permission to restart monitor '.validHtmlStr($monitor->Name()).'.<br/>';
      continue;
    }
    $monitor->zmcControl('restart');
    ZM\AuditAction('restart', 'monitor', $monitor->Id(), 'Name: '.$monitor->Name());
  }

  return true;
}

if ($action == 'restart') {
  if (!canEdit('Monitors')) {
    ZM\Warning('No permission to restart monitors');
    $error_message .= 'You do not have permission to restart monitors.<br/>';
  } else if (!isset($_REQUEST['markMids'])) {
    $error_message .= 'No monitors selected to restart.<br/>';
  } else {
    restartMonitors((array)$_REQUEST['markMids']);
  }
}

if ($action == 'delete') {
  if (!canEdit('Monitors')) {
    ZM\Warning('No permission to delete monitors');
    return;
  }

  if (isset($_REQUEST['markMids'])) {
    require_once('includes/Monitor.php');
    foreach ($_REQUEST['markMids'] as $markMid) {
      if (canEdit('Monitors', $markMid)) {
        $monitor = ZM\Monitor::find_one(['Id'=>$markMid]);
        if ($monitor) {
          if ($monitor->delete()) {
            ZM\AuditAction('delete', 'monitor', $markMid, 'Name: '.$monitor->Name());
          } else {
            // Do not record an audit entry for a delete that did not happen.
            $error_message .= 'Error deleting monitor '.$markMid.': '.
              $monitor->get_last_error().'<br/>';
          }
        }
      } else {
        $error_message .= 'You do not have permission to delete monitor '.$markMid.'<br/>';
      } // end if canedit this monitor
    } // end foreach monitor in MarkMid
  } // markMids is set and we aren't limited to specific monitors
} // end if action == Delete
?>
