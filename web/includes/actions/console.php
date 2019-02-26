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

if ( $action == 'delete' ) {
  if ( ! canEdit('Monitors') ) {
    ZM\Warning('No permission to delete monitors');
    return;
  }

  if ( isset($_REQUEST['markMids']) && !$user['MonitorIds'] ) {
    require_once('includes/Monitor.php');
    foreach ( $_REQUEST['markMids'] as $markMid ) {
      if ( canEdit('Monitors', $markMid) ) {
        // This could be faster as a select all
        if ( $monitor = dbFetchOne('SELECT * FROM Monitors WHERE Id = ?', NULL, array($markMid)) ) {
          $Monitor = new ZM\Monitor($monitor);
          $Monitor->delete();
        } // end if monitor found in db
      } // end if canedit this monitor
    } // end foreach monitor in MarkMid
  } // markMids is set and we aren't limited to specific monitors
} // end if action == Delete
?>
