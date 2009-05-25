//
// Import constants
//
var STATE_IDLE = <?= STATE_IDLE ?>;
var STATE_PREALARM = <?= STATE_PREALARM ?>;
var STATE_ALARM = <?= STATE_ALARM ?>;
var STATE_ALERT = <?= STATE_ALERT ?>;
var STATE_TAPE = <?= STATE_TAPE ?>;

var stateStrings = new Array();
stateStrings[STATE_IDLE] = "<?= $SLANG['Idle'] ?>";
stateStrings[STATE_PREALARM] = "<?= $SLANG['Idle'] ?>";
stateStrings[STATE_ALARM] = "<?= $SLANG['Alarm'] ?>";
stateStrings[STATE_ALERT] = "<?= $SLANG['Alert'] ?>";
stateStrings[STATE_TAPE] = "<?= $SLANG['Record'] ?>";

var deleteString = "<?= $SLANG['Delete'] ?>";

var CMD_NONE = <?= CMD_NONE ?>;
var CMD_PAUSE = <?= CMD_PAUSE ?>;
var CMD_PLAY = <?= CMD_PLAY ?>;
var CMD_STOP = <?= CMD_STOP ?>;
var CMD_FASTFWD = <?= CMD_FASTFWD ?>;
var CMD_SLOWFWD = <?= CMD_SLOWFWD ?>;
var CMD_SLOWREV = <?= CMD_SLOWREV ?>;
var CMD_FASTREV = <?= CMD_FASTREV ?>;
var CMD_ZOOMIN = <?= CMD_ZOOMIN ?>;
var CMD_ZOOMOUT = <?= CMD_ZOOMOUT ?>;
var CMD_PAN = <?= CMD_PAN ?>;
var CMD_SCALE = <?= CMD_SCALE ?>;
var CMD_PREV = <?= CMD_PREV ?>;
var CMD_NEXT = <?= CMD_NEXT ?>;
var CMD_SEEK = <?= CMD_SEEK ?>;
var CMD_QUERY = <?= CMD_QUERY ?>;

var SCALE_BASE = <?= SCALE_BASE ?>;

var SOUND_ON_ALARM = <?= ZM_WEB_SOUND_ON_ALARM ?>;
var POPUP_ON_ALARM = <?= ZM_WEB_POPUP_ON_ALARM ?>;

var streamMode = "<?= $streamMode ?>";
var showMode = "<?= ($showPtzControls && !empty($control))?"control":"events" ?>";

var connKey = '<?= $connkey ?>';
var maxDisplayEvents = <?= 2 * MAX_EVENTS ?>;

var monitorId = <?= $monitor['Id'] ?>;
var monitorWidth = <?= $monitor['Width'] ?>;
var monitorHeight = <?= $monitor['Height'] ?>;

var scale = <?= $scale ?>;

var streamSrc = "<?= $streamSrc ?>";

var statusRefreshTimeout = <?= 1000*ZM_WEB_REFRESH_STATUS ?>;
var eventsRefreshTimeout = <?= 1000*ZM_WEB_REFRESH_EVENTS ?>;
var imageRefreshTimeout = <?= 1000*ZM_WEB_REFRESH_IMAGE ?>;

var canEditMonitors = <?= canEdit( 'Monitors' )?'true':'false' ?>;
var canStreamNative = <?= canStreamNative()?'true':'false' ?>;

var canPlayPauseAudio = Browser.Engine.trident;

<?php if ( $monitor['CanMoveMap'] ) { ?>
var imageControlMode = "moveMap";
<?php } elseif ( $monitor['CanMoveRel'] ) { ?>
var imageControlMode = "movePseudoMap";
<?php } elseif ( $monitor['CanMoveCon'] ) { ?>
var imageControlMode = "moveConMap";
<?php } else { ?>
var imageControlMode = null;
<?php } ?>

var refreshApplet = <?= (canStreamApplet() && $streamMode == "jpeg")?'true':'false' ?>;
var appletRefreshTime = <?= ZM_RELOAD_CAMBOZOLA ?>;
