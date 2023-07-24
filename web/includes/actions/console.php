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
        if ($monitor) $monitor->delete();
      } else {
        $error_message .= 'You do not have permission to delete monitor '.$markMid.'<br/>';
      } // end if canedit this monitor
    } // end foreach monitor in MarkMid
  } // markMids is set and we aren't limited to specific monitors
} // end if action == Delete
?>
