<?php
//
// ZoneMinder web zone view file, $Date$, $Revision$
// Copyright (C) 2001-2008 Philip Coombes
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

$mid = empty($_REQUEST['mid']) ? 0 : validInt($_REQUEST['mid']);
if ( !($mid and canEdit('Monitors', $mid)) ) {
  $view = 'error';
  return;
}

$zid = (!empty($_REQUEST['zid'])) ? validInt($_REQUEST['zid']) : 0;
require_once('includes/Event.php');

$hicolor = '0x00ff00'; // Green

require_once('includes/ZonePreset.php');
$builtInPresets = ZM\ZonePreset::BUILT_IN_PRESETS;
$builtInPresetIds = array();
$presets = array();
$presetNames = array();
$presetNames[0] = translate('ChoosePreset');
foreach ( ZM\ZonePreset::find(array(), array('order' => 'Id ASC')) as $preset ) {
  if ( $preset->isBuiltIn() ) {
    $builtInPresetIds[] = (int)$preset->Id();
    $langKey = isset($builtInPresets[$preset->Name()]) ? $builtInPresets[$preset->Name()] : '';
    if ( $langKey ) {
      $presetNames[$preset->Id()] = array(
        'text'  => translate($langKey),
        'title' => translate($langKey.'Desc'),
      );
    } else {
      $presetNames[$preset->Id()] = $preset->Name();
    }
  } else {
    $presetNames[$preset->Id()] = $preset->Name();
  }
  $presets[$preset->Id()] = $preset;
}

$typeLabels = [
  'Active'     => [
    'text'  => translate('ZoneActive'),
    'title' => translate('ZoneActiveDesc'),
    'class' => 'zone-type-active',
  ],
  'Inclusive'  => [
    'text'  => translate('ZoneInclusive'),
    'title' => translate('ZoneInclusiveDesc'),
    'class' => 'zone-type-inclusive',
  ],
  'Exclusive'  => [
    'text'  => translate('ZoneExclusive'),
    'title' => translate('ZoneExclusiveDesc'),
    'class' => 'zone-type-exclusive',
  ],
  'Preclusive' => [
    'text'  => translate('ZonePreclusive'),
    'title' => translate('ZonePreclusiveDesc'),
    'class' => 'zone-type-preclusive',
  ],
  'Inactive'   => [
    'text'  => translate('ZoneInactive'),
    'title' => translate('ZoneInactiveDesc'),
    'class' => 'zone-type-inactive',
  ],
  'Privacy'    => [
    'text'  => translate('ZonePrivacy'),
    'title' => translate('ZonePrivacyDesc'),
    'class' => 'zone-type-privacy',
  ],
];

$optTypes = [];
foreach (getEnumValues('Zones', 'Type') as $optType) {
  $optTypes[$optType] = $typeLabels[$optType] ?? $optType;
}
$optUnits = array();
foreach ( getEnumValues('Zones', 'Units') as $optUnit ) {
  $optUnits[$optUnit] = $optUnit;
}

$optCheckMethods = array();
foreach ( getEnumValues('Zones', 'CheckMethod') as $optCheckMethod ) {
  $optCheckMethods[$optCheckMethod] = $optCheckMethod;
}

$monitor = new ZM\Monitor($mid);

$minX = 0;
$maxX = 100;
$minY = 0;
$maxY = 100;

if ( !isset($zone['Points']) ) {
  if ( $zid > 0 ) {
    $zone = dbFetchOne('SELECT * FROM Zones WHERE MonitorId=? AND Id=?', NULL, array($monitor->Id(), $zid));
    if (!$zone) {
      // A stale link to a deleted zone, or a zone of another monitor.
      $view = 'error';
      return;
    }
  } else {
    $zone = array(
      'Id' => 0,
      'Name' => translate('New'),
      'Type'  =>  'Active',
			'Units'	=>	'Percent',
      'MonitorId' => $monitor->Id(),
      'NumCoords' => 4,
      'Coords' => '0.00,0.00 100.00,0.00 100.00,100.00 0.00,100.00',
      'Area' => 10000,
      'AlarmRGB' => 0xff0000,
      'CheckMethod' => 'Blobs',
      'MinPixelThreshold' => '',
      'MaxPixelThreshold' => '',
      'MinAlarmPixels' => '',
      'MaxAlarmPixels' => '',
      'FilterX' => '',
      'FilterY' => '',
      'MinFilterPixels' => '',
      'MaxFilterPixels' => '',
      'MinBlobPixels' => '',
      'MaxBlobPixels' => '',
      'MinBlobs' => '',
      'MaxBlobs' => '',
      'OverloadFrames' => '',
      'ExtendAlarmFrames' => '',
    );
  }
  $zone['Points'] = coordsToPoints($zone['Coords']) ?: array();
  $zone['AreaCoords'] = preg_replace('/\s+/', ',', $zone['Coords']);
} # end if new Zone

