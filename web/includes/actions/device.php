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
    $newDevice = is_array($_REQUEST['newDevice']) ? $_REQUEST['newDevice'] : array();
    $saved = true;
    $name = $newDevice['Name'] ?? null;
    $keyString = $newDevice['KeyString'] ?? null;
    if ( !is_scalar($name) or !is_scalar($keyString) ) {
      $error_message .= 'A device needs a Name and a KeyString<br/>';
    } else if ( isset($_REQUEST['did']) && $_REQUEST['did'] ) {
      if ( !dbFetchOne('SELECT Id FROM Devices WHERE Id=?', 'Id', array($_REQUEST['did'])) ) {
        // Deleted in another tab: the UPDATE would match no row and still report a save
        $error_message .= 'Device not found<br/>';
      } else {
        $saved = (dbQuery('UPDATE Devices SET Name=?, KeyString=? WHERE Id=?',
          array($name, $keyString, $_REQUEST['did']) ) !== null);
      }
    } else {
      $saved = (dbQuery('INSERT INTO Devices SET Name=?, KeyString=?',
        array($name, $keyString) ) !== null);
    }
    if ( !$saved )
      $error_message .= 'Error saving device: '.htmlspecialchars(dbLastError()).'<br/>';
    if ( !$error_message ) {
      $redirect = '?view=devices';
    } else {
      // The modal posts view=device, which has no view file, so show the error on Devices.
      $view = 'devices';
    }
  }
} else {
  ZM\Error('Unknown action in device');
} // end if action

?>
