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
require_once('includes/ZonePreset.php');

// Monitor edit actions, require a monitor id and edit permissions for that monitor
if ( !empty($_REQUEST['mid']) && canEdit('Monitors', $_REQUEST['mid']) ) {
  $mid = validInt($_REQUEST['mid']);
  if ( ($action == 'zone') && isset($_REQUEST['zid']) ) {
    $zid = validInt($_REQUEST['zid']);
    $monitor = new ZM\Monitor($mid);
    if ( !$monitor->Id() or $monitor->Deleted() ) {
      $error_message .= 'Monitor not found.<br/>';
      return;
    }

    if ( !empty($zid) ) {
      $zone = dbFetchOne('SELECT * FROM Zones WHERE MonitorId=? AND Id=?', NULL, array($mid, $zid));
      if ( !$zone ) {
        $error_message .= 'Zone not found.<br/>';
        return;
      }
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
        // Stay on the zone editor and say why; redirecting to the list lost the
        // edit without a word.
        $error_message .= 'The zone was not saved: its points do not make a polygon of at least 3 points.<br/>';
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

    // The object box from the zone editor: "x1,y1 x2,y2" in percent, or empty
    // for none. Anything else is dropped rather than stored and echoed back.
    if ( isset($_REQUEST['newZone']['ObjectCoords']) and
      ( !is_string($_REQUEST['newZone']['ObjectCoords']) or !preg_match('/^\d{1,3}(\.\d{1,2})?,\d{1,3}(\.\d{1,2})? \d{1,3}(\.\d{1,2})?,\d{1,3}(\.\d{1,2})?$/', $_REQUEST['newZone']['ObjectCoords']) ) ) {
      $_REQUEST['newZone']['ObjectCoords'] = '';
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

  if ( $action == 'save_preset' ) {
    $presetName = isset($_REQUEST['presetName']) ? trim($_REQUEST['presetName']) : '';
    if ( $presetName === '' || ZM\ZonePreset::isBuiltInName($presetName) ) {
      ajaxError(translate('ZoneInvalidPresetName'));
    }

    $presetData = isset($_REQUEST['newZone']) && is_array($_REQUEST['newZone']) ? $_REQUEST['newZone'] : array();
    $presetFields = ZM\ZonePreset::getConfigurableFields();

    // Dynamically identify area threshold fields by the 'Pixels' suffix instead of hardcoding a field list,
    // normalize decimal separators, clamp values to [0..100], and format to 2 decimal places
    $cleanData = array(
      'Name'  => $presetName,
      'Units' => 'Percent',
    );
    foreach ( $presetFields as $field ) {
      if ( isset($presetData[$field]) && $presetData[$field] !== '' ) {
        $val = $presetData[$field];
        // Convert locale-specific comma decimal separators to dots and format to 2 decimal places
        // to match DECIMAL columns and preserve trailing zeros (e.g., 17.00) in the UI
        if ( substr($field, -6) === 'Pixels' ) {
          $val = str_replace(',', '.', (string)$val);
          $val = number_format(max(0, min(100, floatval($val))), 2, '.', '');
        }
        $cleanData[$field] = $val;
      } else if ( $field == 'OverloadFrames' || $field == 'ExtendAlarmFrames' ) {
        // NOT NULL columns, a blank input (a new zone's default) saves as 0 like the zone itself
        $cleanData[$field] = 0;
      } else {
        $cleanData[$field] = null;
      }
    }

    $preset = ZM\ZonePreset::find_one(array('Name' => $presetName));
    if ( $preset ) {
      if ( $preset->isBuiltIn() ) {
        ajaxError(translate('ZoneCannotModifyBuiltInPreset'));
      }
      if ( !$preset->save($cleanData) ) {
        ajaxError($preset->get_last_error());
      }
      ZM\AuditAction('update', 'zone_preset', $preset->Id(), 'Name: '.$presetName);
    } else {
      $cleanData['Type'] = 'Active';
      $preset = new ZM\ZonePreset();
      if ( !$preset->save($cleanData) ) {
        ajaxError($preset->get_last_error());
      }
      ZM\AuditAction('create', 'zone_preset', $preset->Id(), 'Name: '.$presetName);
    }

    ajaxResponse(array('preset' => $preset->toArray()));
  } else if ( $action == 'delete_preset' ) {
    $presetId = isset($_REQUEST['presetId']) ? validInt($_REQUEST['presetId']) : 0;
    if ( $presetId <= 0 ) {
      ajaxError(translate('ZoneCannotDeleteBuiltInPreset'));
    }
    $target = ZM\ZonePreset::find_one(array('Id' => $presetId));
    if ( !$target || $target->isBuiltIn() ) {
      ajaxError(translate('ZoneCannotDeleteBuiltInPreset'));
    }
    $targetName = $target->Name();
    $target->delete();
    ZM\AuditAction('delete', 'zone_preset', $presetId, 'Name: '.$targetName);
    ajaxResponse(array('deletedId' => $presetId));
  }
} // end if $mid and canEdit($mid)
?>
