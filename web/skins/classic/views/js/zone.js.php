//
// Import constants
//

<?php
  global $presets;
  global $zone;
  global $monitor;
  global $streamMode;
  global $connkey;
?>

var presets = new Object();
<?php
foreach ( $presets as $preset ) {
?>
presets[<?php echo (int)$preset->Id() ?>] = <?php echo json_encode($preset->toArray()) ?>;
<?php
} # end foreach preset
?>

var zone = {
    'Name': '<?php echo validJsStr($zone['Name']) ?>',
    'Id': <?php echo validJsStr($zone['Id']) ?>,
    'MonitorId': <?php echo validJsStr($zone['MonitorId']) ?>,
    'CheckMethod': '<?php echo validJsStr($zone['CheckMethod']) ?>',
    'AlarmRGB': '<?php echo validJsStr($zone['AlarmRGB']) ?>',
    'NumCoords': <?php echo $zone['NumCoords'] ?>,
    'Coords': '<?php echo validJsStr($zone['Coords']) ?>',
    'Area': <?php echo $zone['Area'] ?>
};

zone['Points'] = new Array();
<?php
for ( $i = 0; $i < count($zone['Points']); $i++ ) {
?>
zone['Points'][<?php echo $i ?>] = { 'x': <?php echo $zone['Points'][$i]['x'] ?>, 'y': <?php echo $zone['Points'][$i]['y'] ?> };
<?php
}
?>

var maxX = 100;
var maxY = 100;
var monitorArea = 10000;
var monitorPixelArea = <?php echo $monitor->ViewWidth() * $monitor->ViewHeight() ?>;

var monitorData = new Array();
monitorData[monitorData.length] = {
  'id': <?php echo $monitor->Id() ?>,
  'name': '<?php echo validJsStr($monitor->Name()) ?>',
  'connKey': <?php echo $monitor->connKey() ?>,
  'width': <?php echo $monitor->ViewWidth() ?>,
  'height':<?php echo $monitor->ViewHeight() ?>,
  'janusEnabled':<?php echo $monitor->JanusEnabled() ?>,
  'RTSP2WebEnabled': <?php echo $monitor->RTSP2WebEnabled() ?>,
  'RTSPServer':<?php echo $monitor->RTSPServer() ? 'true' : 'false' ?>,
  'StreamChannel': '<?php echo validJsStr($monitor->StreamChannel()) ?>',
  'DefaultPlayer':'<?php echo validJsStr($monitor->DefaultPlayer()) ?>',
  'Go2RTCEnabled': <?php echo $monitor->Go2RTCEnabled() ?>,
  'url': '<?php echo $monitor->UrlToIndex( ZM_MIN_STREAMING_PORT ? ($monitor->Id() + ZM_MIN_STREAMING_PORT) : '') ?>',
  'url_to_zms': '<?php echo $monitor->UrlToZMS( ZM_MIN_STREAMING_PORT ? ($monitor->Id() + ZM_MIN_STREAMING_PORT) : '') ?>',
  'type': '<?php echo validJsStr($monitor->Type()) ?>',
  'capturing': '<?php echo validJsStr($monitor->Capturing()) ?>',
  'refresh': '<?php echo validJsStr($monitor->Refresh()) ?>',
  'janus_pin': '<?php echo validJsStr($monitor->Janus_Pin()) ?>'
};

var selfIntersectingString = '<?php echo addslashes(translate('SelfIntersecting')) ?>';
var alarmRGBUnsetString = '<?php echo addslashes(translate('AlarmRGBUnset')) ?>';
var minPixelThresUnsetString = '<?php echo addslashes(translate('MinPixelThresUnset')) ?>';
var minPixelThresLtMaxString = '<?php echo addslashes(translate('MinPixelThresLtMax')) ?>';
var filterUnsetString = '<?php echo addslashes(translate('FilterUnset')) ?>';
var minAlarmAreaUnsetString = '<?php echo addslashes(translate('MinAlarmAreaUnset')) ?>';
var minAlarmAreaLtMaxString = '<?php echo addslashes(translate('MinAlarmAreaLtMax')) ?>';
var minFilterAreaUnsetString = '<?php echo addslashes(translate('MinFilterAreaUnset')) ?>';
var minFilterAreaLtMaxString = '<?php echo addslashes(translate('MinFilterAreaLtMax')) ?>';
var minFilterLtMinAlarmString = '<?php echo addslashes(translate('MinFilterLtMinAlarm')) ?>';
var minBlobAreaUnsetString = '<?php echo addslashes(translate('MinBlobAreaUnset')) ?>';
var minBlobAreaLtMaxString = '<?php echo addslashes(translate('MinBlobAreaLtMax')) ?>';
var minBlobLtMinFilterString = '<?php echo addslashes(translate('MinBlobLtMinFilter')) ?>';
var minBlobsUnsetString = '<?php echo addslashes(translate('MinBlobsUnset')) ?>';
var minBlobsLtMaxString = '<?php echo addslashes(translate('MinBlobsLtMax')) ?>';

