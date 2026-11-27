<?php 
  global $monitor_index;
  global $options;
  global $monitors;
  global $monitorsExtraData;
  global $streamMode;
  global $showPtzControls;
  global $monitor;
  global $scale;
  global $labels;
  global $cycle;
  global $player;
  $control = $monitor->Control();
  require_once('includes/log_redact.php');
?>
//
// Import constants
//

var POPUP_ON_ALARM = <?php echo ZM_WEB_POPUP_ON_ALARM ?>;
var LIST_THUMBS = <?php echo ZM_WEB_LIST_THUMBS?'true':'false' ?>;

var streamMode = "<?php echo validJsStr($streamMode) ?>";
var showMode = "<?php echo ($showPtzControls && !empty($control))?"control":"events" ?>";
// Whether the PTZ panel starts shown: the request, the cookie or, with neither, shown
var ptzVisible = <?php echo $showPtzControls ? 'true' : 'false' ?>;
var cycle = <?php echo $cycle ? 'true' : 'false' ?>;
var player = '<?php echo validJsStr($player) ?>';
const showZonesString = "<?php echo validJsStr(translate('Show Zones')) ?>";
const hideZonesString = "<?php echo validJsStr(translate('Hide Zones')) ?>";

var maxDisplayEvents = <?php echo 2 * MAX_EVENTS ?>;
var monitorId = parseInt('<?php echo validJsStr($monitor->Id()) ?>');
var monitorUrl = '<?php echo $monitor->UrlToIndex(ZM_MIN_STREAMING_PORT ? ($monitor->Id() + ZM_MIN_STREAMING_PORT) : '') ?>';

var monIdx = '<?php echo $monitor_index; ?>';
var mode = '<?php echo validJsStr($options['mode']) ?>';

var monitorData = new Array();
<?php
foreach ($monitors as $m) {
?>
monitorData[monitorData.length] = {
  'id': <?php echo $m->Id() ?>,
  'name': '<?php echo validJsStr($m->Name()) ?>',
  'server_id': '<?php echo validJsStr($m->ServerId()) ?>',
  'connKey': <?php echo $m->connKey() ?>,
  'width': <?php echo $m->ViewWidth() ?>,
  'height':<?php echo $m->ViewHeight() ?>,
  'RTSP2WebEnabled':<?php echo $m->RTSP2WebEnabled() ?>,
  'DefaultPlayer':'<?php echo validJsStr($m->DefaultPlayer()) ?>',
  'RTSPServer':<?php echo $m->RTSPServer() ? 'true' : 'false' ?>,
  'StreamChannel':'<?php echo validJsStr($m->StreamChannel()) ?>',
  'Go2RTCEnabled': <?php echo $m->Go2RTCEnabled() ?>,
  'Path':'<?php echo validJsStr($m->canEdit() ? $m->Path() : ZM\removeUserinfo($m->Path())) ?>',
  'SecondPath':'<?php echo validJsStr($m->canEdit() ? $m->SecondPath() : ZM\removeUserinfo($m->SecondPath())) ?>',
  'Restream':<?php echo $m->Restream() ? 'true' : 'false' ?>,
  'janusEnabled':<?php echo $m->JanusEnabled() ?>,
  'url': '<?php echo $m->UrlToIndex(ZM_MIN_STREAMING_PORT ? ($m->Id() + ZM_MIN_STREAMING_PORT) : '') ?>',
  'url_to_zms': '<?php echo $m->UrlToZMS( ZM_MIN_STREAMING_PORT ? ($m->Id() + ZM_MIN_STREAMING_PORT) : '') ?>',
  'onclick': function(){window.location.assign( '?view=watch&mid=<?php echo $m->Id() ?>' );},
  'type': '<?php echo validJsStr($m->Type()) ?>',
  'capturing': '<?php echo validJsStr($m->Capturing()) ?>',
  'refresh': '<?php echo validJsStr($m->Refresh()) ?>',
  'janus_pin': '<?php echo validJsStr($m->Janus_Pin()) ?>',
  'streamHTML': '<?php echo str_replace(array("\r\n", "\r", "\n"), '', $monitorsExtraData[$m->Id()]['StreamHTML']) ?>',
  'urlForAllEvents': '<?php echo $monitorsExtraData[$m->Id()]['urlForAllEvents'] ?>',
  'ptzControls': '<?php echo str_replace(array("\r\n", "\r", "\n"), '', $monitorsExtraData[$m->Id()]['ptzControls']) ?>',
  'monitorWidth': parseInt('<?php echo validJsStr($m->ViewWidth()) ?>'),
  'monitorHeight': parseInt('<?php echo validJsStr($m->ViewHeight()) ?>'),
  'monitorType': '<?php echo validJsStr($m->Type()) ?>',
  'monitorRefresh': '<?php echo validJsStr($m->Refresh()) ?>',
  'monitorStreamReplayBuffer': parseInt('<?php echo validJsStr($m->StreamReplayBuffer()) ?>'),
  'monitorControllable': <?php echo $m->Controllable()?'true':'false' ?>,
  'streamMode': '<?php echo $m->getStreamMode(); ?>',
  'whatDisplay': '<?php echo validJsStr($m->WhatDisplay()) ?>'
};
<?php
} // end foreach monitor
?>

var scale = '<?php echo validJsStr($scale) ?>';

const statusRefreshTimeout = <?php echo 1000*ZM_WEB_REFRESH_STATUS ?>;
const eventsRefreshTimeout = <?php echo 1000*ZM_WEB_REFRESH_EVENTS ?>;
const imageRefreshTimeout = <?php echo 1000*ZM_WEB_REFRESH_IMAGE ?>;

const canStream = <?php echo canStream()?'true':'false' ?>;

var imageControlMode = '<?php 
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
global $players;
echo 'players = '.json_encode($players).PHP_EOL;
?>
