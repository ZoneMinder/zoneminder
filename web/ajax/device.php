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
  ajaxError('Insufficient permissions for user '.validHtmlStr($user->Username()));
  return;
}

$action = validStr($_REQUEST['action'] ?? '');
if ( $action == 'device' ) {
  if ( !empty($_REQUEST['command']) ) {
    $status = setDeviceStatusX10($_REQUEST['key'], strtolower($_REQUEST['command']));
    if ( $status == 'unknown' ) {
      ajaxError('No response from X10 device '.validHtmlStr($_REQUEST['key']));
    }
  } else if ( isset($_REQUEST['newDevice']) ) {
    if ( !is_array($_REQUEST['newDevice'])
      || !isset($_REQUEST['newDevice']['Name']) || !is_string($_REQUEST['newDevice']['Name'])
      || !isset($_REQUEST['newDevice']['KeyString']) || !is_string($_REQUEST['newDevice']['KeyString']) ) {
      ajaxError('Invalid device');
      return;
    }
    $failed = false;
    if ( isset($_REQUEST['did']) && $_REQUEST['did'] ) {
      ZM\Warning('did value is: '.$_REQUEST['did']);
      ZM\Warning('newDevice array value is: '.print_r($_REQUEST['newDevice'],true));
      if ( dbQuery('UPDATE Devices SET Name=?, KeyString=? WHERE Id=?',
        array($_REQUEST['newDevice']['Name'], $_REQUEST['newDevice']['KeyString'], $_REQUEST['did']) ) === null ) $failed = true;
    } else {

      if ( dbQuery('INSERT INTO Devices SET Name=?, KeyString=?',
        array($_REQUEST['newDevice']['Name'], $_REQUEST['newDevice']['KeyString']) ) === null ) $failed = true;
    }
    if ( $failed ) {
      ajaxError('Failed to save device: '.dbLastError());
      return;
    }
  }
  ajaxResponse();
} else {
  ajaxError('Unrecognised action '.validHtmlStr($action));
} // end if action

?>
