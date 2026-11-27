<?php
  global $monitor;
?>
// PTZ requests go to the server that runs the monitor, as on the watch view:
// only that server's zmcontrol.pl can drive the camera.
var monitorUrl = '<?php echo $monitor->UrlToIndex(ZM_MIN_STREAMING_PORT ? ($monitor->Id() + ZM_MIN_STREAMING_PORT) : '') ?>';
