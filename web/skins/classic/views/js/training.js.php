<?php
  ini_set('display_errors', '0');
  global $Event;
  global $monitor;
?>

var eventData = {
<?php if ($Event->Id()) { ?>
    Id: '<?php echo $Event->Id() ?>',
    Name: '<?php echo $Event->Name() ?>',
    MonitorId: '<?php echo $Event->MonitorId() ?>',
    MonitorName: '<?php echo validJsStr($monitor->Name()) ?>',
    Width: '<?php echo $Event->Width() ?>',
    Height: '<?php echo $Event->Height() ?>',
    Frames: '<?php echo $Event->Frames() ?>',
    Path: '<?php echo $Event->Path() ?>'
<?php } ?>
};

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
  "TrainingNoObjects": "<?php echo translate('TrainingNoObjects') ?>",
  "TrainingObjects": "<?php echo translate('TrainingObjects') ?>",
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

<?php if ($Event->Id()) { ?>
$j(document).ready(function initTrainingView() {
  if (typeof AnnotationEditor === 'undefined') return;

  var annotationEditor = new AnnotationEditor({
    canvasId: 'annotationCanvas',
    sidebarId: 'annotationObjectList',
    eventId: eventData.Id,
    translations: trainingTranslations
  });
  annotationEditor.init();

  // In training view, the panel is always open. Load the initial frame.
  var urlFrame = new URLSearchParams(window.location.search).get('frame');
  annotationEditor.open(urlFrame || undefined);

  // Show browse panel by default
  setTimeout(function() { annotationEditor.browseTrainingData(); }, 200);

  $j('#annotationSaveBtn').on('click', function() {
    annotationEditor.save();
  });

  $j('#annotationCancelBtn').on('click', function() {
    // In training view, cancel navigates back to event view
    if (annotationEditor.dirty) {
      if (!confirm(trainingTranslations.TrainingUnsaved)) return;
      annotationEditor.dirty = false;
    }
    window.location.assign('?view=event&eid=' + eventData.Id);
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

  // Back button dirty-check
  $j('#backToEventBtn').on('click', function(e) {
    if (annotationEditor.dirty) {
      e.preventDefault();
      if (!confirm(trainingTranslations.TrainingUnsaved)) return;
      annotationEditor.dirty = false;
      window.location.assign(this.href);
    }
  });
});
<?php } ?>
