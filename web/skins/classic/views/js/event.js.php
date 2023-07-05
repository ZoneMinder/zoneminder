<?php
  ini_set('display_errors', '0');
  global $dateTimeFormatter;
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
  global $player;
?>

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
    Notes: `<?php echo $Event->Notes()?>`,
    Width: '<?php echo $Event->Width() ?>',
    Height: '<?php echo $Event->Height() ?>',
    Length: '<?php echo $Event->Length() ?>',
    StartDateTime: '<?php echo $Event->StartDateTime() ?>',
    StartDateTimeFormatted: '<?php echo $dateTimeFormatter->format(strtotime($Event->StartDateTime())) ?>',
    EndDateTime: '<?php echo $Event->EndDateTime() ?>',
    EndDateTimeFormatted: '<?php echo $Event->EndDateTime()? $dateTimeFormatter->format(strtotime($Event->EndDateTime())) : '' ?>',
    Frames: '<?php echo $Event->Frames() ?>',
    AlarmFrames: '<?php echo $Event->AlarmFrames() ?>',
    TotScore: '<?php echo $Event->TotScore() ?>',
    AvgScore: '<?php echo $Event->AvgScore() ?>',
    MaxScore: '<?php echo $Event->MaxScore() ?>',
    DiskSpace: '<?php echo human_filesize($Event->DiskSpace(null)) ?>',
    Storage: '<?php echo validHtmlStr($Event->Storage()->Name()).( $Event->SecondaryStorageId() ? ', '.validHtmlStr($Event->SecondaryStorage()->Name()) : '' ) ?>',
    Archived: <?php echo $Event->Archived?'true':'false' ?>,
    Emailed: <?php echo $Event->Emailed?'true':'false' ?>,
    DefaultVideo: '<?php echo $Event->DefaultVideo() ?>',
    Path: '<?php echo $Event->Path() ?>'
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
    StartDateTimeFormatted: '<?php echo translate('AttrStartTime') ?>',
    EndDateTimeFormatted: '<?php echo translate('AttrEndTime') ?>',
    Length: '<?php echo translate('Duration') ?>',
    Frames: '<?php echo translate('AttrFrames') ?>',
    AlarmFrames: '<?php echo translate('AttrAlarmFrames') ?>',
    TotScore: '<?php echo translate('AttrTotalScore') ?>',
    AvgScore: '<?php echo translate('AttrAvgScore') ?>',
    MaxScore: '<?php echo translate('AttrMaxScore') ?>',
    Resolution: '<?php echo translate('Resolution') ?>',
    DiskSpace: '<?php echo translate('DiskSpace') ?>',
    Storage: '<?php echo translate('Storage') ?>',
    Path: '<?php echo translate('Path') ?>',
    Archived: '<?php echo translate('Archived') ?>',
    Emailed: '<?php echo translate('Emailed') ?>'
};

var monitorUrl = '<?php echo $Event->Storage()->Server()->UrlToIndex(); ?>';
var videoUrl = '<?php echo $Event->getStreamSrc(array('mode'=>'mpeg','format'=>'h264'),'&'); ?>';

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
const deleteString = "<?php echo validJsStr(translate('Delete')) ?>";
const causeString = "<?php echo validJsStr(translate('AttrCause')) ?>";
const showZonesString = "<?php echo validJsStr(translate('Show Zones'))?>";
const hideZonesString = "<?php echo validJsStr(translate('Hide Zones'))?>";
const WEB_LIST_THUMB_WIDTH = '<?php echo ZM_WEB_LIST_THUMB_WIDTH ?>';
const WEB_LIST_THUMB_HEIGHT = '<?php echo ZM_WEB_LIST_THUMB_HEIGHT ?>';
const popup = '<?php echo $popup ?>';
const playerType = '<?php echo $player ?>';

var translate = {
  "seconds": "<?php echo translate('seconds') ?>",
  "Fullscreen": "<?php echo translate('Fullscreen') ?>",
  "Exit Fullscreen": "<?php echo translate('Exit Fullscreen') ?>",
};
