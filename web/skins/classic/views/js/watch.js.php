<?php 
  global $monitor_index;
  global $nextMid;
  global $options;
  global $monitors;
  global $monitorsExtraData;
  global $streamMode;
  global $showPtzControls;
  global $monitor;
  global $scale;
  global $labels;
  global $cycle;
?>
//
// Import constants
//

var ZM_DIR_SOUNDS = "<?php echo ZM_DIR_SOUNDS ?>";
var POPUP_ON_ALARM = <?php echo ZM_WEB_POPUP_ON_ALARM ?>;
var LIST_THUMBS = <?php echo ZM_WEB_LIST_THUMBS?'true':'false' ?>;

var streamMode = "<?php echo $streamMode ?>";
var showMode = "<?php echo ($showPtzControls && !empty($control))?"control":"events" ?>";
var cycle = <?php echo $cycle ? 'true' : 'false' ?>;

var maxDisplayEvents = <?php echo 2 * MAX_EVENTS ?>;
var monitorId = parseInt('<?php echo $monitor->Id() ?>');
var monitorWidth = parseInt('<?php echo $monitor->ViewWidth() ?>');
var monitorHeight = parseInt('<?php echo $monitor->ViewHeight() ?>');
var monitorUrl = '<?php echo $monitor->UrlToIndex(ZM_MIN_STREAMING_PORT ? ($monitor->Id() + ZM_MIN_STREAMING_PORT) : '') ?>';
var monitorType = '<?php echo $monitor->Type() ?>';
var monitorRefresh = '<?php echo $monitor->Refresh() ?>';
var monitorStreamReplayBuffer = parseInt('<?php echo $monitor->StreamReplayBuffer() ?>');
var monitorControllable = <?php echo $monitor->Controllable()?'true':'false' ?>;

var monIdx = '<?php echo $monitor_index; ?>';
var nextMid = '<?php echo isset($nextMid)?$nextMid:'' ?>';
var mode = '<?php echo $options['mode'] ?>';

var monitorData = new Array();
<?php
foreach ($monitors as $m) {
?>
monitorData[monitorData.length] = {
  'id': <?php echo $m->Id() ?>,
  'server_id': '<?php echo $m->ServerId() ?>',
  'connKey': <?php echo $m->connKey() ?>,
  'width': <?php echo $m->ViewWidth() ?>,
  'height':<?php echo $m->ViewHeight() ?>,
  'RTSP2WebEnabled':<?php echo $m->RTSP2WebEnabled() ?>,
  'RTSP2WebType':'<?php echo $m->RTSP2WebType() ?>',
  'janusEnabled':<?php echo $m->JanusEnabled() ?>,
  'url': '<?php echo $m->UrlToIndex(ZM_MIN_STREAMING_PORT ? ($m->Id() + ZM_MIN_STREAMING_PORT) : '') ?>',
  'onclick': function(){window.location.assign( '?view=watch&mid=<?php echo $m->Id() ?>' );},
  'type': '<?php echo $m->Type() ?>',
  'refresh': '<?php echo $m->Refresh() ?>',
  'janus_pin': '<?php echo $m->Janus_Pin() ?>',
  'streamHTML': '<?php echo str_replace(array("\r\n", "\r", "\n"), '', $monitorsExtraData[$m->Id()]['StreamHTML']) ?>',
  'urlForAllEvents': '<?php echo $monitorsExtraData[$m->Id()]['urlForAllEvents'] ?>',
  'ptzControls': '<?php echo str_replace(array("\r\n", "\r", "\n"), '', $monitorsExtraData[$m->Id()]['ptzControls']) ?>',
  'monitorWidth': parseInt('<?php echo $m->ViewWidth() ?>'),
  'monitorHeight': parseInt('<?php echo $m->ViewHeight() ?>'),
  'monitorType': '<?php echo $m->Type() ?>',
  'monitorRefresh': '<?php echo $m->Refresh() ?>',
  'monitorStreamReplayBuffer': parseInt('<?php echo $m->StreamReplayBuffer() ?>'),
  'monitorControllable': <?php echo $m->Controllable()?'true':'false' ?>,
  'streamMode': '<?php echo getStreamModeMonitor($m) ?>'
};
<?php
} // end foreach monitor
?>

var scale = '<?php echo $scale ?>';

const statusRefreshTimeout = <?php echo 1000*ZM_WEB_REFRESH_STATUS ?>;
const eventsRefreshTimeout = <?php echo 1000*ZM_WEB_REFRESH_EVENTS ?>;
const imageRefreshTimeout = <?php echo 1000*ZM_WEB_REFRESH_IMAGE ?>;

const canStream = <?php echo canStream()?'true':'false' ?>;

var imageControlMode = '<?php 
$control = $monitor->Control();
if ($control->CanMoveMap()) {
  echo 'moveMap';
} else if ($control->CanMoveRel()) {
  echo 'movePseudoMap';
} else if ($control->CanMoveCon()) {
  echo 'moveConMap';
}
?>';

var labels = new Array();
<?php
$labels = array();
foreach (dbFetchAll('SELECT * FROM ControlPresets WHERE MonitorId = ?', NULL, array($monitor->Id())) as $row) {
  $label = $labels[$row['Preset']] = $row['Label'];
  echo 'labels['. validInt($row['Preset']) .'] = \''.validJsStr($label).'\';'.PHP_EOL;
}
?>
var deleteString = "<?php echo translate('Delete') ?>";
var enableAlarmsStr = "<?php echo translate('EnableAlarms') ?>";
var disableAlarmsStr = "<?php echo translate('DisableAlarms') ?>";
var forceAlarmStr = "<?php echo translate('ForceAlarm') ?>";
var cancelForcedAlarmStr = "<?php echo translate('CancelForcedAlarm') ?>";
var translate = {
  "seconds": "<?php echo translate('seconds') ?>",
  "Fullscreen": "<?php echo translate('Fullscreen') ?>",
  "Exit Fullscreen": "<?php echo translate('Exit Fullscreen') ?>",
};
