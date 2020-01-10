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


// Monitor edit actions, require a monitor id and edit permissions for that monitor
if ( empty($_REQUEST['mid']) ) {
  ZM\Error('Must specify mid');
  return;
}
$mid = validInt($_REQUEST['mid']);
if ( !canEdit('Monitors', $mid) ) {
  ZM\Error("You do not have permission to edit monitor $mid");
  return;
}

if ( $action == 'function' ) {
  $monitor = dbFetchOne('SELECT * FROM Monitors WHERE Id=?', NULL, array($mid));

  $newFunction = validStr($_REQUEST['newFunction']);
  # Because we use a checkbox, it won't get passed in the request. So not being in _REQUEST means 0
  $newEnabled = ( !isset($_REQUEST['newEnabled']) or $_REQUEST['newEnabled'] != '1' ) ? '0' : '1';
  $oldFunction = $monitor['Function'];
  $oldEnabled = $monitor['Enabled'];
  if ( $newFunction != $oldFunction || $newEnabled != $oldEnabled ) {
    dbQuery('UPDATE Monitors SET `Function`=?, `Enabled`=? WHERE `Id`=?',
      array($newFunction, $newEnabled, $mid));

    $monitor['Function'] = $newFunction;
    $monitor['Enabled'] = $newEnabled;
    if ( daemonCheck() && ($monitor['Type'] != 'WebSite') ) {
      zmaControl($monitor, 'stop');
      zmcControl($monitor, ($newFunction != 'None') ? 'restart' : 'stop');
			if ( $newFunction != 'None' && $newFunction != 'NoDect' )
        zmaControl($monitor, 'start');
    }
    $refreshParent = true;
  } else {
    ZM\Logger::Debug('No change to function, not doing anything.');
  }
} // end if action 
$view = 'none';
$closePopup = true;
?>
