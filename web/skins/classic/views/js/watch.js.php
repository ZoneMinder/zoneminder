<?php 
  global $monitor_index;
  global $nextMid;
  global $options;
  global $monitors;
  global $streamMode;
  global $showPtzControls;
  global $connkey;
  global $monitor;
  global $scale;
  global $labels;
  global $cycle;
?>
//
// Import constants
//


var CMD_NONE = <?php echo CMD_NONE ?>;
var CMD_PAUSE = <?php echo CMD_PAUSE ?>;
var CMD_PLAY = <?php echo CMD_PLAY ?>;
var CMD_STOP = <?php echo CMD_STOP ?>;
var CMD_FASTFWD = <?php echo CMD_FASTFWD ?>;
var CMD_SLOWFWD = <?php echo CMD_SLOWFWD ?>;
var CMD_SLOWREV = <?php echo CMD_SLOWREV ?>;
var CMD_FASTREV = <?php echo CMD_FASTREV ?>;
var CMD_ZOOMIN = <?php echo CMD_ZOOMIN ?>;
var CMD_ZOOMOUT = <?php echo CMD_ZOOMOUT ?>;
var CMD_PAN = <?php echo CMD_PAN ?>;
var CMD_SCALE = <?php echo CMD_SCALE ?>;
var CMD_PREV = <?php echo CMD_PREV ?>;
var CMD_NEXT = <?php echo CMD_NEXT ?>;
var CMD_SEEK = <?php echo CMD_SEEK ?>;
var CMD_QUERY = <?php echo CMD_QUERY ?>;
var CMD_MAXFPS = <?php echo CMD_MAXFPS ?>;

var SOUND_ON_ALARM = <?php echo ZM_WEB_SOUND_ON_ALARM ?>;
var POPUP_ON_ALARM = <?php echo ZM_WEB_POPUP_ON_ALARM ?>;
var LIST_THUMBS = <?php echo ZM_WEB_LIST_THUMBS?'true':'false' ?>;

var streamMode = "<?php echo $streamMode ?>";
var showMode = "<?php echo ($showPtzControls && !empty($control))?"control":"events" ?>";
var cycle = <?php echo $cycle ? 'true' : 'false' ?>;

var connKey = '<?php echo $connkey ?>';
var maxDisplayEvents = <?php echo 2 * MAX_EVENTS ?>;

var monitorId = <?php echo $monitor->Id() ?>;
var monitorWidth = <?php echo $monitor->ViewWidth() ?>;
var monitorHeight = <?php echo $monitor->ViewHeight() ?>;
var monitorUrl = '<?php echo $monitor->UrlToIndex() ?>';
var monitorType = '<?php echo $monitor->Type() ?>';
var monitorRefresh = '<?php echo $monitor->Refresh() ?>';
var monitorStreamReplayBuffer = <?php echo $monitor->StreamReplayBuffer() ?>;
var monitorControllable = <?php echo $monitor->Controllable()?'true':'false' ?>;

var monIdx = <?php echo $monitor_index; ?>;
var nextMid = "<?php echo isset($nextMid)?$nextMid:'' ?>";
var mode = "<?php echo $options['mode'] ?>";

var monitorData = new Array();
<?php
foreach ($monitors as $m) {
?>
monitorData[monitorData.length] = {
  'id': <?php echo $m->Id() ?>,
  'connKey': <?php echo $m->connKey() ?>,
  'width': <?php echo $m->ViewWidth() ?>,
  'height':<?php echo $m->ViewHeight() ?>,
  'janusEnabled':<?php echo $m->JanusEnabled() ?>,
  'url': '<?php echo $m->UrlToIndex() ?>',
  'onclick': function(){window.location.assign( '?view=watch&mid=<?php echo $m->Id() ?>' );},
  'type': '<?php echo $m->Type() ?>',
  'refresh': '<?php echo $m->Refresh() ?>'
};
<?php
} // end foreach monitor
?>

var SCALE_BASE = <?php echo SCALE_BASE ?>;
var scale = '<?php echo $scale ?>';

var statusRefreshTimeout = <?php echo 1000*ZM_WEB_REFRESH_STATUS ?>;
var eventsRefreshTimeout = <?php echo 1000*ZM_WEB_REFRESH_EVENTS ?>;
var imageRefreshTimeout = <?php echo 1000*ZM_WEB_REFRESH_IMAGE ?>;

var canStreamNative = <?php echo canStreamNative()?'true':'false' ?>;

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

var refreshApplet = <?php echo (canStreamApplet() && $streamMode == "jpeg")?'true':'false' ?>;
var appletRefreshTime = <?php echo ZM_RELOAD_CAMBOZOLA ?>;

var labels = new Array();
<?php
$labels = array();
ZM\Debug("Presets");
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