// Set initial color class for the closed <select> based on the current zone type
$currentClass = $typeLabels[$zone['Type']]['class'] ?? '';

# Auto-detect pixel coordinates and convert to percentages
# Coordinates without a decimal point are pixels, as zmc reads them
$legacyPixels = strpos($zone['Coords'], '.') === false;
convertPixelPointsToPercent($zone['Points'], $monitor->ViewWidth(), $monitor->ViewHeight(), $legacyPixels);
# Always store as percentages
$zone['Units'] = 'Percent';

# Ensure Zone fits within the limits of the Monitor
limitPoints($zone['Points'], $minX, $minY, $maxX, $maxY);

ksort($zone['Points'], SORT_NUMERIC);

$zone['Coords'] = pointsToCoords($zone['Points']);
$zone['Area'] = getPolyArea($zone['Points']);
$zone['AreaCoords'] = preg_replace('/\s+/', ',', $zone['Coords']);
$selfIntersecting = isSelfIntersecting($zone['Points']);

# Threshold values are stored as percentages of zone area (0-100).
# Values > 100 are unconverted pixel counts from before the migration;
# convert them to percentages so the UI displays correctly.
$zonePixelArea = $zone['Area'] / 10000.0 * $monitor->ViewWidth() * $monitor->ViewHeight();
convertZoneThresholdsToPercent($zone, $zonePixelArea, $legacyPixels);

# Rectangle measurement ratios, and the only definition of their defaults -
# zone-object-size.js falls back to whatever is rendered here. Read from the
# cookie server side, as the player selector below does with zmZonePlayer.
#
# 25% of the drawn rectangle is docs/userguide/definezone.rst [J]; the rest of
# the box is background, and low-contrast pixels never clear MinPixelThreshold.
# 70% and 86% match every shipped Blobs preset (zm_create.sql.in ZonePresets:
# 0.5/0.35/0.3, 5/3.5/3, 1/0.7/0.6, 0.2/0.14/0.12), so the steps are a property
# of the pipeline rather than a sensitivity dial.
$rectangle_ratios = array(
  'objectFraction'    => array('cookie' => 'zmZoneObjectFraction', 'value' => 25),
  'objectFilterRatio' => array('cookie' => 'zmZoneFilterRatio',    'value' => 70),
  'objectBlobRatio'   => array('cookie' => 'zmZoneBlobRatio',      'value' => 86),
);
foreach ($rectangle_ratios as $id => $spec) {
  if (!isset($_COOKIE[$spec['cookie']])) continue;
  $saved = validInt($_COOKIE[$spec['cookie']]);
  if ($saved >= 1 and $saved <= 100) $rectangle_ratios[$id]['value'] = $saved;
}

# Testing the form's settings against a recording, through zma --dry-run.
# Disabled, with the reason as its title, when it cannot work.
# Only events this user may view: their times, notes and tags are shown.
$test_events = array_values(array_filter(
  ZM\Event::find(array('MonitorId'=>$monitor->Id()), array('order'=>'Id DESC', 'limit'=>25)),
  function($e) { return $e->canView(); }));
