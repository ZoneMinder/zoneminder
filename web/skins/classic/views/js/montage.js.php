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

var CMD_QUERY = <?= CMD_QUERY ?>;

var SCALE_BASE = <?= SCALE_BASE ?>;

var COMPACT_MONTAGE = <?= ZM_WEB_COMPACT_MONTAGE ?>;
var SOUND_ON_ALARM = <?= ZM_WEB_SOUND_ON_ALARM ?>;
var POPUP_ON_ALARM = <?= ZM_WEB_POPUP_ON_ALARM ?>;

var statusRefreshTimeout = <?= 1000*ZM_WEB_REFRESH_STATUS ?>;

var canStreamNative = <?= canStreamNative()?'true':'false' ?>;

var monitorData = new Array();
<?php
foreach ( $monitors as $monitor )
{
?>
monitorData[monitorData.length] = { 'id': <?= $monitor['Id'] ?>, 'connKey': <?= $monitor['connKey'] ?>, 'width': <?= $monitor['Width'] ?>,'height':<?= $monitor['Height'] ?> };
<?php
}
?>
