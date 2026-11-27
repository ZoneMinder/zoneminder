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

require_once('includes/Monitor.php');
require_once('includes/Zone.php');

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
    if (!canCreate('Monitors')) {
      ZM\Warning('Monitor actions require Monitors Create Permissions');
      return;
    }
    if ($user->unviewableMonitorIds()) {
      ZM\Warning('You are restricted to certain monitors so cannot add a new one.');
      return;
    }
    if (ZM_OPT_X10) {
      $x10Monitor = array();
    }
    if (!empty($_REQUEST['newMonitor']['Id'])) { 
      // Reuse existing but deleted monitor
      $mid = validCardinal($_REQUEST['newMonitor']['Id']);
    }
  }

  # For convenience
  $newMonitor = $_REQUEST['newMonitor'];

  # Validate Device path to prevent command injection (CVE-worthy).
  # Only Local monitors pass Device to a shell; for other Types the field
  # is unused and may legitimately hold legacy values (e.g. an RTSP URL).
  if (!empty($newMonitor['Device']) and isset($newMonitor['Type']) and $newMonitor['Type'] == 'Local') {
    $newMonitor['Device'] = validDevicePath($newMonitor['Device']);
    if ($newMonitor['Device'] === '') {
      $error_message .= 'Invalid device path. Must be a valid /dev/ path (e.g. /dev/video0).</br>';
      return;
    }
  }

  if (!$newMonitor['ManufacturerId'] and ($newMonitor['Manufacturer'] != '')) {
    # Need to add a new Manufacturer entry
    $newManufacturer = ZM\Manufacturer::find_one(array('Name'=>$newMonitor['Manufacturer']));
    if (!$newManufacturer) {
      $newManufacturer = new ZM\Manufacturer();
      if (!$newManufacturer->save(array('Name'=>$newMonitor['Manufacturer']))) {
        $error_message .= "Error saving new Manufacturer: " . $newManufacturer->get_last_error().'</br>';
      }
    }
    $newMonitor['ManufacturerId'] = $newManufacturer->Id();
    unset($newMonitor['Manufacturer']);
  }

  if (!$newMonitor['ModelId'] and ($newMonitor['Model'] != '')) {
    # Need to add a new Model entry
    # Model names are only unique per manufacturer, so look under this one.
    $newModel = ZM\Model::find_one(array(
      'Name'=>$newMonitor['Model'],
      'ManufacturerId'=>$newMonitor['ManufacturerId'] ? $newMonitor['ManufacturerId'] : null
    ));
    if (!$newModel) {
      $newModel = new ZM\Model();
      if (!$newModel->save(array(
            'Name'=>$newMonitor['Model'],
            'ManufacturerId'=>$newMonitor['ManufacturerId']
      ))) {
        $error_message .= "Error saving new Model: " . $newModel->get_last_error().'</br>';
      }
    }
    $newMonitor['ModelId'] = $newModel->Id();
    unset($newMonitor['Model']);
  }

  $monitor = new ZM\Monitor($mid);

  # A new monitor may reuse the Id of a deleted one. A live monitor with that Id
  # would be overwritten by a user who may only create monitors.
  if (empty($_REQUEST['mid']) and $mid and $monitor->Id() and !$monitor->Deleted()) {
    $error_message .= 'A monitor with Id '.validInt($mid).' already exists.<br/>';
    ZM\Warning('Refused to create monitor '.$mid.': a monitor with that Id exists');
    return;
  }

  # Refuse an Ffmpeg source path with no protocol. ffmpeg then fails in
  # avformat_open_input with "Protocol not found" and zmdc restarts zmc in a
  # loop without pointing at the field. Absolute paths and ffmpeg's scheme-less
  # v4l2: form are valid, so allow those.
  $type = isset($newMonitor['Type']) ? $newMonitor['Type'] : $monitor->Type();
  if ($type == 'Ffmpeg') {
    $path = array_key_exists('Path', $newMonitor) ? $newMonitor['Path'] : $monitor->Path();
    if ($path !== null and $path !== '' and strpos($path, '://') === false and
        substr($path, 0, 1) !== '/' and stripos($path, 'v4l2:') !== 0) {
      $error_message .= translate('BadFfmpegPathProtocol').'</br>';
      ZM\Warning('Refused to save monitor '.$mid.': Source Path must start with a protocol such as rtsp://');
      return;
    }
  }

  // Define a field type for anything that's not simple text equivalent
  $types = monitorFormUncheckedDefaults();

  # Checkboxes don't return an element in the POST data, so won't be present in newMonitor.
  # So force a value for these fields.
  # Deleted is deliberately NOT in this list: its checkbox is an "undelete"
  # action, present only on a deleted monitor and only ever sending 0. Forcing
  # a default of 0 when it is absent undeleted the monitor on every save.
  foreach ($types as $field => $value) {
    if (!isset($newMonitor[$field])) {
      $newMonitor[$field] = $value;
    }
  } # end foreach type

  if (isset($newMonitor['ServerId']) and ($newMonitor['ServerId'] == 'auto')) {
    $newMonitor['ServerId'] = dbFetchOne(
      'SELECT Id FROM Servers WHERE Status=\'Running\' ORDER BY FreeMem DESC, CpuLoad ASC LIMIT 1', 'Id');
    ZM\Debug('Auto selecting server: Got ' . $newMonitor['ServerId']);
    if ((!$newMonitor['ServerId']) and defined('ZM_SERVER_ID')) {
      $newMonitor['ServerId'] = ZM_SERVER_ID;
      ZM\Debug('Auto selecting server to '.ZM_SERVER_ID);
    }
  }
  if (empty($newMonitor['Manufacturer']))
    unset($newMonitor['Manufacturer']);
  if (empty($newMonitor['Model']))
    unset($newMonitor['Model']);

  $refused = $monitor->commandFieldChangesNotAllowed($newMonitor);
  if (count($refused)) {
    $error_message .= 'Changing '.implode(', ', $refused).' requires System edit permission.<br/>';
    ZM\Warning('Refused to save monitor '.$mid.': '.implode(', ', $refused).' requires System edit permission');
    return;
  }

  $changes = $monitor->changes($newMonitor);
  ZM\Debug('Changes: '. print_r(ZM\redactForLog($changes), true));
  $restart = false;
  $zmcStopped = false;
  $actionsChanged = false;

  if (count($changes)) {
    // monitor->Id() has a value when the db record exists
    if ($monitor->Id()) {
      if ($monitor->Deleted() and empty($_REQUEST['mid'])) {
        # We are saving a new monitor with a specified Id and the Id is used in a deleted record.
        # Undelete it so that it is visible.
        # Editing an existing deleted monitor (mid in the request) must NOT
        # undelete it - that only happens via the explicit undelete checkbox,
        # which arrives as newMonitor[Deleted]=0 and so shows up in $changes.
        $monitor->Deleted(false);
      }

      # If we change anything that changes the shared mem size, zma can complain.  So let's stop first.
      if ($monitor->Type() != 'WebSite') {
        $monitor->zmcControl('stop');
        $zmcStopped = true;
        if ($monitor->Controllable()) {
          $monitor->sendControlCommand('stop');
        }
      }

      $oldMonitor = clone $monitor;

      if ($monitor->save($changes)) {
        # If the name has changed, it must also be changed in all storage areas.
        if (isset($changes['Name'])) {
          $saferName = basename($newMonitor['Name']);
          $oldLinkPath = $oldMonitor->Storage()->Path().'/'.basename($oldMonitor->Name());
          $oldLinkPathFound = false;
          require_once('includes/Storage.php');
          foreach (ZM\Storage::find() as $Storage) {
            # Let's remove old symlinks
            $storagePath = realpath($Storage->Path()) .'/';
            $dirStoragePath = opendir($storagePath);
            if($dirStoragePath === false) {
              ZM\Warning("Unable to check and delete old symlinks to monitor with ID=".$monitor->Id()." in the '".$Storage->Name()."' storage because the path to the storage is missing or unreadable.");
            } else {
              while (($file = readdir($dirStoragePath)) !== false) {
                $linkPath = $storagePath . $file;
                if (is_link($linkPath)) {
                  $absolutePath = realpath($linkPath);
                  if ($oldLinkPath == $linkPath) $oldLinkPathFound = true;
                  if ($absolutePath == $storagePath.$mid) {
                    ZM\Debug("Deleting old link in storage '" . $Storage->Name() . "' " . $linkPath);
                    unlink($linkPath);
                  }
                }
              } 
              closedir($dirStoragePath);
            }

            # Let's create new symlinks
            $dir = $Storage->Path().'/'.$mid;
            if (is_dir($dir) && (string)$saferName !== (string)$mid) { # Let's check if there is a folder with events in this storage
              $linkPath = $Storage->Path().'/'.$saferName;
              if (!is_link($linkPath) && !@symlink($mid, $linkPath)) {
                # It is necessary to check is_link() to avoid unnecessary warnings, since different repositories can have the same path and this is not prohibited by the configuration.
                ZM\Warning('Unable to symlink in storage "' . $Storage->Name() . '" ' . $dir . ' to ' . $linkPath);
              }
            }
          }
          if (!$oldLinkPathFound) {
            ZM\Debug("Old link at '" . $oldLinkPath . "' was not found in any of the storages");
          }
        }

        if (isset($changes['Width']) || isset($changes['Height'])) {
          $newW = $newMonitor['Width'];
          $newH = $newMonitor['Height'];

          $zones = dbFetchAll('SELECT * FROM Zones WHERE MonitorId=?', NULL, array($mid));

          // Zone coords are stored as percentages, so they don't need rescaling
          // on resolution change. Only handle rotation (swap x/y) and rescale
          // threshold pixel counts.
          $newA = $newW * $newH;
          $oldA = $oldMonitor->Width() * $oldMonitor->Height();

          // Coordinates without a decimal point are pixels to zmc. Turn those
          // zones into percentage zones at the old size first, otherwise the
          // rotation below writes them with decimals, which zmc reads as
          // percentages, and a resize leaves them covering the wrong area.
          foreach ( $zones as &$zone ) {
            if ( $oldA <= 0 or strpos($zone['Coords'], '.') !== false ) continue;
            $points = coordsToPoints($zone['Coords']);
            if ( !$points ) continue;
            convertPixelPointsToPercent($points, $oldMonitor->Width(), $oldMonitor->Height(), true);
            $zonePixelArea = getPolyArea($points) / 10000.0 * $oldA;
            $newZone = $zone;
            $newZone['Units'] = 'Percent';
            $newZone['Coords'] = pointsToCoords($points);
            convertZoneThresholdsToPercent($newZone, $zonePixelArea, true);
            $zoneChanges = getFormChanges($zone, $newZone, $types);
            if ( count($zoneChanges) ) {
              if ( !dbQuery('UPDATE Zones SET '.implode(', ', $zoneChanges).' WHERE MonitorId=? AND Id=?',
                array($mid, $zone['Id'])) ) {
                $error_message .= 'Error updating zone '.validInt($zone['Id']).' after resize: '.dbLastError().'<br/>';
              }
            }
            $zone = $newZone;
          }
          unset($zone);

          if ( ($newW == $oldMonitor->Height()) and ($newH == $oldMonitor->Width()) ) {
            // Rotation: swap x,y percentage coords
            foreach ( $zones as $zone ) {
              $newZone = $zone;
              $points = coordsToPoints($zone['Coords']);
              for ( $i = 0; $i < count($points); $i++ ) {
                $x = $points[$i]['x'];
                $points[$i]['x'] = $points[$i]['y'];
                $points[$i]['y'] = $x;
              }
              $newZone['Coords'] = pointsToCoords($points);
              $zoneChanges = getFormChanges($zone, $newZone, $types);

              if ( count($zoneChanges) ) {
                if ( !dbQuery('UPDATE Zones SET '.implode(', ', $zoneChanges).' WHERE MonitorId=? AND Id=?',
                  array($mid, $zone['Id'])) ) {
                  $error_message .= 'Error updating zone '.validInt($zone['Id']).' after resize: '.dbLastError().'<br/>';
                }
              }
            } # end foreach zone
          } else if ($oldA > 0 && $newA != $oldA) {
            // Non-rotation resize: coords stay the same (percentages),
            // but rescale threshold pixel counts by area ratio
            foreach ( $zones as $zone ) {
              // Percent zones carry thresholds that are a percentage of the
              // zone, so they do not change with the area.
              if ($zone['Units'] == 'Percent') continue;
              $newZone = $zone;
              $newZone['MinAlarmPixels'] = intval(round(($zone['MinAlarmPixels']*$newA)/$oldA));
              $newZone['MaxAlarmPixels'] = intval(round(($zone['MaxAlarmPixels']*$newA)/$oldA));
              $newZone['MinFilterPixels'] = intval(round(($zone['MinFilterPixels']*$newA)/$oldA));
              $newZone['MaxFilterPixels'] = intval(round(($zone['MaxFilterPixels']*$newA)/$oldA));
              $newZone['MinBlobPixels'] = intval(round(($zone['MinBlobPixels']*$newA)/$oldA));
              $newZone['MaxBlobPixels'] = intval(round(($zone['MaxBlobPixels']*$newA)/$oldA));

              $zoneChanges = getFormChanges($zone, $newZone, $types);

              if ( count($zoneChanges) ) {
                if ( !dbQuery('UPDATE Zones SET '.implode(', ', $zoneChanges).' WHERE MonitorId=? AND Id=?',
                  array($mid, $zone['Id'])) ) {
                  $error_message .= 'Error updating zone '.validInt($zone['Id']).' after resize: '.dbLastError().'<br/>';
                }
              }
            } // end foreach zone
          } // end if rotation or just size change
        } // end if changes in width or height
        ZM\AuditAction('update', 'monitor', $mid, 'Changed: '.implode(', ', array_keys($changes)));
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
        $dupId = empty($_REQUEST['dupId']) ? 0 : validCardinal($_REQUEST['dupId']);
        $dupZones = (!empty($_REQUEST['cloneZones']) and $dupId and canView('Monitors', $dupId))
          ? dbFetchAll('SELECT * FROM Zones WHERE MonitorId=?', NULL, array($dupId)) : array();
        foreach ($dupZones as $dupZone) {
          unset($dupZone['Id']);
          $dupZone['MonitorId'] = $mid;
          $zone = new ZM\Zone();
          if (!$zone->save($dupZone)) {
            $error_message .= $zone->get_last_error();
            ZM\Error('Error cloning zone: '.$error_message);
          }
        }
        // Zone coords and thresholds are stored as percentages (0-100)
        $zone = new ZM\Zone();
        if ($dupZones) {
          // zones were cloned, no default zone needed
        } else if (!$zone->save(['MonitorId'=>$monitor->Id(), 'Name'=>'All',
          'Units'=>'Percent',
          'Coords'=>'0.00,0.00 100.00,0.00 100.00,100.00 0.00,100.00',
          'Area'=>10000,
          'MinAlarmPixels'=>0.05,
          'MaxAlarmPixels'=>75,
          'MinFilterPixels'=>0.05,
          'MaxFilterPixels'=>75,
          'MinBlobPixels'=>0.05
        ])) {
          $error_message .= $zone->get_last_error();
          ZM\Error('Error adding zone:' . $error_message);
        }
        ZM\AuditAction('create', 'monitor', $mid, 'Name: '.($newMonitor['Name'] ?? ''));
      } else {
        $dbError = $monitor->get_last_error();
        $error_message .= 'Error saving new Monitor: '.($dbError ? $dbError : 'unknown database error').'<br/>';
        ZM\Error('Error saving new Monitor: '.$dbError);
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
    if (((string)$saferName !== (string)$mid) and !@symlink($mid, $link_path)) {
      if (!(file_exists($link_path) and is_link($link_path))) {
        ZM\Warning('Unable to symlink ' . $Storage->Path().'/'.$mid . ' to ' . $link_path);
      }
    }

    if ( isset($changes['GroupIds']) ) {
      if ( !dbQuery('DELETE FROM Groups_Monitors WHERE MonitorId=?', array($mid)) ) {
        $error_message .= 'Error clearing groups: '.dbLastError().'<br/>';
      } else {
        foreach ( $changes['GroupIds'] as $group_id ) {
          if ( !dbQuery('INSERT INTO Groups_Monitors (GroupId, MonitorId) VALUES (?,?)', array($group_id, $mid)) ) {
            $error_message .= 'Error adding monitor to group '.validInt($group_id).': '.dbLastError().'<br/>';
          }
        }
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
        if ( !dbQuery('UPDATE TriggersX10 SET '.implode(', ', $x10Changes).' WHERE MonitorId=?', array($mid)) ) {
          $error_message .= 'Error saving X10 settings: '.dbLastError().'<br/>';
        }
      } else if ( !$user->unviewableMonitorIds() ) {
        if ( !$x10Monitor ) {
          if ( !dbQuery('INSERT INTO TriggersX10 SET MonitorId = ?, '.implode(', ', $x10Changes), array($mid)) ) {
            $error_message .= 'Error saving X10 settings: '.dbLastError().'<br/>';
          }
        } else {
          if ( !dbQuery('DELETE FROM TriggersX10 WHERE MonitorId = ?', array($mid)) ) {
            $error_message .= 'Error saving X10 settings: '.dbLastError().'<br/>';
          }
        }
      }
      $restart = true;
    } # end if has x10Changes
  } # end if ZM_OPT_X10

  if ( isset($_REQUEST['newAction']) and is_array($_REQUEST['newAction']) ) {
    require_once('includes/MonitorAction.php');
    require_once('includes/CronTab.php');

    $submitted_ids = array();
    foreach ( $_REQUEST['newAction'] as $posted ) {
      if ( !is_array($posted) ) continue;

      # A row with no target is how the editor expresses "unused": the spare
      # blank row on every page, or an existing action the operator cleared.
      $target = (empty($posted['TargetMonitorId']) or is_array($posted['TargetMonitorId'])) ? 0 : validInt($posted['TargetMonitorId']);
      $action_id = (empty($posted['Id']) or is_array($posted['Id'])) ? 0 : validInt($posted['Id']);

      if ( !$target ) {
        if ( $action_id ) {
          dbQuery('DELETE FROM MonitorActions WHERE Id=? AND MonitorId=?', array($action_id, $mid));
          $actionsChanged = true;
        }
        continue;
      }

      # The same right ajax/control.php demands when an action is fired by hand.
      # Without it a user who may edit this monitor could store an action whose
      # alarms drive a light or speaker on a monitor they have no Control over.
      if ( !canView('Control', $target) ) {
        $error_message .= 'Ignoring action: no Control permission on monitor '.validInt($target).'.<br/>';
        continue;
      }

      # Never store an action the target cannot perform. The editor filters the
      # dropdown, but the request is not to be trusted. A disabled select is
      # not submitted at all, so the key may simply be absent.
      $action_type = (isset($posted['ActionType']) and is_string($posted['ActionType'])) ? $posted['ActionType'] : '';
      $trigger_on = (isset($posted['TriggerOn']) and is_string($posted['TriggerOn'])) ? $posted['TriggerOn'] : 'EventStart';

      $valid_triggers = array('EventStart', 'EventEnd', 'Alarm', 'AlarmEnd', 'Manual', 'Schedule');
      if ( !in_array($trigger_on, $valid_triggers, true) ) {
        $error_message .= 'Ignoring action: "'.validHtmlStr($trigger_on).'" is not a trigger.<br/>';
        continue;
      }
      $valid_action_types = array('LightOn', 'LightOff', 'IndicatorLightOn', 'IndicatorLightOff', 'AudioPlay', 'AudioStop');
      if ( !in_array($action_type, $valid_action_types, true) ) {
        $error_message .= 'Ignoring action: "'.validHtmlStr($action_type).'" is not an action type.<br/>';
        continue;
      }

      # The editor collects "Every <when> at <time>"; cron's five fields are
      # what gets stored and written to the crontab. A schedule it could not
      # draw comes back untouched in ScheduleRaw so editing a row does not
      # destroy one that was set another way.
      #
      # Built and validated here rather than trusted from the browser: this
      # string ends up in a crontab line, so the one check that matters has to
      # be on this side of the request.
      $schedule = '';
      if ( $trigger_on == 'Schedule' ) {
        if ( isset($posted['ScheduleRaw']) and is_string($posted['ScheduleRaw']) and (trim($posted['ScheduleRaw']) !== '') ) {
          $schedule = trim($posted['ScheduleRaw']);
        } else {
          $frequency = (isset($posted['ScheduleFrequency']) and is_string($posted['ScheduleFrequency'])) ? $posted['ScheduleFrequency'] : '';
          $time = (isset($posted['ScheduleTime']) and is_string($posted['ScheduleTime'])) ? trim($posted['ScheduleTime']) : '';
          # <input type="time"> gives HH:MM, and HH:MM:SS where seconds are on.
          if ( !preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $time, $hm) ) {
            $error_message .= 'Ignoring action: "'.validHtmlStr($time).
              '" is not a time of day.<br/>';
            continue;
          }
          $schedule = cronFromParts($frequency, intval($hm[1]), intval($hm[2]));
          if ($schedule === null) {
            $error_message .= 'Ignoring action: could not make a schedule from "'.
              validHtmlStr($frequency).'" at "'.validHtmlStr($time).'".<br/>';
            continue;
          }
        }

        if ( !cronScheduleIsValid($schedule) ) {
          $error_message .= 'Ignoring action: "'.validHtmlStr($schedule).
            '" is not a schedule this can run.<br/>';
          continue;
        }
      }

      $target_monitor = ZM\Monitor::find_one(array('Id'=>$target));
      $allowed = ZM\MonitorAction::typesForMonitor($target_monitor);
      if ( !in_array($action_type, $allowed) ) {
        ZM\Warning('Ignoring action: monitor '.$target.' cannot '.$action_type);
        continue;
      }

      $values = array(
        'MonitorId'       => $mid,
        'TargetMonitorId' => $target,
        'TriggerOn'       => $trigger_on,
        'Schedule'        => $schedule,
        'ActionType'      => $action_type,
        'AudioFile'       => (!isset($posted['AudioFile']) or is_array($posted['AudioFile']) or $posted['AudioFile'] === '')
                               ? null : validInt($posted['AudioFile']),
        'Enabled'         => empty($posted['Enabled']) ? 0 : 1,
      );

      $action = $action_id ?
        ZM\MonitorAction::find_one(array('Id'=>$action_id, 'MonitorId'=>$mid)) : new ZM\MonitorAction();
      if ( !$action ) $action = new ZM\MonitorAction();

      if ( $action->Id() ) {
        if ( count($action->changes($values)) ) $actionsChanged = true;
        if ( $action->save($values) ) {
          $submitted_ids[] = $action->Id();
        } else {
          $error_message .= 'Error saving monitor action: '.$action->get_last_error().'<br/>';
        }
      } else if ( $action->insert($values) ) {
        $actionsChanged = true;
        $submitted_ids[] = $action->Id();
      } else {
        $error_message .= 'Error saving monitor action: '.$action->get_last_error().'<br/>';
      }
    } # end foreach posted action

    # zmc only re-reads the action list on restart or reload. If nothing above
    # stopped it, start would find it running and do nothing.
    $restart = true;
    if ( $actionsChanged and !$zmcStopped and $monitor->Type() != 'WebSite' ) {
      $monitor->zmcControl('reload');
    }

    # cron owns the scheduled ones, so the crontab is rebuilt from the table
    # on every save. Doing it here rather than on a timer means the crontab
    # cannot drift from what the operator just agreed to.
    if ( cronRebuildScheduledActions() === false )
      $error_message .= 'Saved, but the crontab could not be updated, so '.
        'scheduled actions will not run. See the log for why.<br/>';
  } # end if newAction

  if ( $restart ) {
    if ( $monitor->Capturing() != 'None' and $monitor->Type() != 'WebSite' and !$monitor->Deleted()) {
      $monitor->zmcControl('start');

      if ( $monitor->Controllable() ) {
        $monitor->sendControlCommand('start');
      }
      $monitor->zmtrackControl('start');
    }
    // really should thump zmwatch and maybe zmtrigger too.
    //daemonControl( 'restart', 'zmwatch.pl' );
  } // end if restart
  if (!$error_message)
    $redirect = '?view=console';
} else {
  ZM\Warning("Unknown action $action in Monitor");
} // end if action == Delete
?>
