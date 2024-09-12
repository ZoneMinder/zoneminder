<?php 
  global $streamMode;
  global $showPtzControls;
  global $connkey;
  global $monitor;
  global $scale;
  global $labels;
?>
//
// Import constants
//

var deleteString = "<?php echo translate('Delete') ?>";

var enableAlarmsStr = "<?php echo translate('EnableAlarms') ?>";
var disableAlarmsStr = "<?php echo translate('DisableAlarms') ?>";
var forceAlarmStr = "<?php echo translate('ForceAlarm') ?>";
var cancelForcedAlarmStr = "<?php echo translate('CancelForcedAlarm') ?>";

var SCALE_BASE = <?php echo SCALE_BASE ?>;

var SOUND_ON_ALARM = <?php echo ZM_WEB_SOUND_ON_ALARM ?>;
var POPUP_ON_ALARM = <?php echo ZM_WEB_POPUP_ON_ALARM ?>;
var LIST_THUMBS = <?php echo ZM_WEB_LIST_THUMBS?'true':'false' ?>;

var streamMode = "<?php echo $streamMode ?>";
var showMode = "<?php echo ($showPtzControls && !empty($control))?"control":"events" ?>";

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

var monIdx = 0;
var monitorData = new Array();
monitorData[monitorData.length] = {
  'id': <?php echo $monitor->Id() ?>,
  'connKey': <?php echo $monitor->connKey() ?>,
  'width': <?php echo $monitor->ViewWidth() ?>,
  'height':<?php echo $monitor->ViewHeight() ?>,
  'url': '<?php echo $monitor->UrlToIndex() ?>',
  'onclick': function(){window.location.assign( '?view=watch&mid=<?php echo $monitor->Id() ?>' );},
  'type': '<?php echo $monitor->Type() ?>',
  'refresh': '<?php echo $monitor->Refresh() ?>'
};

var scale = '<?php echo $scale ?>';

var statusRefreshTimeout = <?php echo 1000*ZM_WEB_REFRESH_STATUS ?>;
var eventsRefreshTimeout = <?php echo 1000*ZM_WEB_REFRESH_EVENTS ?>;
var imageRefreshTimeout = <?php echo 1000*ZM_WEB_REFRESH_IMAGE ?>;

var canStreamNative = <?php echo canStreamNative()?'true':'false' ?>;

<?php 
  $control = $monitor->Control();
  if ( $control->CanMoveMap() ) { ?>
var imageControlMode = "moveMap";
<?php } elseif ( $control->CanMoveRel() ) { ?>
var imageControlMode = "movePseudoMap";
<?php } elseif ( $control->CanMoveCon() ) { ?>
var imageControlMode = "moveConMap";
<?php } else { ?>
var imageControlMode = null;
<?php } ?>

var refreshApplet = <?php echo (canStreamApplet() && $streamMode == "jpeg")?'true':'false' ?>;
var appletRefreshTime = <?php echo defined('ZM_RELOAD_CAMBOZOLA') ? ZM_RELOAD_CAMBOZOLA : 0 ?>;

var labels = new Array();
<?php
$labels = array();
foreach( dbFetchAll( 'SELECT * FROM ControlPresets WHERE MonitorId = ?', NULL, array( $monitor->Id() ) ) as $row ) {
  $labels[$row['Preset']] = $row['Label'];
}

foreach ( $labels as $index=>$label ) {
?>
labels[<?php echo validInt($index) ?>] = '<?php echo validJsStr($label) ?>';
<?php
}
?>
