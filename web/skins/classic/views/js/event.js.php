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
?>

//
// PHP variables to JS
//
var connKey = '<?php echo validJsStr($connkey) ?>';

var eventData = {
<?php if ( $Event->Id() ) { ?>
    Id: '<?php echo validJsStr($Event->Id()) ?>',
    Name: <?php echo json_encode($Event->Name(), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>,
    MonitorId: '<?php echo validJsStr($Event->MonitorId()) ?>',
    MonitorName: '<?php echo validJsStr($monitor->Name()) ?>',
    Cause: '<?php echo validHtmlStr($Event->Cause()) ?>',
    <!-- Tags: '<?php echo validHtmlStr(implode(', ', array_map(function($t){return $t->Name();}, $Event->Tags()))); ?>', -->
    Notes: <?php echo json_encode($Event->Notes(), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>,
    Width: '<?php echo validJsStr($Event->Width()) ?>',
    Height: '<?php echo validJsStr($Event->Height()) ?>',
    Length: '<?php echo validJsStr($Event->Length()) ?>',
    StartDateTime: '<?php echo validJsStr($Event->StartDateTime()) ?>',
    StartDateTimeFormatted: '<?php echo $dateTimeFormatter->format(strtotime($Event->StartDateTime())) ?>',
    EndDateTime: '<?php echo validJsStr($Event->EndDateTime()) ?>',
    EndDateTimeFormatted: '<?php echo $Event->EndDateTime()? $dateTimeFormatter->format(strtotime($Event->EndDateTime())) : '' ?>',
    Frames: '<?php echo validJsStr($Event->Frames()) ?>',
    AlarmFrames: '<?php echo validJsStr($Event->AlarmFrames()) ?>',
    TotScore: '<?php echo validJsStr($Event->TotScore()) ?>',
    AvgScore: '<?php echo validJsStr($Event->AvgScore()) ?>',
    MaxScore: '<?php echo validJsStr($Event->MaxScore()) ?>',
    DiskSpace: '<?php echo human_filesize($Event->DiskSpace(null)) ?>',
    Storage: '<?php echo validHtmlStr($Event->Storage()->Name()).( $Event->SecondaryStorageId() ? ', '.validHtmlStr($Event->SecondaryStorage()->Name()) : '' ) ?>',
    DefaultVideo: '<?php echo validHtmlStr($Event->DefaultVideo()) ?>',
    Archived: <?php echo $Event->Archived?'true':'false' ?>,
    Emailed: <?php echo $Event->Emailed?'true':'false' ?>,
    Path: '<?php echo validJsStr($Event->Path()) ?>',
    Latitude: '<?php echo validJsStr($Event->Latitude()) ?>',
    Longitude: '<?php echo validJsStr($Event->Longitude()) ?>',
    whatDisplay: '<?php echo validJsStr($monitor->WhatDisplay()) ?>'
<?php } ?>
};

var yesStr = '<?php echo translate('Yes') ?>';
var noStr = '<?php echo translate('No') ?>';

var eventDataStrings = {
    <!--Id: '<?php echo translate('EventId') ?>',-->
    Name: '<?php echo translate('Name') ?>',
    MonitorId: '<?php echo translate('AttrMonitorId') ?>',
    MonitorName: '<?php echo translate('Monitor') ?>',
    Cause: '<?php echo translate('Cause') ?>',
    <!-- Tags is not necessary since tags are displayed above -->
    <!-- Tags: '<?php echo translate('Tags') ?>', -->  
    Notes: '<?php echo translate('Notes') ?>',
    StartDateTimeFormatted: '<?php echo translate('AttrStartTime') ?>',
    EndDateTimeFormatted: '<?php echo translate('AttrEndTime') ?>',
    Length: '<?php echo translate('Duration') ?>',
    Frames: '<?php echo translate('AttrFrames') ?>',
    <!--AlarmFrames: '<?php echo translate('AttrAlarmFrames') ?>',-->
    <!--TotScore: '<?php echo translate('AttrTotalScore') ?>',-->
    <!--AvgScore: '<?php echo translate('AttrAvgScore') ?>',-->
    <!--MaxScore: '<?php echo translate('AttrMaxScore') ?>',-->
    Score: '<?php echo translate('Score') ?>',
    Resolution: '<?php echo translate('Resolution') ?>',
    DiskSpace: '<?php echo translate('DiskSpace') ?>',
    <!--Storage: '<?php echo translate('Storage') ?>',-->
    Path: '<?php echo translate('Path') ?>',
    <!--Archived: '<?php echo translate('Archived') ?>',-->
    <!--Emailed: '<?php echo translate('Emailed') ?>'-->
    Info: '<?php echo translate('Info') ?>'
};
if ( parseInt(ZM_OPT_USE_GEOLOCATION) ) {
  eventDataStrings.Location = '<?php echo translate('Location') ?>';
}

var monitorUrl = '<?php echo $Event->Server()->UrlToIndex(); ?>';

var filterQuery = '<?php echo isset($filterQuery)?validJsStr(htmlspecialchars_decode($filterQuery)):'' ?>';
var sortQuery = '<?php echo isset($sortQuery)?validJsStr(htmlspecialchars_decode($sortQuery)):'' ?>';

var rates = <?php echo json_encode(array_keys($rates)) ?>;
var rate = '<?php echo validJsStr($rate) ?>'; // really only used when setting up initial playback rate.
var scale = "<?php echo validJsStr($scale) ?>";
var LabelFormat = "<?php echo validJsStr($monitor->LabelFormat())?>";

var streamTimeout = <?php echo 1000*ZM_WEB_REFRESH_STATUS ?>;

var canStreamNative = <?php echo canStreamNative()?'true':'false' ?>;
var streamMode = '<?php echo validJsStr($streamMode) ?>';

//
// Strings
//
var deleteString = "<?php echo validJsStr(translate('Delete')) ?>";
var causeString = "<?php echo validJsStr(translate('AttrCause')) ?>";
var showZonesString = "<?php echo validJsStr(translate('Show Zones'))?>";
var hideZonesString = "<?php echo validJsStr(translate('Hide Zones'))?>";
var WEB_LIST_THUMB_WIDTH = '<?php echo ZM_WEB_LIST_THUMB_WIDTH ?>';
var WEB_LIST_THUMB_HEIGHT = '<?php echo ZM_WEB_LIST_THUMB_HEIGHT ?>';
var popup = '<?php echo validJsStr($popup) ?>';

var translate = {
  "seconds": "<?php echo translate('seconds') ?>",
  "Fullscreen": "<?php echo translate('Fullscreen') ?>",
  "Exit Fullscreen": "<?php echo translate('Exit Fullscreen') ?>",
  "Live": "<?php echo translate('Live') ?>",
  "Edit": "<?php echo translate('Edit') ?>",
  "All Events": "<?php echo translate('All Events') ?>",
  "Info": "<?php echo translate('Info') ?>",
  "Archived": "<?php echo translate('Archived') ?>",
  "Emailed": "<?php echo translate('Emailed') ?>",
};

