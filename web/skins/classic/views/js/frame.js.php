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

var statHeaderStrings = {
    EventId: '<?php echo translate('EventId') ?>',
    FrameId: '<?php echo translate('FrameId') ?>',
    ZoneName: '<?php echo translate('Zone') ?>',
    PixelDiff: '<?php echo translate('PixelDiff') ?>',
    AlarmPixels: '<?php echo translate('AlarmPx') ?>',
    FilterPixels: '<?php echo translate('FilterPx') ?>',
    BlobPixels: '<?php echo translate('BlobPx') ?>',
    Blobs: '<?php echo translate('Blobs') ?>',
    BlobSizes: '<?php echo translate('BlobSizes') ?>',
    AlarmLimits: '<?php echo translate('AlarmLimits') ?>',
    Score: '<?php echo translate('Score') ?>'
};