# Tag names per event, in one query. Skipped where the Tags tables are missing
# (a database not yet upgraded), and the inner join drops links to deleted tags.
$test_tags = array();
if ( count($test_events) and dbFetchAll("SHOW TABLES LIKE 'Events_Tags'") ) {
  $ids = array_map(function($e) { return $e->Id(); }, $test_events);
  $rows = dbFetchAll('SELECT ET.EventId, T.Name FROM Events_Tags AS ET INNER JOIN Tags AS T ON T.Id=ET.TagId
    WHERE ET.EventId IN ('.implode(',', array_fill(0, count($ids), '?')).') ORDER BY T.Name', NULL, $ids);
  foreach ( ($rows ? $rows : array()) as $row ) $test_tags[$row['EventId']][] = $row['Name'];
}
# What was in each event, so the right one is easy to find: when, how long,
# its notes (where detection writes what it saw) or cause, and any tags.
$test_options = array();
foreach ( $test_events as $test_event ) {
  $what = trim(preg_replace('/\s+/', ' ', (string)($test_event->Notes() ? $test_event->Notes() : $test_event->Cause())));
  if ( mb_strlen($what) > 60 ) $what = mb_substr($what, 0, 59).'…';
  $tags = isset($test_tags[$test_event->Id()]) ? $test_tags[$test_event->Id()] : array();
  $test_options[$test_event->Id()] = $test_event->Id().' - '
    .$dateTimeFormatter->format(strtotime($test_event->StartDateTime()))
    .' ('.round($test_event->Length()).'s) '.$what.(count($tags) ? ' ['.implode(', ', $tags).']' : '');
}
$test_disabled = '';
if ( !$zid ) {
  $test_disabled = translate('ZoneTestNeedSave');
} else if ( !count($test_events) ) {
  $test_disabled = translate('ZoneTestNoEvents');
} else if ( !is_executable(ZM_PATH_BIN.'/zma') ) {
  $test_disabled = translate('ZoneTestNoZma');
}

$focusWindow = true;
# Have to do this here, because the .js.php references somethings figured out when generating the streamHTML
$monitor->connKey();

# So I'm thinking now that 50% of screen real-estate with a minimum of 640px. 
# scale should be floor(whatever that width is/actual width)
# So we need javascript to figure out browser width, figure out scale and then activate the stream.  

xhtmlHeaders(__FILE__, translate('Zone'));
getBodyTopHTML();
echo getNavBarHTML();
?>
  <div id="page">
    <div class="w-100">
      <div class="float-left pl-3 pt-1">
        <button type="button" id="backBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Back') ?>" disabled><i class="fa fa-arrow-left"></i></button>
        <button type="button" id="refreshBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Refresh') ?>" ><i class="fa fa-refresh"></i></button>
      </div>
      <div class="float-right pr-3 pt-1">
        <button type="button" id="saveBtn" value="Save" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Save') ?>"<?php if (!canEdit('Monitors') || (false && $selfIntersecting)) { ?> disabled="disabled"<?php } ?>><i class="fa fa-save"></i></button>
        <button type="button" id="undoBtn" value="Undo" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Undo') ?>" disabled="disabled"><i class="fa fa-reply"></i></button>
        <button type="button" id="resetBtn" value="Reset" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Reset') ?>" disabled="disabled"><i class="fa fa-undo"></i></button>
        <button type="button" id="cancelBtn" value="Cancel" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Cancel') ?>"><i class="fa fa-times"></i></button>
      </div>
      <div class="w-100 pt-2">
        <h2><?php echo translate('Monitor').' '.validHtmlStr($monitor->Name()).' - '.translate('Zone').' '.validHtmlStr($zone['Name']) ?></h2>
      </div>
    </div>
    <div id="content">
      <form name="zoneForm" id="zoneForm" method="post" action="?">
        <input type="hidden" name="REFERER" value="<?php echo isset($_SERVER['HTTP_REFERER']) ? validHtmlStr($_SERVER['HTTP_REFERER']) : '' ?>"/> 
        <input type="hidden" name="view" value="<?php echo $view ?>"/>
        <input type="hidden" name="action" value="zone"/>
        <input type="hidden" name="mid" value="<?php echo $mid ?>"/>
        <input type="hidden" name="zid" value="<?php echo $zid ?>"/>
        <input type="hidden" name="newZone[NumCoords]" value="<?php echo count($zone['Points']) ?>"/>
        <input type="hidden" name="newZone[Coords]" value="<?php echo $zone['Coords'] ?>"/>
        <input type="hidden" name="newZone[AlarmRGB]"/>
        <input type="hidden" name="newZone[ObjectCoords]" value="<?php echo isset($zone['ObjectCoords']) ? validHtmlStr($zone['ObjectCoords']) : '' ?>"/>

				  <div id="imageAndPoints">
            <style>#imageFeed<?php echo $monitor->Id() ?> { aspect-ratio: <?php echo $monitor->ViewWidth() ?> / <?php echo $monitor->ViewHeight() ?>; }</style>
            <div id="imageFrame">
              <?php echo
$monitor->getStreamHTML(array('mode'=>'single', 'zones'=>false, 'state'=>true, 'analysis'=>true));
?>
              <svg id="zoneSVG" class="zones" viewBox="0 0 100 100" preserveAspectRatio="none">
<?php
if ( $zone['Id'] ) {
  $other_zones = dbFetchAll('SELECT * FROM Zones WHERE MonitorId = ? AND Id != ?', NULL, array($monitor->Id(), $zone['Id']));
} else {
  $other_zones = dbFetchAll('SELECT * FROM Zones WHERE MonitorId = ?', NULL, array($monitor->Id()));
}
if ( count($other_zones) ) {
  $html = '';
  foreach ( $other_zones as $other_zone ) {
    # Percent coordinates are always written with decimals; without any they are pixels
    $otherPoints = coordsToPoints($other_zone['Coords']) ?: array();
    convertPixelPointsToPercent($otherPoints, $monitor->ViewWidth(), $monitor->ViewHeight(), strpos($other_zone['Coords'], '.') === false);
    $other_zone['AreaCoords'] = preg_replace('/\s+/', ',', pointsToCoords($otherPoints));
    $html .= '<polygon id="zonePoly'.$other_zone['Id'].'" points="'. $other_zone['AreaCoords'] .'" class="'. $other_zone['Type'] .'"/>';
  }
  echo $html;
}
?>
                <polygon id="zonePoly" points="<?php echo $zone['AreaCoords'] ?>" class="Editing <?php echo $zone['Type'] ?>"/>
                <rect id="objectRect" x="0" y="0" width="0" height="0" style="display:none"/>
                <rect id="objectAlarmRect" x="0" y="0" width="0" height="0" style="display:none"><title><?php echo translate('RectangleAlarmedArea') ?></title></rect>
                <rect id="objectFilterRect" x="0" y="0" width="0" height="0" style="display:none"><title><?php echo translate('RectangleFilteredArea') ?></title></rect>
                <rect id="objectBlobRect" x="0" y="0" width="0" height="0" style="display:none"><title><?php echo translate('RectangleBlobArea') ?></title></rect>
                <rect id="objectKernelRect" x="0" y="0" width="0" height="0" style="display:none"><title><?php echo translate('ZoneFilterSize') ?></title></rect>
                <rect id="replayAlarmRect" x="0" y="0" width="0" height="0" style="display:none"/>
                <!-- Filtered over blob: when the blob is all of the filtered pixels the
                     two outlines coincide, and the dotted one lets both show. -->
                <rect id="replayBlobRect" x="0" y="0" width="0" height="0" style="display:none"/>
                <rect id="replayFilterRect" x="0" y="0" width="0" height="0" style="display:none"/>
                Sorry, your browser does not support inline SVG
              </svg>
              <button type="button" id="objectImageReplaceBtn" class="btn btn-normal" style="display:none" title="<?php echo translate('ObjectImageLoad') ?>">
                <i class="material-icons md-18">folder_open</i>
              </button>
            </div><?php # imageFrame
?>

            <div id="StreamControlButtons">
              <div id="controlButtons">
                <button type="button" id="analyseBtn" class="btn btn-primary" title="<?php echo translate('Showing Analysis') ?>">
                  <i class="material-icons md-18">assessment</i>
                </button>
                <button type="button" id="pauseBtn" class="btn btn-primary" title="<?php echo translate('Pause') ?>">
                  <i class="material-icons md-18">pause</i>
                </button>
                <button type="button" id="playBtn" class="btn btn-primary" title="<?php echo translate('Play') ?>">
                  <i class="material-icons md-18">play_arrow</i>
                </button>
                <button type="button" id="objectImageBtn" class="btn btn-normal" title="<?php echo translate('ObjectImageUse') ?>">
                  <i class="material-icons md-18">image</i>
                </button>
                <input type="file" id="objectImageFile" accept="image/*" style="display:none"/>
                <button type="button" id="objectSizeBtn" class="btn btn-normal" title="<?php echo translate('ObjectSizeMeasure') ?>">
                  <i class="material-icons md-18">highlight_alt</i>
                </button>
                <button type="button" id="objectSizeUndoBtn" class="btn btn-normal" style="display:none" title="<?php echo translate('ObjectSizeUndo') ?>">
                  <i class="material-icons md-18">undo</i>
                </button>
                <button type="button" id="zoneTestToggleBtn" class="btn btn-normal" title="<?php echo translate('ZoneTest') ?>">
                  <i class="material-icons md-18">history</i>
                </button>
              </div>
              <div id="playerControl">
                <label for="player"><?php echo translate('Player') ?></label>
<?php
                $players = [''=>translate('Auto'), 'zms'=>'ZMS MJPEG'];
                $players['go2rtc'] = 'Go2RTC Auto';
                $players['go2rtc_webrtc'] = 'Go2RTC WEBRTC';
                $players['go2rtc_mse'] = 'Go2RTC MSE';
                $players['go2rtc_hls'] = 'Go2RTC HLS';
                $players['rtsp2web_webrtc'] = 'RTSP2Web WEBRTC';
                $players['rtsp2web_mse'] = 'RTSP2Web MSE';
                $players['rtsp2web_hls'] = 'RTSP2Web HLS';
                $players['janus'] = 'Janus';
                $player = validHtmlStr($monitor->DefaultPlayer());
                if (isset($_COOKIE['zmZonePlayer']) and isset($players[$_COOKIE['zmZonePlayer']])) {
                  $player = validHtmlStr($_COOKIE['zmZonePlayer']);
                }
                echo htmlSelect('codec', $players, $player, array('data-on-change'=>'changePlayer','id'=>'player','class'=>'chosen'));
?>
              </div>
            </div>
            <div id="zoneTestPanel" style="display:none">
              <div class="zoneTestTitle" title="<?php echo translate('ZoneTestHelp') ?>"><?php echo translate('ZoneTest') ?> <i class="material-icons md-18">help_outline</i></div>
              <div>
                <?php # No name: it sits in the zone form, and a named field is a zone
                # change to Reset and gets posted on Save. ?>
                <?php echo htmlSelect('', $test_options, '', array('id'=>'zoneTestEvent', 'class'=>'chosen chosen-auto-width',
                  'aria-label'=>translate('Event')) + ($test_disabled ? array('disabled'=>'disabled') : array())) ?>
                <input type="number" id="zoneTestEventId" min="1" step="1" placeholder="<?php echo translate('ZoneTestEventId') ?>" title="<?php echo translate('ZoneTestEventIdHelp') ?>"<?php echo $test_disabled ? ' disabled' : '' ?>/>
                <!-- A disabled button gets no hover, so the reason sits on a wrapper. -->
                <span title="<?php echo validHtmlStr($test_disabled) ?>">
                  <button type="button" id="zoneTestBtn" class="btn btn-normal"<?php echo $test_disabled ? ' disabled' : '' ?>><?php echo translate('Run') ?></button>
                </span>
              </div>
              <div id="zoneTestResult"></div>
              <div id="replayControls" style="display:none">
                <canvas id="replayTimeline" height="24" title="<?php echo translate('ZoneReplayTimeline') ?>"></canvas>
                <div id="replayLegend" class="legend">
                  <span class="legendInAlarm"><?php echo translate('ZoneReplayInAlarmKey') ?></span>
                  <span class="legendScored"><?php echo translate('ZoneReplayScored') ?></span>
                  <span class="legendFail"><?php echo translate('ZoneReplayFails') ?></span>
                  <span class="legendNone"><?php echo translate('ZoneReplayNoChange') ?></span>
                  <span class="legendRerun"><?php echo translate('ZoneReplayRerun') ?></span>
                </div>
                <div>
                  <button type="button" id="replayPrevBtn" class="btn btn-normal" title="<?php echo translate('ZoneReplayPrev') ?>"><i class="material-icons md-18">skip_previous</i></button>
                  <button type="button" id="replayPlayBtn" class="btn btn-normal" title="<?php echo translate('Play') ?>"><i class="material-icons md-18">play_arrow</i></button>
                  <button type="button" id="replayNextBtn" class="btn btn-normal" title="<?php echo translate('ZoneReplayNext') ?>"><i class="material-icons md-18">skip_next</i></button>
                  <button type="button" id="replayCloseBtn" class="btn btn-normal" title="<?php echo translate('ZoneReplayClose') ?>"><i class="material-icons md-18">close</i> <?php echo translate('Exit') ?></button>
                  <span id="replayFrameInfo"></span>
                </div>
              </div>
              <div id="boxLegend" class="legend">
                <div class="legendRow">
                  <span class="lgObject" data-rect="objectRect" data-saved="0"><?php echo translate('ZoneLegendObject') ?></span>
                  <span class="lgEstimate" data-rect="objectRect" data-saved="1"><?php echo translate('ZoneLegendEstimate') ?></span>
                  <span class="lgAlarm" data-rect="objectAlarmRect"><?php echo translate('ZoneLegendAlarm') ?></span>
                  <span class="lgFilter" data-rect="objectFilterRect"><?php echo translate('ZoneLegendFilter') ?></span>
                  <span class="lgBlob" data-rect="objectBlobRect"><?php echo translate('ZoneLegendBlob') ?></span>
                  <span class="lgKernel" data-rect="objectKernelRect"><?php echo translate('ZoneLegendKernel') ?></span>
                </div>
                <div class="legendRow">
                  <b class="legendHead"><?php echo translate('ZoneLegendFrame') ?></b>
                  <span class="lgAlarmBox" data-rect="replayAlarmRect"><?php echo translate('ZoneReplayAlarmed') ?></span>
                  <span class="lgFilterBox" data-rect="replayFilterRect"><?php echo translate('ZoneReplayFiltered') ?></span>
                  <span class="lgBlobBox" data-rect="replayBlobRect"><?php echo translate('ZoneLegendBlobBox') ?></span>
                </div>
              </div>
            </div>
          </div><!--image & buttons-->

					<div id="settingsPanel">
						<table id="zoneSettings">
							<tbody>
								<tr>
									<th scope="row"><?php echo translate('Name') ?></th>
									<td colspan="2"><input type="text" name="newZone[Name]" value="<?php echo validHtmlStr($zone['Name']) ?>" maxlength="64"/></td>
								</tr>
								<tr>
									<th scope="row"><?php echo translate('Type') ?></th>
									<td colspan="2"><?php echo htmlSelect('newZone[Type]', $optTypes, $zone['Type'],
  array('data-on-change'=>'applyZoneType', 'id'=>'newZone[Type]', 'class'=>$currentClass)); ?></td>
								</tr>
								<tr>
									<th scope="row"><?php echo translate('Preset') ?></th>
									<td colspan="2">
<?php echo htmlSelect('presetSelector', $presetNames,
  ( isset($_REQUEST['presetSelector']) ? $_REQUEST['presetSelector'] : null),
  array(
    'data-on-change'   => 'applyPreset',
    'id'               => 'presetSelector',
    'data-builtin-ids' => implode(',', $builtInPresetIds)
  ) )
?>
										<div class="float-right">
										  <button type="button" id="loadPresetBtn" class="btn btn-sm btn-normal" title="<?php echo translate('ZoneLoadPresetDesc'); ?>"><i class="fa fa-download"></i></button>
										  <button type="button" id="savePresetBtn" class="btn btn-sm btn-normal" title="<?php echo translate('ZoneSavePresetDesc'); ?>"><i class="fa fa-save"></i></button>
										  <button type="button" id="deletePresetBtn" class="btn btn-sm btn-danger" style="display:none;" title="<?php echo translate('ZoneDeletePresetDesc'); ?>"><i class="fa fa-trash"></i></button>
										</div>
									</td>
								</tr>
								<input type="hidden" name="newZone[Units]" value="Percent"/>
								<tr>
									<th scope="row"><?php echo translate('ZoneAlarmColour') ?></th>
									<td colspan="2">
										<input type="color" id="alarmColour" value="<?php echo sprintf('#%06x', $zone['AlarmRGB'] & 0xffffff) ?>"/>
										<input type="number" name="newAlarmRgbR" value="<?php echo ($zone['AlarmRGB']>>16)&0xff ?>" min="0" max="255"/>
										/
										<input type="number" name="newAlarmRgbG" value="<?php echo ($zone['AlarmRGB']>>8)&0xff ?>" min="0" max="255"/>
										/
										<input type="number" name="newAlarmRgbB" value="<?php echo $zone['AlarmRGB']&0xff ?>" min="0" max="255"/>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo translate('CheckMethod') ?></th>
									<td colspan="2"><?php echo htmlSelect('newZone[CheckMethod]', $optCheckMethods, $zone['CheckMethod']); ?></td>
								</tr>
								<tr>
									<th scope="row"><?php echo translate('ZoneMinMaxPixelThres') ?></th>
									<td><input type="number" name="newZone[MinPixelThreshold]" value="<?php echo $zone['MinPixelThreshold'] ?>" min="0" max="255"/></td>
									<td><input type="number" name="newZone[MaxPixelThreshold]" value="<?php echo $zone['MaxPixelThreshold'] ?>" min="0" max="255"/></td>
								</tr>
								<tr>
									<th scope="row"><?php echo translate('ZoneFilterSize') ?></th>
									<td><input type="number" name="newZone[FilterX]" max="255" value="<?php echo $zone['FilterX'] ?>" min="0"/></td>
									<td><input type="number" name="newZone[FilterY]" max="255" value="<?php echo $zone['FilterY'] ?>" min="0"/></td>
								</tr>
								<tr>
									<th scope="row"><?php echo translate('ZoneArea') ?></th>
									<td><span class="zoneArea" id="areaPercent"><?php echo $zone['Area'] ?></span>%<input type="hidden" name="newZone[Area]" value="<?php echo $zone['Area'] ?>"/></td>
									<td><span class="zoneArea" id="areaPixels"></span> px</td>
								</tr>
								<tr>
									<th scope="row" class="swatch swatchAlarm"><?php echo translate('ZoneMinMaxAlarmArea') ?></th>
									<td><input type="number" name="newZone[MinAlarmPixels]" value="<?php echo $zone['MinAlarmPixels'] ?>" step="any" max="100" min="0"/>% <input type="number" class="pxInput" id="MinAlarmPixels_px" min="0" step="1"/> px</td>
									<td><input type="number" name="newZone[MaxAlarmPixels]" value="<?php echo $zone['MaxAlarmPixels'] ?>" step="any" max="100" min="0"/>% <input type="number" class="pxInput" id="MaxAlarmPixels_px" min="0" step="1"/> px</td>
								</tr>
								<tr>
									<th scope="row" class="swatch swatchFilter"><?php echo translate('ZoneMinMaxFiltArea') ?></th>
									<td><input type="number" name="newZone[MinFilterPixels]" value="<?php echo $zone['MinFilterPixels'] ?>" step="any" max="100" min="0"/>% <input type="number" class="pxInput" id="MinFilterPixels_px" min="0" step="1"/> px</td>
									<td><input type="number" name="newZone[MaxFilterPixels]" value="<?php echo $zone['MaxFilterPixels'] ?>" step="any" max="100" min="0"/>% <input type="number" class="pxInput" id="MaxFilterPixels_px" min="0" step="1"/> px</td>
								</tr>
								<tr>
									<th scope="row" class="swatch swatchBlob"><?php echo translate('ZoneMinMaxBlobArea') ?></th>
									<td><input type="number" name="newZone[MinBlobPixels]" value="<?php echo $zone['MinBlobPixels'] ?>" step="any" max="100" min="0"/>% <input type="number" class="pxInput" id="MinBlobPixels_px" min="0" step="1"/> px</td>
									<td><input type="number" name="newZone[MaxBlobPixels]" value="<?php echo $zone['MaxBlobPixels'] ?>" step="any" max="100" min="0"/>% <input type="number" class="pxInput" id="MaxBlobPixels_px" min="0" step="1"/> px</td>
								</tr>
								<tr>
									<th scope="row"><?php echo translate('ZoneMinMaxBlobs') ?></th>
									<td><input type="number" name="newZone[MinBlobs]" max="65535" value="<?php echo $zone['MinBlobs'] ?>" min="0"/></td>
									<td><input type="number" name="newZone[MaxBlobs]" max="65535" value="<?php echo $zone['MaxBlobs'] ?>" min="0"/></td>
								</tr>
								<tr>
									<th scope="row"><?php echo translate('ZoneOverloadFrames') ?></th>
									<td colspan="2"><input type="number" name="newZone[OverloadFrames]" max="65535" value="<?php echo $zone['OverloadFrames'] ?>" min="0"/></td>
								</tr>
								<tr>
									<th scope="row"><?php echo translate('ZoneExtendAlarmFrames') ?></th>
									<td colspan="2"><input type="number" name="newZone[ExtendAlarmFrames]" max="65535" value="<?php echo $zone['ExtendAlarmFrames'] ?>" min="0"/></td>
								</tr>
							</tbody>
						</table>
						<table id="rectangleSettings" style="display:none">
							<caption><?php echo translate('RectangleMeasure') ?></caption>
							<tbody>
								<tr>
									<th scope="row"><?php echo translate('RectangleObjectSize') ?></th>
									<td>
										<input type="number" id="objectWidthPx" min="1" step="1" title="<?php echo translate('ObjectSizeWidth') ?>" aria-label="<?php echo translate('ObjectSizeWidth') ?>"/>&times;<input type="number" id="objectHeightPx" min="1" step="1" title="<?php echo translate('ObjectSizeHeight') ?>" aria-label="<?php echo translate('ObjectSizeHeight') ?>"/>px
										<span class="ratioHint"><?php echo translate('RectangleOrTypeSize') ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row" class="swatch swatchAlarm"><?php echo translate('RectangleAlarmedArea') ?></th>
									<td>
										<input type="number" id="objectFraction" value="<?php echo $rectangle_ratios['objectFraction']['value'] ?>" min="1" max="100" step="1"/>%
										<span class="ratioHint"><?php echo translate('RectangleOfRectangle') ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row" class="swatch swatchFilter"><?php echo translate('RectangleFilteredArea') ?></th>
									<td>
										<input type="number" id="objectFilterRatio" value="<?php echo $rectangle_ratios['objectFilterRatio']['value'] ?>" min="1" max="100" step="1"/>%
										<span class="ratioHint"><?php echo translate('RectangleOfAlarmed') ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row" class="swatch swatchBlob"><?php echo translate('RectangleBlobArea') ?></th>
									<td>
										<input type="number" id="objectBlobRatio" value="<?php echo $rectangle_ratios['objectBlobRatio']['value'] ?>" min="1" max="100" step="1"/>%
										<span class="ratioHint"><?php echo translate('RectangleOfFiltered') ?></span>
									</td>
								</tr>
							</tbody>
						</table>
            <div id="zonePoints">
              <table>
                <tbody>
                  <tr>
  <?php
  $pointCols = 2;
  for ( $i = 0; $i < $pointCols; $i++ ) {
  ?>
                    <td>
                      <table>
                        <thead>
                          <tr>
                            <th class="pointIndex"><?php echo translate('Point') ?></th>
                            <th class="pointX"><?php echo translate('X') ?></th>
                            <th class="pointY"><?php echo translate('Y') ?></th>
                            <th class="pointAction"><?php echo translate('Action') ?></th>
                          </tr>
                        </thead>
                        <tbody>
                        </tbody>
                      </table>
                    </td>
  <?php
  # I think this for horizontal filler
      if ( $i < ($pointCols-1) ) {
  ?>
                    <td class="pointSpacer">&nbsp;</td>
  <?php
      }
  } # end foreach pointcol
  ?>
                  </tr>
                </tbody>
              </table>
            </div><!--end ZonePoints-->
					</div>
      </form>
    </div><!--content-->
  </div><!--page-->
<?php
if ($monitor->JanusEnabled()) {
?>
  <script src="<?php echo cache_bust('js/adapter.min.js') ?>"></script>
  <script src="/javascript/janus/janus.js"></script>
<?php
}
if ($monitor->Go2RTCEnabled()) {
  echo '<script type="module" src="js/video-stream.js"></script>'.PHP_EOL;
}
if ($monitor->Go2RTCEnabled() || $monitor->RTSP2WebEnabled()) {
?>
  <script src="<?php echo cache_bust('js/hls-1.6.13/hls.min.js') ?>"></script>
<?php
}
?>
  <script src="<?php echo cache_bust('js/MonitorStream.js') ?>"></script>
  <script src="<?php echo cache_bust('skins/classic/views/js/zone-object-size.js') ?>"></script>
<?php xhtmlFooter() ?>
