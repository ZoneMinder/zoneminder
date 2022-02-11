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
var monitorData = new Array();
<?php
foreach ( $monitors as $monitor ) {
?>
monitorData[monitorData.length] = {
  'id': <?php echo $monitor->Id() ?>,
  'width': <?php echo $monitor->ViewWidth() ?>,
  'height':<?php echo $monitor->ViewHeight() ?>,
  'url': '<?php echo $monitor->UrlToIndex() ?>',
  'onclick': function(){window.location.assign( '?view=watch&mid=<?php echo $monitor->Id() ?>' );},
  'type': '<?php echo $monitor->Type() ?>',
  'refresh': '<?php echo $monitor->Refresh() ?>',
  'janusEnabled': <?php echo $monitor->JanusEnabled() ?>
};
<?php
} // end foreach monitor
?>

var SCALE_BASE = <?php echo SCALE_BASE ?>;
