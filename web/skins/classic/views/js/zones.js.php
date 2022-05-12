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

var statusRefreshTimeout = <?php echo 1000*ZM_WEB_REFRESH_STATUS ?>;
