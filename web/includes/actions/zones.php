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
require_once('includes/Zone.php');
global $error_message;
global $redirect;

if ($action == 'delete') {
  if (isset($_REQUEST['markZids'])) {
    $monitors_to_restart = array();
    foreach ($_REQUEST['markZids'] as $markZid) {
      $zone = new ZM\Zone(validCardinal($markZid));
      if (!$zone->Id()) {
        $error_message .= 'Zone not found for id ' . $markZid.'<br/>';
        continue;
      }
      $monitor = $zone->Monitor();
      if (!$monitor->canEdit()) {
        $error_message .= 'You do not have permission to edit zones for monitor ' . $monitor->Name().'.<br/>';
        continue;
      }
      # Could use true but store the object instead for easy access later
      $monitors_to_restart[] = $monitor;
      $error_message .= $zone->delete();
    } # end foreach Zone

    if (daemonCheck()) {
      foreach ($monitors_to_restart as $monitor) {
        if ($monitor->Type() != 'WebSite') {
          $monitor->zmcControl('restart');
        }
      }
    }
    $refreshParent = true;
    if (!$error_message)
      $redirect = '?view=zones'.(isset($_REQUEST['mids']) ? '&'.implode('&', array_map(function($mid){ return 'mids[]='.$mid; }, $_REQUEST['mids'])): '');
  } else {
    $error_message .= 'No Zones marked for deletion.<br/>';
  } // end if isset($_REQUEST['markZids'])
} // end if action 

?>
