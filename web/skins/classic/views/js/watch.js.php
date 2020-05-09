//
// Import constants
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

var deleteString = "<?php echo translate('Delete') ?>";

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

var SCALE_BASE = <?php echo SCALE_BASE ?>;

var SOUND_ON_ALARM = <?php echo ZM_WEB_SOUND_ON_ALARM ?>;
var POPUP_ON_ALARM = <?php echo ZM_WEB_POPUP_ON_ALARM ?>;

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

var scale = '<?php echo $scale ?>';

var statusRefreshTimeout = <?php echo 1000*ZM_WEB_REFRESH_STATUS ?>;
var eventsRefreshTimeout = <?php echo 1000*ZM_WEB_REFRESH_EVENTS ?>;
var imageRefreshTimeout = <?php echo 1000*ZM_WEB_REFRESH_IMAGE ?>;

var canEditMonitors = <?php echo canEdit( 'Monitors' )?'true':'false' ?>;
var canStreamNative = <?php echo canStreamNative()?'true':'false' ?>;

var canPlayPauseAudio = Browser.ie;

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
var appletRefreshTime = <?php echo ZM_RELOAD_CAMBOZOLA ?>;
