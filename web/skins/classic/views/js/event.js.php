<?php
  global $connkey;
  global $Event;
  global $monitor;
  global $filterQuery;
  global $sortQuery;
  global $rates;
  global $rate;
  global $scale;
  global $streamMode;
  global $popup;
?>
//
// Import constants
//
var CMD_NONE = <?php echo CMD_NONE ?>;
var CMD_PAUSE = <?php echo CMD_PAUSE ?>;
var CMD_PLAY = <?php echo CMD_PLAY ?>;
var CMD_VARPLAY = <?php echo CMD_VARPLAY ?>;
var CMD_STOP = <?php echo CMD_STOP ?>;
var CMD_FASTFWD = <?php echo CMD_FASTFWD ?>;
var CMD_SLOWFWD = <?php echo CMD_SLOWFWD ?>;
var CMD_SLOWREV = <?php echo CMD_SLOWREV ?>;
var CMD_FASTREV = <?php echo CMD_FASTREV ?>;
var CMD_ZOOMIN = <?php echo CMD_ZOOMIN ?>;
var CMD_ZOOMOUT = <?php echo CMD_ZOOMOUT ?>;
var CMD_PAN = <?php echo CMD_PAN ?>;
var CMD_SCALE = <?php echo CMD_SCALE ?>;
var CMD_PREV = <?php echo CMD_PREV ?>;
var CMD_NEXT = <?php echo CMD_NEXT ?>;
var CMD_SEEK = <?php echo CMD_SEEK ?>;
var CMD_QUERY = <?php echo CMD_QUERY ?>;

var SCALE_BASE = <?php echo SCALE_BASE ?>;

//
// PHP variables to JS
//
var connKey = '<?php echo $connkey ?>';

var eventData = {
<?php if ( $Event->Id() ) { ?>
    Id: '<?php echo $Event->Id() ?>',
    Name: '<?php echo $Event->Name() ?>',
    MonitorId: '<?php echo $Event->MonitorId() ?>',
    MonitorName: '<?php echo validJsStr($monitor->Name()) ?>',
    Cause: '<?php echo validHtmlStr($Event->Cause()) ?>',
    Notes: '<?php echo $Event->Notes()?>',
    Width: '<?php echo $Event->Width() ?>',
    Height: '<?php echo $Event->Height() ?>',
    Length: '<?php echo $Event->Length() ?>',
    StartDateTime: '<?php echo $Event->StartDateTime() ?>',
    StartDateTimeShort: '<?php echo strftime(STRF_FMT_DATETIME_SHORT, strtotime($Event->StartDateTime())) ?>',
    EndDateTime: '<?php echo $Event->EndDateTime() ?>',
    EndDateTimeShort: '<?php echo $Event->EndDateTime()? strftime(STRF_FMT_DATETIME_SHORT, strtotime($Event->EndDateTime())) : '' ?>',
    Frames: '<?php echo $Event->Frames() ?>',
    AlarmFrames: '<?php echo $Event->AlarmFrames() ?>',
    TotScore: '<?php echo $Event->TotScore() ?>',
    AvgScore: '<?php echo $Event->AvgScore() ?>',
    MaxScore: '<?php echo $Event->MaxScore() ?>',
    DiskSpace: '<?php echo human_filesize($Event->DiskSpace(null)) ?>',
    Storage: '<?php echo validHtmlStr($Event->Storage()->Name()).( $Event->SecondaryStorageId() ? ', '.validHtmlStr($Event->SecondaryStorage()->Name()) : '' ) ?>',
    Archived: <?php echo $Event->Archived?'true':'false' ?>,
    Emailed: <?php echo $Event->Emailed?'true':'false' ?>
<?php } ?>
};

var yesStr = '<?php echo translate('Yes') ?>';
var noStr = '<?php echo translate('No') ?>';

var eventDataStrings = {
    Id: '<?php echo translate('EventId') ?>',
    Name: '<?php echo translate('EventName') ?>',
    MonitorId: '<?php echo translate('AttrMonitorId') ?>',
    MonitorName: '<?php echo translate('AttrMonitorName') ?>',
    Cause: '<?php echo translate('Cause') ?>',
    Notes: '<?php echo translate('Notes') ?>',
    StartDateTimeShort: '<?php echo translate('AttrStartTime') ?>',
    EndDateTimeShort: '<?php echo translate('AttrEndTime') ?>',
    Length: '<?php echo translate('Duration') ?>',
    Frames: '<?php echo translate('AttrFrames') ?>',
    AlarmFrames: '<?php echo translate('AttrAlarmFrames') ?>',
    TotScore: '<?php echo translate('AttrTotalScore') ?>',
    AvgScore: '<?php echo translate('AttrAvgScore') ?>',
    MaxScore: '<?php echo translate('AttrMaxScore') ?>',
    DiskSpace: '<?php echo translate('DiskSpace') ?>',
    Storage: '<?php echo translate('Storage') ?>',
    Archived: '<?php echo translate('Archived') ?>',
    Emailed: '<?php echo translate('Emailed') ?>'
};

var monitorUrl = '<?php echo $Event->Server()->UrlToIndex(); ?>';

var filterQuery = '<?php echo isset($filterQuery)?validJsStr(htmlspecialchars_decode($filterQuery)):'' ?>';
var sortQuery = '<?php echo isset($sortQuery)?validJsStr(htmlspecialchars_decode($sortQuery)):'' ?>';

var rates = <?php echo json_encode(array_keys($rates)) ?>;
var rate = '<?php echo $rate ?>'; // really only used when setting up initial playback rate.
var scale = "<?php echo $scale ?>";
var LabelFormat = "<?php echo validJsStr($monitor->LabelFormat())?>";

var streamTimeout = <?php echo 1000*ZM_WEB_REFRESH_STATUS ?>;

var canStreamNative = <?php echo canStreamNative()?'true':'false' ?>;
var streamMode = '<?php echo $streamMode ?>';

//
// Strings
//
var deleteString = "<?php echo validJsStr(translate('Delete')) ?>";
var causeString = "<?php echo validJsStr(translate('AttrCause')) ?>";
var showZonesString = "<?php echo validJsStr(translate('Show Zones'))?>";
var hideZonesString = "<?php echo validJsStr(translate('Hide Zones'))?>";
var WEB_LIST_THUMB_WIDTH = '<?php echo ZM_WEB_LIST_THUMB_WIDTH ?>';
var WEB_LIST_THUMB_HEIGHT = '<?php echo ZM_WEB_LIST_THUMB_HEIGHT ?>';
var popup = '<?php echo $popup ?>';