var zoneObjectSizeStrings = {
  'filterTooSmall': '<?php echo addslashes(translate('ObjectSizeFilterTooSmall')) ?>',
  'inactive': '<?php echo addslashes(translate('ObjectSizeInactive')) ?>',
  'useImage': '<?php echo addslashes(translate('ObjectImageUse')) ?>',
  'liveView': '<?php echo addslashes(translate('ObjectImageLive')) ?>',
  'stillLiveOff': '<?php echo addslashes(translate('ObjectImageLiveOff')) ?>',
  'notAnImage': '<?php echo addslashes(translate('ObjectImageNotAnImage')) ?>',
  'sizeNeeded': '<?php echo addslashes(translate('ObjectSizeNeeded')) ?>',
  'testRunning': '<?php echo addslashes(translate('ZoneTestRunning')) ?>',
  'testStale': '<?php echo addslashes(translate('ZoneTestStale')) ?>',
  'testUnknown': '<?php echo addslashes(translate('ZoneTestUnknown')) ?>',
  'testUnknownFrame': '<?php echo addslashes(translate('ZoneReplayRerun')) ?>',
  'liveOff': '<?php echo addslashes(translate('ZoneReplayLiveOff')) ?>',
  'labelObject': '<?php echo addslashes(translate('ZoneLegendObject')) ?>',
  'labelEstimate': '<?php echo addslashes(translate('ZoneLegendEstimate')) ?>',
  'replayFrame': '<?php echo addslashes(translate('Frame')) ?>',
  'replayAlarmed': '<?php echo addslashes(translate('ZoneReplayAlarmed')) ?>',
  'replayBlob': '<?php echo addslashes(translate('ZoneReplayBlob')) ?>',
  'replayFiltered': '<?php echo addslashes(translate('ZoneReplayFiltered')) ?>',
  'replayScored': '<?php echo addslashes(translate('ZoneReplayScored')) ?>',
  'testNotDetecting': '<?php echo addslashes(translate('ZoneTestNotDetecting')) ?>',
  'replayNoMotion': '<?php echo addslashes(translate('ZoneReplayNoMotion')) ?>',
  'testWouldAlarm': '<?php echo addslashes(translate('ZoneTestWouldAlarm')) ?>',
  'testWouldNotAlarm': '<?php echo addslashes(translate('ZoneTestWouldNotAlarm')) ?>',
  'replayInAlarm': '<?php echo addslashes(translate('ZoneReplayInAlarm')) ?>',
  'replayFailsAt': '<?php echo addslashes(translate('ZoneReplayFailsAt')) ?>',
  'replayMin': '<?php echo addslashes(translate('ZoneReplayMin')) ?>',
  'replayGot': '<?php echo addslashes(translate('ZoneReplayGot')) ?>',
  'testQualify': '<?php echo addslashes(translate('ZoneTestQualify')) ?>',
  'testNotInARow': '<?php echo addslashes(translate('ZoneTestNotInARow')) ?>',
  'testClosest': '<?php echo addslashes(translate('ZoneTestClosest')) ?>',
  'testUnsure': '<?php echo addslashes(translate('ZoneTestUnsure')) ?>',
  'replayOverload': '<?php echo addslashes(translate('ZoneReplayOverload')) ?>',
  'testNoVideo': '<?php echo addslashes(translate('ZoneTestNoVideo')) ?>'
};

//
// Imported from watch.js.php and modified for new zone edit view
//


const POPUP_ON_ALARM = false;

var streamMode = "<?php echo validJsStr($streamMode) ?>";

var connKey = '<?php echo validJsStr($connkey) ?>';

var monitorId = <?php echo $monitor->Id() ?>;
var monitorUrl = '<?php echo ( $monitor->UrlToIndex() ) ?>';

var statusRefreshTimeout = <?php echo 1000*ZM_WEB_REFRESH_STATUS ?>;
var imageRefreshTimeout = <?php echo 1000*ZM_WEB_REFRESH_IMAGE ?>;

var canStream = <?php echo canStream()?'true':'false' ?>;

var enterZonePresetName = '<?php echo validJsStr(translate('ZoneEnterPresetName')); ?>';
var invalidZonePresetName = '<?php echo validJsStr(translate('ZoneInvalidPresetName')); ?>';
<?php require_once('includes/ZonePreset.php'); ?>
var zonePresetFields = <?php echo json_encode(ZM\ZonePreset::getConfigurableFields()); ?>;
