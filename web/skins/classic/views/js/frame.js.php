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

var statHeaderStrings = {};
statHeaderStrings.ZoneName = "<?php echo translate('Zone') ?>";
statHeaderStrings.PixelDiff = "<?php echo translate('PixelDiff') ?>";
statHeaderStrings.AlarmPixels = "<?php echo translate('AlarmPx') ?>";
statHeaderStrings.FilterPixels = "<?php echo translate('FilterPx') ?>";
statHeaderStrings.BlobPixels = "<?php echo translate('BlobPx') ?>";
statHeaderStrings.Blobs = "<?php echo translate('Blobs') ?>";
statHeaderStrings.BlobSizes = "<?php echo translate('BlobSizes') ?>";
statHeaderStrings.AlarmLimits = "<?php echo translate('AlarmLimits') ?>";
statHeaderStrings.Score = "<?php echo translate('Score') ?>";

