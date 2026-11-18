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

require_once('includes/Zone.php');

// Monitor edit actions, require a monitor id and edit permissions for that monitor
if ( !empty($_REQUEST['mid']) && canEdit('Monitors', $_REQUEST['mid']) ) {
  $mid = validInt($_REQUEST['mid']);
  if ( ($action == 'zone') && isset($_REQUEST['zid']) ) {
    $zid = validInt($_REQUEST['zid']);
    $monitor = new ZM\Monitor($mid);

    if ( !empty($zid) ) {
      $zone = dbFetchOne('SELECT * FROM Zones WHERE MonitorId=? AND Id=?', NULL, array($mid, $zid));
    } else {
      $zone = array();
    }

    // Ensure threshold values are always stored as percentages of zone area (0-100).
    // Values > 100 are pixel counts; convert them using the zone's pixel area.
    $thresholdFields = array('MinAlarmPixels', 'MaxAlarmPixels', 'MinFilterPixels', 'MaxFilterPixels', 'MinBlobPixels', 'MaxBlobPixels');
    if (isset($_REQUEST['newZone']['Coords'])) {
      # coordsToPoints() prints a message for a string with no points in it
      ob_start();
      $points = coordsToPoints($_REQUEST['newZone']['Coords']);
      ob_end_clean();
      if (!$points || count($points) < 3 || count($points) != count(preg_split('/\s+/', trim($_REQUEST['newZone']['Coords'])))) {
        ZM\Warning('Not saving zone '.$zid.' for monitor '.$mid.', Coords are not a polygon: '.$_REQUEST['newZone']['Coords']);
        $redirect = '?view=zones&mid='.$mid;
        return;
      }
      # Keep Coords, NumCoords and Area in step with each other
      $_REQUEST['newZone']['Coords'] = pointsToCoords($points);
      $_REQUEST['newZone']['NumCoords'] = count($points);
      $zoneArea = getPolyArea($points);
      $_REQUEST['newZone']['Area'] = $zoneArea;
      if ($points) {
        $zonePixelArea = $zoneArea / 10000.0 * $monitor->ViewWidth() * $monitor->ViewHeight();
        if ($zonePixelArea > 0) {
          foreach ($thresholdFields as $field) {
            if (isset($_REQUEST['newZone'][$field]) && $_REQUEST['newZone'][$field] !== '' && floatval($_REQUEST['newZone'][$field]) > 100) {
              $_REQUEST['newZone'][$field] = round(floatval($_REQUEST['newZone'][$field]) / $zonePixelArea * 100, 2);
            }
          }
        }
      }
    }
    foreach ($thresholdFields as $field) {
      if (isset($_REQUEST['newZone'][$field]) && $_REQUEST['newZone'][$field] !== '') {
        $_REQUEST['newZone'][$field] = max(0, min(100, floatval($_REQUEST['newZone'][$field])));
      }
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
      ZM\AuditAction(($zid > 0 ? 'update' : 'create'), 'zone', $zid, 'MonitorId: '.$mid);
      if ( daemonCheck() && ($monitor->Type() != 'WebSite') ) {
        $monitor->zmcControl('reload');
      }
      if ( ($_REQUEST['newZone']['Type'] == 'Privacy') && $monitor->Controllable() ) {
        $monitor->sendControlCommand('quit');
      }
    } // end if changes
    # HTTP_REFERER will typically be ?view=zone so no good.
    # if a referer is passed in $_REQUEST then use it otherwise go to ?view=zones.
    # The zone form always posts REFERER, empty when the form had no Referer
    # (a typed URL or bookmark); an empty $redirect renders the zone view with
    # this action's $zone, which has no Points. The form fills it from the
    # Referer its page was loaded with, which can be another site, so only go
    # back to it when it is on this server.
    $redirect = localRedirectUrl(isset($_REQUEST['REFERER']) ? $_REQUEST['REFERER'] : '', '?view=zones&mid='.$mid);
  } // end if action 

  $builtInPresets = ZM\Zone::BUILT_IN_PRESETS;
  $builtInCount = count($builtInPresets);

  if ( $action == 'save_preset' ) {
    $presetName = isset($_REQUEST['presetName']) ? trim($_REQUEST['presetName']) : '';

    // Prevent using names of built-in presets (English or localized)
    $isBuiltInName = false;
    foreach ( $builtInPresets as $sysName => $langKey ) {
      if ( strcasecmp($presetName, $sysName) === 0 || strcasecmp($presetName, translate($langKey)) === 0 ) {
        $isBuiltInName = true;
        break;
      }
    }

    if ( $presetName === '' || $isBuiltInName ) {
      ajaxError(translate('ZoneInvalidPresetName'));
    }

    $presetData = isset($_REQUEST['newZone']) && is_array($_REQUEST['newZone']) ? $_REQUEST['newZone'] : array();
    $thresholdFields = array('MinAlarmPixels', 'MaxAlarmPixels', 'MinFilterPixels', 'MaxFilterPixels', 'MinBlobPixels', 'MaxBlobPixels');
    foreach ( $thresholdFields as $field ) {
      if ( isset($presetData[$field]) && $presetData[$field] !== '' ) {
        $presetData[$field] = max(0, min(100, floatval($presetData[$field])));
      }
    }

    $presetFields = array(
      'CheckMethod', 'MinPixelThreshold', 'MaxPixelThreshold',
      'MinAlarmPixels', 'MaxAlarmPixels', 'FilterX', 'FilterY',
      'MinFilterPixels', 'MaxFilterPixels', 'MinBlobPixels', 'MaxBlobPixels',
      'MinBlobs', 'MaxBlobs', 'OverloadFrames', 'ExtendAlarmFrames'
    );

    $setClauses = array('Name = ?', 'Units = ?');
    $params = array($presetName, 'Percent');
    foreach ( $presetFields as $field ) {
      if ( isset($presetData[$field]) && $presetData[$field] !== '' ) {
        $setClauses[] = $field.' = ?';
        $params[] = $presetData[$field];
      } else {
        $setClauses[] = $field.' = NULL';
      }
    }

    $existing = dbFetchOne('SELECT Id, Name FROM ZonePresets WHERE Name = ?', NULL, array($presetName));
    if ( $existing ) {
      if ( $existing['Id'] <= $builtInCount || isset($builtInPresets[$existing['Name']]) ) {
        ajaxError(translate('ZoneCannotModifyBuiltInPreset'));
      }
      $presetId = $existing['Id'];
      $params[] = $presetId;
      dbQuery('UPDATE ZonePresets SET '.implode(', ', $setClauses).' WHERE Id = ?', $params);
      ZM\AuditAction('update', 'zone_preset', $presetId, 'Name: '.$presetName);
    } else {
      dbQuery('INSERT INTO ZonePresets SET '.implode(', ', $setClauses), $params);
      $presetId = dbInsertId();
      ZM\AuditAction('create', 'zone_preset', $presetId, 'Name: '.$presetName);
    }

    $savedPreset = dbFetchOne('SELECT *, Units-1 AS UnitsIndex, CheckMethod-1 AS CheckMethodIndex FROM ZonePresets WHERE Id = ?', NULL, array($presetId));
    $savedPreset['isBuiltIn'] = false;
    ajaxResponse(array('preset' => $savedPreset));
  } else if ( $action == 'delete_preset' ) {
    $presetId = isset($_REQUEST['presetId']) ? validInt($_REQUEST['presetId']) : 0;
    if ( $presetId <= 0 ) {
      ajaxError(translate('ZoneCannotDeleteBuiltInPreset'));
    }
    $target = dbFetchOne('SELECT Id, Name FROM ZonePresets WHERE Id = ?', NULL, array($presetId));
    if ( !$target || isset($builtInPresets[trim($target['Name'])]) ) {
      ajaxError(translate('ZoneCannotDeleteBuiltInPreset'));
    }
    dbQuery('DELETE FROM ZonePresets WHERE Id = ?', array($presetId));
    ZM\AuditAction('delete', 'zone_preset', $presetId, 'Name: '.$target['Name']);
    ajaxResponse(array('deletedId' => $presetId));
  }
} // end if $mid and canEdit($mid)
?>
