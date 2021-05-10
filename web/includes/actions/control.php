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

// Monitor control actions, require a monitor id and control view permissions for that monitor
if ( empty($_REQUEST['mid']) ) {
  ZM\Warning('Settings requires a monitor id');
  return;
}
if ( ! canView('Control', $_REQUEST['mid']) ) {
  ZM\Warning('Settings requires the Control permission');
  return;
}

require_once('includes/control_functions.php');
require_once('includes/Monitor.php');
$mid = validInt($_REQUEST['mid']);
if ( $action == 'control' ) {
  $monitor = new ZM\Monitor($mid);

  $ctrlCommand = buildControlCommand($monitor);
  $monitor->sendControlCommand($ctrlCommand);
  $view = 'none';
}
?>
