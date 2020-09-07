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

// Device view actions
if ( !canEdit('Devices') ) {
  ZM\Warning('No devices permission in editing device');
  return;
} // end if !canEdit(Devices)

if ( $action == 'device' ) {
  if ( !empty($_REQUEST['command']) ) {
    setDeviceStatusX10($_REQUEST['key'], $_REQUEST['command']);
  } else if ( isset($_REQUEST['newDevice']) ) {
    if ( isset($_REQUEST['did']) ) {
      dbQuery('UPDATE Devices SET Name=?, KeyString=? WHERE Id=?',
        array($_REQUEST['newDevice']['Name'], $_REQUEST['newDevice']['KeyString'], $_REQUEST['did']) );
    } else {
      dbQuery('INSERT INTO Devices SET Name=?, KeyString=?',
        array($_REQUEST['newDevice']['Name'], $_REQUEST['newDevice']['KeyString']) );
    }
    $refreshParent = true;
    $view = 'none';
  }
} else {
  ZM\Error('Unknown action in device');
} // end if action

?>
