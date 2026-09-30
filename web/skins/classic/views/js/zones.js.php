//
// Import constants
//

var monitorData = new Array();
<?php
  global $monitors;
  foreach ( $monitors as $monitor ) {
?>
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
<?php
  }
?>

var statusRefreshTimeout = <?php echo 1000*ZM_WEB_REFRESH_STATUS ?>;
