<?php
  global $monIdx;
  global $nextMid;
  global $options;
  global $monitors;
?>
var monIdx = '<?php echo $monIdx; ?>';
var nextMid = "<?php echo isset($nextMid)?$nextMid:'' ?>";
var mode = "<?php echo $options['mode'] ?>";

var cycleRefreshTimeout = <?php echo 1000*ZM_WEB_REFRESH_CYCLE ?>;
var statusRefreshTimeout = <?php echo 1000*ZM_WEB_REFRESH_STATUS ?>;
var monitorData = new Array();
<?php
foreach ( $monitors as $monitor ) {
?>
monitorData[monitorData.length] = {
  'id': <?php echo $monitor->Id() ?>,
  'connKey': '<?php echo $monitor->connKey() ?>',
  'width': <?php echo $monitor->ViewWidth() ?>,
  'height':<?php echo $monitor->ViewHeight() ?>,
  'url': '<?php echo $monitor->UrlToIndex() ?>',
  'onclick': function(){window.location.assign( '?view=watch&mid=<?php echo $monitor->Id() ?>' );},
  'type': '<?php echo $monitor->Type() ?>',
  'refresh': '<?php echo $monitor->Refresh() ?>'
};
<?php
} // end foreach monitor
?>

var SCALE_BASE = <?php echo SCALE_BASE ?>;
