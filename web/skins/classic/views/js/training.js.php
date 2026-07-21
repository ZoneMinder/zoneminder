<?php
  ini_set('display_errors', '0');
  global $Event;
  global $monitor;
?>

const eventData = <?php echo $Event->Id() ? $Event->to_json() : '{}' ?>;

const trainingTranslations = <?php
  $translationKeys = [
    'ObjectTraining', 'TrainingBackgroundConfirm', 'TrainingBackgroundImages',
    'TrainingBrowse', 'TrainingBrowseFrames', 'TrainingConfirmDeleteFile',
    'TrainingDataDeleted', 'TrainingDataStats', 'TrainingDeleteAll',
    'TrainingDeleteBox', 'TrainingDeleteFailed', 'TrainingDetect',
    'TrainingDetectFailed', 'TrainingDetectNoResults', 'TrainingDetectNoScript',
    'TrainingDetectObjects', 'TrainingDetectRunning', 'TrainingDetectedObjects',
    'TrainingFailedToLoadEvent', 'TrainingFailedToLoadFrame', 'TrainingGuidance',
    'TrainingLoadFrameFirst', 'TrainingLoading', 'TrainingNoData',
    'TrainingNoFiles', 'TrainingNoObjects', 'TrainingObjects',
    'TrainingNoFrameLoaded', 'TrainingPendingDiscard', 'TrainingPendingOnly',
    'TrainingPreviewUnavailable', 'TrainingRemoved', 'TrainingSave',
    'TrainingSaved', 'TrainingSaveFailed', 'TrainingSaving',
    'TrainingSelectBoxFirst', 'TrainingTotalClasses', 'TrainingTotalImages',
    'TrainingUnsaved', 'AcceptDetection', 'ConfirmDeleteTrainingData',
    'DrawBox', 'Frame', 'GoToFrame', 'ImagesPerClass', 'NewLabel',
    'NoDetectionData', 'SelectLabel',
  ];
  $t = [];
  foreach ($translationKeys as $k) $t[$k] = translate($k);
  echo json_encode($t);
?>;

<?php if ($Event->Id()) { ?>
$j(document).ready(function initTrainingView() {
  if (typeof AnnotationEditor === 'undefined') return;

  const annotationEditor = new AnnotationEditor({
    canvasId: 'annotationCanvas',
    sidebarId: 'annotationObjectList',
    eventId: eventData.Id,
    translations: trainingTranslations
  });
  annotationEditor.init();

  // In training view, the panel is always open. Load the initial frame.
  const urlFrame = new URLSearchParams(window.location.search).get('frame');
  annotationEditor.open(urlFrame || undefined);

  // Show browse panel by default
  setTimeout(function() { annotationEditor.browseTrainingData(); }, 200);

  $j('#annotationSaveBtn').on('click', function() {
    annotationEditor.save();
  });

  $j('#annotationCancelBtn').on('click', function() {
    // In training view, cancel navigates back to event view
    if (!annotationEditor._confirmDiscardIfDirty()) return;
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
      annotationEditor._setStatus(trainingTranslations.TrainingSelectBoxFirst, 'error');
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
    const frame = $j(this).data('frame');
    const current = parseInt(annotationEditor.currentFrameId);
    const total = annotationEditor.totalFrames;
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
    const fid = $j('#annotationFrameInput').val();
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
    if (!annotationEditor._confirmDiscardIfDirty()) {
      e.preventDefault();
      return;
    }
  });
});
<?php } ?>
