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
if ( !empty($_REQUEST['mid']) && canEdit('Monitors', $_REQUEST['mid']) ) {
  $mid = validInt($_REQUEST['mid']);
  if ( $action == 'zone' && isset($_REQUEST['zid']) ) {
    $zid = validInt($_REQUEST['zid']);
    $monitor = new ZM\Monitor($mid);

    if ( !empty($zid) ) {
      $zone = dbFetchOne('SELECT * FROM Zones WHERE MonitorId=? AND Id=?', NULL, array($mid, $zid));
    } else {
      $zone = array();
    }

    if ( $_REQUEST['newZone']['Units'] == 'Percent' ) {
      $_REQUEST['newZone']['MinAlarmPixels'] = intval(($_REQUEST['newZone']['MinAlarmPixels']*$_REQUEST['newZone']['Area'])/100);
      $_REQUEST['newZone']['MaxAlarmPixels'] = intval(($_REQUEST['newZone']['MaxAlarmPixels']*$_REQUEST['newZone']['Area'])/100);
      if ( isset($_REQUEST['newZone']['MinFilterPixels']) )
        $_REQUEST['newZone']['MinFilterPixels'] = intval(($_REQUEST['newZone']['MinFilterPixels']*$_REQUEST['newZone']['Area'])/100);
      if ( isset($_REQUEST['newZone']['MaxFilterPixels']) )
        $_REQUEST['newZone']['MaxFilterPixels'] = intval(($_REQUEST['newZone']['MaxFilterPixels']*$_REQUEST['newZone']['Area'])/100);
      if ( isset($_REQUEST['newZone']['MinBlobPixels']) )
        $_REQUEST['newZone']['MinBlobPixels'] = intval(($_REQUEST['newZone']['MinBlobPixels']*$_REQUEST['newZone']['Area'])/100);
      if ( isset($_REQUEST['newZone']['MaxBlobPixels']) )
        $_REQUEST['newZone']['MaxBlobPixels'] = intval(($_REQUEST['newZone']['MaxBlobPixels']*$_REQUEST['newZone']['Area'])/100);
    }

    unset($_REQUEST['newZone']['Points']);

    # convert these fields to integer e.g. NULL -> 0
    $types = array(
        'OverloadFrames' => 'integer',
        'ExtendAlarmFrames' => 'integer',
        );

    $changes = getFormChanges($zone, $_REQUEST['newZone'], $types);

    if ( count($changes) ) {
      if ( $zid > 0 ) {
        dbQuery('UPDATE Zones SET '.implode(', ', $changes).' WHERE MonitorId=? AND Id=?', array($mid, $zid));
      } else {
        dbQuery('INSERT INTO Zones SET MonitorId=?, '.implode(', ', $changes), array($mid));
      }
      if ( daemonCheck() && ($monitor->Type() != 'WebSite') ) {
        if ( $_REQUEST['newZone']['Type'] == 'Privacy' ) {
          $monitor->zmaControl('stop');
          $monitor->zmcControl('restart');
          $monitor->zmaControl('start');
        } else {
          $monitor->zmaControl('restart');
        }
      }
      if ( ($_REQUEST['newZone']['Type'] == 'Privacy') && $monitor->Controllable() ) {
        $monitor->sendControlCommand('quit');
      }
      $refreshParent = true;
    } // end if changes
    $view = 'none';
  } // end if action 
} // end if $mid and canEdit($mid)
?>
