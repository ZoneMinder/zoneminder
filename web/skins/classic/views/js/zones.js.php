//
// Import constants
//

const ZM_DIR_SOUNDS = '<?php echo ZM_DIR_SOUNDS ?>';

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
  'janusEnabled':<?php echo $monitor->JanusEnabled() ?>,
  'RTSP2WebEnabled': <?php echo $monitor->RTSP2WebEnabled() ?>,
  'RTSP2WebType': '<?php echo $monitor->RTSP2WebType() ?>',
  'url': '<?php echo $monitor->UrlToIndex( ZM_MIN_STREAMING_PORT ? ($monitor->Id() + ZM_MIN_STREAMING_PORT) : '') ?>',
  'url_to_zms': '<?php echo $monitor->UrlToZMS( ZM_MIN_STREAMING_PORT ? ($monitor->Id() + ZM_MIN_STREAMING_PORT) : '') ?>',
  'type': '<?php echo $monitor->Type() ?>',
  'refresh': '<?php echo $monitor->Refresh() ?>',
  'janus_pin': '<?php echo $monitor->Janus_Pin() ?>'
};
<?php
  }
?>

var statusRefreshTimeout = <?php echo 1000*ZM_WEB_REFRESH_STATUS ?>;
