<?php
  global $snapshot;
?>
var snapshot = <?php echo json_encode($snapshot); ?>;

var eventDataStrings = {
    Id: '<?php echo translate('EventId') ?>',
    Name: '<?php echo translate('EventName') ?>',
    MonitorId: '<?php echo translate('AttrMonitorId') ?>',
    MonitorName: '<?php echo translate('AttrMonitorName') ?>',
    Cause: '<?php echo translate('Cause') ?>',
    StartDateTimeFmt: '<?php echo translate('AttrStartTime') ?>',
    Length: '<?php echo translate('Duration') ?>',
    Frames: '<?php echo translate('AttrFrames') ?>',
    AlarmFrames: '<?php echo translate('AttrAlarmFrames') ?>',
    TotScore: '<?php echo translate('AttrTotalScore') ?>',
    AvgScore: '<?php echo translate('AttrAvgScore') ?>',
    MaxScore: '<?php echo translate('AttrMaxScore') ?>',
    DiskSpace: '<?php echo translate('DiskSpace') ?>',
    Storage: '<?php echo translate('Storage') ?>',
    ArchivedStr: '<?php echo translate('Archived') ?>',
    EmailedStr: '<?php echo translate('Emailed') ?>'
};

// Strings
//
var deleteString = "<?php echo validJsStr(translate('Delete')) ?>";
var causeString = "<?php echo validJsStr(translate('AttrCause')) ?>";
var downloadProgressString = "<?php echo validJsStr(translate('Downloading')) ?>";
var downloadFailedString = '<?php echo translate('Download Failed') ?>';
var downloadSucceededString = '<?php echo translate('Download Succeeded') ?>';

var WEB_LIST_THUMB_WIDTH = '<?php echo ZM_WEB_LIST_THUMB_WIDTH ?>';
var WEB_LIST_THUMB_HEIGHT = '<?php echo ZM_WEB_LIST_THUMB_HEIGHT ?>';
