var monitorData = new Array();
<?php
  global $monitors;
  foreach ( $monitors as $monitor ) {
?>
monitorData[monitorData.length] = {
  'id': <?php echo $monitor->Id() ?>,
  'connKey': <?php echo $monitor->connKey() ?>,
  'width': <?php echo $monitor->ViewWidth() ?>,
  'height':<?php echo $monitor->ViewHeight() ?>,
  'url': '<?php echo $monitor->UrlToIndex( ZM_MIN_STREAMING_PORT ? ($monitor->Id() + ZM_MIN_STREAMING_PORT) : '') ?>',
  'type': '<?php echo $monitor->Type() ?>',
  'refresh': '<?php echo $monitor->Refresh() ?>'
};
<?php
  }
?>

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


var CMD_PAUSE = <?php echo CMD_PAUSE ?>;
var CMD_PLAY = <?php echo CMD_PLAY ?>;
var CMD_STOP = <?php echo CMD_STOP ?>;
var CMD_QUERY = <?php echo CMD_QUERY ?>;
var CMD_QUIT = <?php echo CMD_QUIT ?>;


var statusRefreshTimeout = <?php echo 1000*ZM_WEB_REFRESH_STATUS ?>;
