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
var connKey = '<?php echo $connkey ?>';

var eventData = {
<?php if ( $Event->Id() ) { ?>
    Id: '<?php echo $Event->Id() ?>',
    Name: '<?php echo $Event->Name() ?>',
    MonitorId: '<?php echo $Event->MonitorId() ?>',
    MonitorName: '<?php echo validJsStr($monitor->Name()) ?>',
    Cause: '<?php echo validHtmlStr($Event->Cause()) ?>',
    <!-- Tags: '<?php echo validHtmlStr(implode(', ', array_map(function($t){return $t->Name();}, $Event->Tags()))); ?>', -->
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
    DefaultVideo: '<?php echo validHtmlStr($Event->DefaultVideo()) ?>',
    Archived: <?php echo $Event->Archived?'true':'false' ?>,
    Emailed: <?php echo $Event->Emailed?'true':'false' ?>,
    Path: '<?php echo $Event->Path() ?>',
    Latitude: '<?php echo $Event->Latitude() ?>',
    Longitude: '<?php echo $Event->Longitude() ?>'
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

<?php if (defined('ZM_OPT_TRAINING') and ZM_OPT_TRAINING) { ?>
var trainingTranslations = {
  "Annotate": "<?php echo translate('Annotate') ?>",
  "AnnotationSaved": "<?php echo translate('AnnotationSaved') ?>",
  "AnnotationsRemoved": "<?php echo translate('AnnotationsRemoved') ?>",
  "DeleteBox": "<?php echo translate('DeleteBox') ?>",
  "DrawBox": "<?php echo translate('DrawBox') ?>",
  "GoToFrame": "<?php echo translate('GoToFrame') ?>",
  "NewLabel": "<?php echo translate('NewLabel') ?>",
  "NoDetectionData": "<?php echo translate('NoDetectionData') ?>",
  "SaveToTrainingSet": "<?php echo translate('SaveToTrainingSet') ?>",
  "SelectLabel": "<?php echo translate('SelectLabel') ?>",
  "UnsavedAnnotations": "<?php echo translate('UnsavedAnnotations') ?>",
  "TrainingDataStats": "<?php echo translate('TrainingDataStats') ?>",
  "TotalAnnotatedImages": "<?php echo translate('TotalAnnotatedImages') ?>",
  "TotalClasses": "<?php echo translate('TotalClasses') ?>",
  "ImagesPerClass": "<?php echo translate('ImagesPerClass') ?>",
  "TrainingGuidance": "<?php echo translate('TrainingGuidance') ?>",
  "Detect": "<?php echo translate('Detect') ?>",
  "DetectObjects": "<?php echo translate('DetectObjects') ?>",
  "DetectRunning": "<?php echo translate('DetectRunning') ?>",
  "DetectNoScript": "<?php echo translate('DetectNoScript') ?>",
  "DetectNoResults": "<?php echo translate('DetectNoResults') ?>"
};
<?php } ?>

<?php if (defined('ZM_OPT_TRAINING') and ZM_OPT_TRAINING && $Event->Id()) { ?>
// Training annotation editor initialization
// Deferred until DOM ready (event.js handles $j(document).ready)
$j(document).ready(function initAnnotationEditor() {
  if (typeof AnnotationEditor === 'undefined') return;

  var annotationEditor = new AnnotationEditor({
    canvasId: 'annotationCanvas',
    sidebarId: 'annotationObjectList',
    eventId: eventData.Id,
    translations: trainingTranslations
  });
  annotationEditor.init();

  // Make accessible globally for debugging
  window.annotationEditor = annotationEditor;

  $j('#annotateBtn').on('click', function() {
    var panel = document.getElementById('annotationPanel');
    if (panel && panel.classList.contains('open')) {
      annotationEditor.close();
    } else {
      annotationEditor.open();
    }
  });

  $j('#annotationSaveBtn').on('click', function() {
    annotationEditor.save();
  });

  $j('#annotationCancelBtn').on('click', function() {
    annotationEditor.close();
  });

  $j('#annotationDetectBtn').on('click', function() {
    annotationEditor.detect();
  });

  $j('#annotationDeleteBtn').on('click', function() {
    if (annotationEditor.selectedIndex >= 0) {
      annotationEditor.deleteAnnotation(annotationEditor.selectedIndex);
    }
  });

  $j('#annotationLabelSelect').on('change', function() {
    if (annotationEditor.selectedIndex >= 0) {
      annotationEditor.relabelAnnotation(
        annotationEditor.selectedIndex,
        $j(this).val()
      );
    }
  });

  // Frame navigation
  $j('#annotationFrameSelector').on('click', '[data-frame]', function() {
    var frame = $j(this).data('frame');
    if (frame === 'prev') {
      var current = parseInt(annotationEditor.currentFrameId);
      if (!isNaN(current) && current > 1) {
        annotationEditor.switchFrame(String(current - 1));
      }
    } else if (frame === 'next') {
      var current = parseInt(annotationEditor.currentFrameId);
      if (!isNaN(current)) {
        annotationEditor.switchFrame(String(current + 1));
      } else {
        annotationEditor.switchFrame('1');
      }
    } else {
      annotationEditor.switchFrame(String(frame));
    }
  });

  $j('#annotationGoToFrame').on('click', function() {
    var fid = $j('#annotationFrameInput').val();
    if (fid && parseInt(fid) > 0) {
      annotationEditor.switchFrame(fid);
    }
  });

  $j('#annotationFrameInput').on('keydown', function(e) {
    if (e.key === 'Enter') {
      $j('#annotationGoToFrame').click();
    }
  });
});
<?php } ?>
