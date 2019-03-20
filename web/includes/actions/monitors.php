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
if ( ! canEdit('Monitors') ) {
  ZM\Warning("Monitor actions require Monitors Permissions");
  return;
}

if ( $action == 'save' ) {
  foreach ( $_REQUEST['mids'] as $mid ) {
    $mid = ValidInt($mid);
    if ( ! canEdit('Monitors', $mid) ) {
      ZM\Warning("Cannot edit monitor $mid");
      continue;
    }
    $Monitor = new ZM\Monitor($mid);
    if ( $Monitor->Type() != 'WebSite' ) {
      $Monitor->zmaControl('stop');
      $Monitor->zmcControl('stop');
    }
    $Monitor->save($_REQUEST['newMonitor']);
    if ( $Monitor->Function() != 'None' && $Monitor->Type() != 'WebSite' ) {
      $Monitor->zmcControl('start');
      if ( $Monitor->Enabled() ) {
        $Monitor->zmaControl('start');
      }
    }
  } // end foreach mid
  $refreshParent = true;
  $view = 'none';
} else {
  ZM\Warning("Unknown action $action in Monitor");
} // end if action == Delete
?>
