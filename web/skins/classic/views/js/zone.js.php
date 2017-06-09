var presets = new Object();
<?php
foreach ( $presets as $preset ) {
?>
presets[<?php echo $preset['Id'] ?>] = {
    'UnitsIndex': <?php echo $preset['UnitsIndex'] ?>,
    'CheckMethodIndex': <?php echo $preset['CheckMethodIndex'] ?>,
    'MinPixelThreshold': '<?php echo $preset['MinPixelThreshold'] ?>',
    'MaxPixelThreshold': '<?php echo $preset['MaxPixelThreshold'] ?>',
    'FilterX': '<?php echo $preset['FilterX'] ?>',
    'FilterY': '<?php echo $preset['FilterY'] ?>',
    'MinAlarmPixels': '<?php echo $preset['MinAlarmPixels'] ?>',
    'MaxAlarmPixels': '<?php echo $preset['MaxAlarmPixels'] ?>',
    'MinFilterPixels': '<?php echo $preset['MinFilterPixels'] ?>',
    'MaxFilterPixels': '<?php echo $preset['MaxFilterPixels'] ?>',
    'MinBlobPixels': '<?php echo $preset['MinBlobPixels'] ?>',
    'MaxBlobPixels': '<?php echo $preset['MaxBlobPixels'] ?>',
    'MinBlobs': '<?php echo $preset['MinBlobs'] ?>',
    'MaxBlobs': '<?php echo $preset['MaxBlobs'] ?>',
    'OverloadFrames': '<?php echo $preset['OverloadFrames'] ?>',
    'ExtendAlarmFrames': '<?php echo $preset['ExtendAlarmFrames'] ?>'
};
<?php
}
?>

var zone = {
    'Name': '<?php echo validJsStr($zone['Name']) ?>',
    'Id': <?php echo validJsStr($zone['Id']) ?>,
    'MonitorId': <?php echo validJsStr($zone['MonitorId']) ?>,
    'CheckMethod': '<?php echo $zone['CheckMethod'] ?>',
    'AlarmRGB': '<?php echo $zone['AlarmRGB'] ?>',
    'NumCoords': <?php echo $zone['NumCoords'] ?>,
    'Coords': '<?php echo $zone['Coords'] ?>',
    'Area': <?php echo $zone['Area'] ?>
};

zone['Points'] = new Array();
<?php
for ( $i = 0; $i < count($newZone['Points']); $i++ ) {
?>
zone['Points'][<?php echo $i ?>] = { 'x': <?php echo $newZone['Points'][$i]['x'] ?>, 'y': <?php echo $newZone['Points'][$i]['y'] ?> };
<?php
}
?>

var maxX = <?php echo $monitor->Width()-1 ?>;
var maxY = <?php echo $monitor->Height()-1 ?>;
var monitorArea = <?php echo $monitor->Width() * $monitor->Height() ?>;
var selfIntersecting = <?php echo $selfIntersecting?'true':'false' ?>;

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

//
// Imported from watch.js.php and modified for new zone edit view
//

var STATE_IDLE = <?php echo STATE_IDLE ?>;
var STATE_PREALARM = <?php echo STATE_PREALARM ?>;
var STATE_ALARM = <?php echo STATE_ALARM ?>;
var STATE_ALERT = <?php echo STATE_ALERT ?>;
var STATE_TAPE = <?php echo STATE_TAPE ?>;

var stateStrings = new Array();
stateStrings[STATE_IDLE] = "<?php echo translate('Idle') ?>";
stateStrings[STATE_PREALARM] = "<?php echo translate('Idle') ?>";
stateStrings[STATE_ALARM] = "<?php echo translate('Alarm') ?>";
stateStrings[STATE_ALERT] = "<?php echo translate('Alert') ?>";
stateStrings[STATE_TAPE] = "<?php echo translate('Record') ?>";

var pauseString = "<?php echo translate('Pause') ?>";
var playString = "<?php echo translate('Play') ?>";

var deleteString = "<?php echo translate('Delete') ?>";

var CMD_PAUSE = <?php echo CMD_PAUSE ?>;
var CMD_PLAY = <?php echo CMD_PLAY ?>;
var CMD_STOP = <?php echo CMD_STOP ?>;
var CMD_QUERY = <?php echo CMD_QUERY ?>;

var SCALE_BASE = <?php echo SCALE_BASE ?>;

var SOUND_ON_ALARM = <?php echo ZM_WEB_SOUND_ON_ALARM ?>;

var streamMode = "<?php echo $streamMode ?>";

var connKey = '<?php echo $connkey ?>';

var monitorId = <?php echo $monitor->Id() ?>;
var monitorUrl = '<?php echo ( $monitor->Server()->Url() ) ?>';

var streamSrc = "<?php echo preg_replace( '/&amp;/', '&', $streamSrc ) ?>";

var statusRefreshTimeout = <?php echo 1000*ZM_WEB_REFRESH_STATUS ?>;
var imageRefreshTimeout = <?php echo 1000*ZM_WEB_REFRESH_IMAGE ?>;

var canEditMonitors = <?php echo canEdit( 'Monitors' )?'true':'false' ?>;
var canStreamNative = <?php echo canStreamNative()?'true':'false' ?>;

var canPlayPauseAudio = Browser.ie;

var refreshApplet = <?php echo (canStreamApplet() && $streamMode == "jpeg")?'true':'false' ?>;
var appletRefreshTime = <?php echo ZM_RELOAD_CAMBOZOLA ?>;

