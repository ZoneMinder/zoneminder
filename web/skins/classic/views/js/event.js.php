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
  "ObjectTraining": "<?php echo translate('ObjectTraining') ?>",
  "TrainingBackgroundConfirm": "<?php echo translate('TrainingBackgroundConfirm') ?>",
  "TrainingBackgroundImages": "<?php echo translate('TrainingBackgroundImages') ?>",
  "TrainingBrowse": "<?php echo translate('TrainingBrowse') ?>",
  "TrainingBrowseFrames": "<?php echo translate('TrainingBrowseFrames') ?>",
  "TrainingConfirmDeleteFile": "<?php echo translate('TrainingConfirmDeleteFile') ?>",
  "TrainingDataDeleted": "<?php echo translate('TrainingDataDeleted') ?>",
  "TrainingDataStats": "<?php echo translate('TrainingDataStats') ?>",
  "TrainingDeleteAll": "<?php echo translate('TrainingDeleteAll') ?>",
  "TrainingDeleteBox": "<?php echo translate('TrainingDeleteBox') ?>",
  "TrainingDeleteFailed": "<?php echo translate('TrainingDeleteFailed') ?>",
  "TrainingDetect": "<?php echo translate('TrainingDetect') ?>",
  "TrainingDetectFailed": "<?php echo translate('TrainingDetectFailed') ?>",
  "TrainingDetectNoResults": "<?php echo translate('TrainingDetectNoResults') ?>",
  "TrainingDetectNoScript": "<?php echo translate('TrainingDetectNoScript') ?>",
  "TrainingDetectObjects": "<?php echo translate('TrainingDetectObjects') ?>",
  "TrainingDetectRunning": "<?php echo translate('TrainingDetectRunning') ?>",
  "TrainingDetectedObjects": "<?php echo translate('TrainingDetectedObjects') ?>",
  "TrainingFailedToLoadEvent": "<?php echo translate('TrainingFailedToLoadEvent') ?>",
  "TrainingFailedToLoadFrame": "<?php echo translate('TrainingFailedToLoadFrame') ?>",
  "TrainingGuidance": "<?php echo translate('TrainingGuidance') ?>",
  "TrainingLoadFrameFirst": "<?php echo translate('TrainingLoadFrameFirst') ?>",
  "TrainingLoading": "<?php echo translate('TrainingLoading') ?>",
  "TrainingNoData": "<?php echo translate('TrainingNoData') ?>",
  "TrainingNoFiles": "<?php echo translate('TrainingNoFiles') ?>",
  "TrainingNoFrameLoaded": "<?php echo translate('TrainingNoFrameLoaded') ?>",
  "TrainingPendingDiscard": "<?php echo translate('TrainingPendingDiscard') ?>",
  "TrainingPendingOnly": "<?php echo translate('TrainingPendingOnly') ?>",
  "TrainingPreviewUnavailable": "<?php echo translate('TrainingPreviewUnavailable') ?>",
  "TrainingRemoved": "<?php echo translate('TrainingRemoved') ?>",
  "TrainingSave": "<?php echo translate('TrainingSave') ?>",
  "TrainingSaved": "<?php echo translate('TrainingSaved') ?>",
  "TrainingSaveFailed": "<?php echo translate('TrainingSaveFailed') ?>",
  "TrainingSaving": "<?php echo translate('TrainingSaving') ?>",
  "TrainingSelectBoxFirst": "<?php echo translate('TrainingSelectBoxFirst') ?>",
  "TrainingTotalClasses": "<?php echo translate('TrainingTotalClasses') ?>",
  "TrainingTotalImages": "<?php echo translate('TrainingTotalImages') ?>",
  "TrainingUnsaved": "<?php echo translate('TrainingUnsaved') ?>",
  "AcceptDetection": "<?php echo translate('AcceptDetection') ?>",
  "ConfirmDeleteTrainingData": "<?php echo translate('ConfirmDeleteTrainingData') ?>",
  "DrawBox": "<?php echo translate('DrawBox') ?>",
  "Frame": "<?php echo translate('Frame') ?>",
  "GoToFrame": "<?php echo translate('GoToFrame') ?>",
  "ImagesPerClass": "<?php echo translate('ImagesPerClass') ?>",
  "NewLabel": "<?php echo translate('NewLabel') ?>",
  "NoDetectionData": "<?php echo translate('NoDetectionData') ?>",
  "SelectLabel": "<?php echo translate('SelectLabel') ?>"
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

  $j('#annotateBtn').on('click', function() {
    var panel = document.getElementById('annotationPanel');
    if (panel && panel.classList.contains('open')) {
      annotationEditor.close();
    } else {
      annotationEditor.open();
    }
  });

  // Auto-open annotation panel if linked with ?annotate=1&frame=FRAME_ID
  if (new URLSearchParams(window.location.search).get('annotate') === '1') {
    var urlFrame = new URLSearchParams(window.location.search).get('frame');
    annotationEditor.open(urlFrame || undefined);
  }

  $j('#annotationSaveBtn').on('click', function() {
    annotationEditor.save();
  });

  $j('#annotationCancelBtn').on('click', function() {
    annotationEditor.close();
  });

  $j('#annotationDetectBtn').on('click', function() {
    annotationEditor.detect();
  });

  $j('#annotationBrowseBtn').on('click', function() {
    annotationEditor.browseTrainingData();
  });

  $j('#annotationDeleteBtn').on('click', function() {
    if (annotationEditor.selectedIndex >= 0) {
      annotationEditor.deleteAnnotation(annotationEditor.selectedIndex);
    } else {
      annotationEditor._setStatus('<?php echo translate('TrainingSelectBoxFirst') ?>', 'error');
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
    var current = parseInt(annotationEditor.currentFrameId);
    var total = annotationEditor.totalFrames;
    if (frame === 'prev') {
      if (!isNaN(current) && current > 1) {
        annotationEditor.switchFrame(String(current - 1));
      }
    } else if (frame === 'next') {
      if (!isNaN(current) && current < total) {
        annotationEditor.switchFrame(String(current + 1));
      } else if (isNaN(current)) {
        annotationEditor.switchFrame('1');
      }
    } else if (frame === 'skip-back') {
      if (!isNaN(current)) {
        annotationEditor.switchFrame(String(Math.max(1, current - 10)));
      }
    } else if (frame === 'skip-forward') {
      if (!isNaN(current) && total > 0) {
        annotationEditor.switchFrame(String(Math.min(total, current + 10)));
      } else if (isNaN(current)) {
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

  $j('#annotationBrowseFramesBtn').on('click', function() {
    annotationEditor.browseFrames();
  });

  // Intercept event navigation so dirty-check happens BEFORE
  // the stream is torn down.  The prev/next buttons call
  // streamPrev/streamNext directly (via data-on-click-true),
  // which kill the video stream before location.replace().
  // If beforeunload fires and the user cancels, the stream is
  // already dead, leaving a blank page.  By wrapping at the
  // streamPrev/streamNext level we prompt before any teardown.
  var _wrapNav = function(fnName) {
    var orig = window[fnName];
    if (typeof orig !== 'function') return;
    window[fnName] = function() {
      if (annotationEditor.dirty) {
        if (!confirm(trainingTranslations.TrainingUnsaved)) return;
        annotationEditor.dirty = false;
      }
      orig.apply(this, arguments);
    };
  };
  _wrapNav('streamPrev');
  _wrapNav('streamNext');
});
<?php } ?>
