<?php
  global $scale;
  global $eid;
  global $fid;
  global $alarmFrame;
?>

var scale = '<?php echo validJsStr($scale); ?>';

var SCALE_BASE = <?php echo SCALE_BASE ?>;

var eid = <?php echo $eid ?>;
var fid = <?php echo $fid ?>;
var record_event_stats = <?php echo ZM_RECORD_EVENT_STATS ?>;
var alarmFrame = <?php echo $alarmFrame ?>;
