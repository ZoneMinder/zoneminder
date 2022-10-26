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
if (!canEdit('Monitors')) {
  ZM\Warning('Monitor actions require Monitors Permissions');
  return;
}

global $error_message;

if ($action == 'save') {
  $mid = 0;
  if (!empty($_REQUEST['mid'])) {
    $mid = validInt($_REQUEST['mid']);
    if (!canEdit('Monitors', $mid)) {
      ZM\Warning('You do not have permission to edit this monitor');
      return;
    }
    if (ZM_OPT_X10) {
      $x10Monitor = dbFetchOne('SELECT * FROM TriggersX10 WHERE MonitorId=?', NULL, array($mid));
      if (!$x10Monitor) $x10Monitor = array();
    }
  } else {
    if ($user['MonitorIds']) {
      ZM\Warning('You are restricted to certain monitors so cannot add a new one.');
      return;
    }
    if (ZM_OPT_X10) {
      $x10Monitor = array();
    }
  }

  # For convenience
  $newMonitor = $_REQUEST['newMonitor'];

  $monitor = new ZM\Monitor($mid);

  // Define a field type for anything that's not simple text equivalent
  $types = array(
      'Triggers' => array(),
      'Controllable' => 0,
      'TrackMotion' => 0,
      'ModectDuringPTZ' =>  0,
      'Enabled' => 0,
      'DecodingEnabled' => 0,
      'Exif' => 0,
      'RTSPDescribe' => 0,
      'V4LMultiBuffer'  => '',
      'RecordAudio' => 0,
      'Method' => 'raw',
      'GroupIds'  =>  array(),
      'LinkedMonitors'  => array(),
      'RTSPServer' => 0
      );

  # Checkboxes don't return an element in the POST data, so won't be present in newMonitor.
  # So force a value for these fields
  foreach ($types as $field => $value) {
    if (!isset($newMonitor[$field])) {
      $newMonitor[$field] = $value;
    }
  } # end foreach type

  if ($newMonitor['ServerId'] == 'auto') {
    $newMonitor['ServerId'] = dbFetchOne(
      'SELECT Id FROM Servers WHERE Status=\'Running\' ORDER BY FreeMem DESC, CpuLoad ASC LIMIT 1', 'Id');
    ZM\Debug('Auto selecting server: Got ' . $newMonitor['ServerId']);
    if ((!$newMonitor['ServerId']) and defined('ZM_SERVER_ID') ) {
      $newMonitor['ServerId'] = ZM_SERVER_ID;
      ZM\Debug('Auto selecting server to '.ZM_SERVER_ID);
    }
  }

  $changes = $monitor->changes($newMonitor);
  $restart = false;

  if (count($changes)) {
    // monitor->Id() has a value when the db record exists
    if ($monitor->Id()) {

      # If we change anything that changes the shared mem size, zma can complain.  So let's stop first.
      if ($monitor->Type() != 'WebSite') {
        $monitor->zmcControl('stop');
        if ($monitor->Controllable()) {
          $monitor->sendControlCommand('stop');
        }
      }

      $oldMonitor = clone $monitor;

      if ($monitor->save($changes)) {
        # Leave old symlinks on old storage areas, as old events will still be there. Only delete the link if the name has changed
        if (isset($changes['Name'])) {
          $link_path = $oldMonitor->Storage()->Path().'/'.basename($oldMonitor->Name());
          if (file_exists($link_path)) {
            ZM\Debug("Deleting old link  ".$link_path);
            unlink($link_path);
          } else {
            ZM\Debug("Old link didn't exist at ".$link_path);
          }
        }

        if (isset($changes['Width']) || isset($changes['Height'])) {
          $newW = $newMonitor['Width'];
          $newH = $newMonitor['Height'];

          $zones = dbFetchAll('SELECT * FROM Zones WHERE MonitorId=?', NULL, array($mid));

          if ( ($newW == $oldMonitor->Height()) and ($newH == $oldMonitor->Width()) ) {
            foreach ( $zones as $zone ) {
              $newZone = $zone;
              # Rotation, no change to area etc just swap the coords
              $newZone = $zone;
              $points = coordsToPoints($zone['Coords']);
              for ( $i = 0; $i < count($points); $i++ ) {
                $x = $points[$i]['x'];
                $points[$i]['x'] = $points[$i]['y'];
                $points[$i]['y'] = $x;

                if ( $points[$i]['x'] > ($newW-1) ) {
                  ZM\Warning("Correcting x {$points[$i]['x']} > $newW of zone {$newZone['Name']} as it extends outside the new dimensions");
                  $points[$i]['x'] = ($newW-1);
                }
                if ( $points[$i]['y'] > ($newH-1) ) {
                  ZM\Warning("Correcting y {$points[$i]['y']} $newH of zone {$newZone['Name']} as it extends outside the new dimensions");
                  $points[$i]['y'] = ($newH-1);
                }
              }

              $newZone['Coords'] = pointsToCoords($points);
              $changes = getFormChanges($zone, $newZone, $types);

              if ( count($changes) ) {
                dbQuery('UPDATE Zones SET '.implode(', ', $changes).' WHERE MonitorId=? AND Id=?',
                  array($mid, $zone['Id']));
              }
            } # end foreach zone
          } else {
            $newA = $newW * $newH;
            $oldA = $oldMonitor->Width() * $oldMonitor->Height();

            foreach ( $zones as $zone ) {
              $newZone = $zone;
              $points = coordsToPoints($zone['Coords']);
              for ( $i = 0; $i < count($points); $i++ ) {
                $points[$i]['x'] = intval(($points[$i]['x']*($newW-1))/($oldMonitor->Width()-1));
                $points[$i]['y'] = intval(($points[$i]['y']*($newH-1))/($oldMonitor->Height()-1));
                if ( $points[$i]['x'] > ($newW-1) ) {
                  ZM\Warning("Correcting x of zone {$newZone['Name']} as it extends outside the new dimensions");
                  $points[$i]['x'] = ($newW-1);
                }
                if ( $points[$i]['y'] > ($newH-1) ) {
                  ZM\Warning("Correcting y of zone {$newZone['Name']} as it extends outside the new dimensions");
                  $points[$i]['y'] = ($newH-1);
                }
              }
              $newZone['Coords'] = pointsToCoords($points);
              $newZone['Area'] = intval(round(($zone['Area']*$newA)/$oldA));
              $newZone['MinAlarmPixels'] = intval(round(($newZone['MinAlarmPixels']*$newA)/$oldA));
              $newZone['MaxAlarmPixels'] = intval(round(($newZone['MaxAlarmPixels']*$newA)/$oldA));
              $newZone['MinFilterPixels'] = intval(round(($newZone['MinFilterPixels']*$newA)/$oldA));
              $newZone['MaxFilterPixels'] = intval(round(($newZone['MaxFilterPixels']*$newA)/$oldA));
              $newZone['MinBlobPixels'] = intval(round(($newZone['MinBlobPixels']*$newA)/$oldA));
              $newZone['MaxBlobPixels'] = intval(round(($newZone['MaxBlobPixels']*$newA)/$oldA));

              $changes = getFormChanges($zone, $newZone, $types);

              if ( count($changes) ) {
                dbQuery('UPDATE Zones SET '.implode(', ', $changes).' WHERE MonitorId=? AND Id=?',
                  array($mid, $zone['Id']));
              }
            } // end foreach zone
          } // end if rotation or just size change
        } // end if changes in width or height
      } else {
        $error_message .= $monitor->get_last_error();
      } // end if successful save
      $restart = true;
    } else { // new monitor
      // Can only create new monitors if we are not restricted to specific monitors
# FIXME This is actually a race condition. Should lock the table.
      $maxSeq = dbFetchOne('SELECT MAX(Sequence) AS MaxSequence FROM Monitors', 'MaxSequence');
      $changes['Sequence'] = $maxSeq+1;
      if ( $mid ) $changes['Id'] = $mid; # mid specified in request, doesn't exist in db, will re-use slot

      if ( $monitor->insert($changes) ) {
        $mid = $monitor->Id();
        $zoneArea = $newMonitor['Width'] * $newMonitor['Height'];
        dbQuery("INSERT INTO Zones SET MonitorId = ?, Name = 'All', Type = 'Active', Units = 'Percent', NumCoords = 4, Coords = ?, Area=?, AlarmRGB = 0xff0000, CheckMethod = 'Blobs', MinPixelThreshold = 25, MinAlarmPixels=?, MaxAlarmPixels=?, FilterX = 3, FilterY = 3, MinFilterPixels=?, MaxFilterPixels=?, MinBlobPixels=?, MinBlobs = 1", array( $mid,
              sprintf( '%d,%d %d,%d %d,%d %d,%d', 0, 0,
                $newMonitor['Width']-1,
                0,
                $newMonitor['Width']-1,
                $newMonitor['Height']-1,
                0,
                $newMonitor['Height']-1),
              $zoneArea,
              intval(($zoneArea*3)/100),
              intval(($zoneArea*75)/100),
              intval(($zoneArea*3)/100),
              intval(($zoneArea*75)/100),
              intval(($zoneArea*2)/100)
              ));
      } else {
        $error_message .= $monitor->get_last_error();
        ZM\Error('Error saving new Monitor: '.$error_message);
        return;
      }
    }

    $Storage = $monitor->Storage();
    $mid_dir = $Storage->Path().'/'.$mid;
    if (!file_exists($mid_dir)) {
      if (!@mkdir($mid_dir, 0755)) {
        ZM\Error('Unable to mkdir '.$Storage->Path().'/'.$mid);
      }
    }

    $saferName = basename($newMonitor['Name']);
    $link_path = $Storage->Path().'/'.$saferName;
    if (!@symlink($mid, $link_path)) {
      if (!(file_exists($link_path) and is_link($link_path))) {
        ZM\Warning('Unable to symlink ' . $Storage->Path().'/'.$mid . ' to ' . $link_path);
      }
    }

    if ( isset($changes['GroupIds']) ) {
      dbQuery('DELETE FROM Groups_Monitors WHERE MonitorId=?', array($mid));
      foreach ( $changes['GroupIds'] as $group_id ) {
        dbQuery('INSERT INTO Groups_Monitors (GroupId, MonitorId) VALUES (?,?)', array($group_id, $mid));
      }
    } // end if there has been a change of groups

    $restart = true;
  } else {
    ZM\Debug('No action due to no changes to Monitor');
  } # end if count(changes)

  if ( !$mid ) {
    ZM\Error("We should have a mid by now.  Something went wrong.");
    return;
  }

  if ( ZM_OPT_X10 ) {
    $x10Changes = getFormChanges($x10Monitor, $_REQUEST['newX10Monitor']);

    if ( count($x10Changes) ) {
      if ( $x10Monitor && isset($_REQUEST['newX10Monitor']) ) {
        dbQuery('UPDATE TriggersX10 SET '.implode(', ', $x10Changes).' WHERE MonitorId=?', array($mid));
      } elseif ( !$user['MonitorIds'] ) {
        if ( !$x10Monitor ) {
          dbQuery('INSERT INTO TriggersX10 SET MonitorId = ?, '.implode(', ', $x10Changes), array($mid));
        } else {
          dbQuery('DELETE FROM TriggersX10 WHERE MonitorId = ?', array($mid));
        }
      }
      $restart = true;
    } # end if has x10Changes
  } # end if ZM_OPT_X10

  if ( $restart ) {
    if ( $monitor->Function() != 'None' and $monitor->Type() != 'WebSite' ) {
      $monitor->zmcControl('start');

      if ( $monitor->Controllable() ) {
        $monitor->sendControlCommand('start');
      }
    }
    // really should thump zmwatch and maybe zmtrigger too.
    //daemonControl( 'restart', 'zmwatch.pl' );
  } // end if restart
  $redirect = '?view=console';
} else {
  ZM\Warning("Unknown action $action in Monitor");
} // end if action == Delete
?>
