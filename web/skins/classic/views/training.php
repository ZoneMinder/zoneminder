<?php
//
// ZoneMinder web training view file
// Dedicated annotation/training view launched from event view.
//

if (!canView('Events')) {
  $view = 'error';
  return;
}

if (!defined('ZM_OPT_TRAINING') || !ZM_OPT_TRAINING) {
  $view = 'error';
  return;
}

require_once('includes/Event.php');

$eid = validInt($_REQUEST['eid']);
$Event = new ZM\Event($eid);
$monitor = $Event->Monitor();

if (!$Event->Id()) {
  $view = 'error';
  return;
}

if (!$monitor->canView()) {
  $view = 'error';
  return;
}

xhtmlHeaders(__FILE__, translate('ObjectTraining').' - '.$Event->Id());
getBodyTopHTML();
?>
  <div id="page">
    <?php echo getNavBarHTML() ?>
    <div id="content">

    <div class="d-flex flex-row flex-wrap justify-content-between px-3 py-1">
      <div id="toolbar">
        <a id="backToEventBtn" class="btn btn-normal" href="?view=event&eid=<?php echo $Event->Id() ?>" title="<?php echo translate('Back') ?>"><i class="fa fa-arrow-left"></i></a>
        <span class="training-view-title"><?php echo translate('ObjectTraining') ?> &mdash; <?php echo translate('Event') ?> <?php echo $Event->Id() ?></span>
      </div>
    </div>

    <div id="trainingLayout">
      <div id="annotationPanel" class="open">
        <div id="annotationFrameSelector" class="annotation-frame-selector">
          <span><strong><?php echo translate('Frame') ?>:</strong></span>
          <button class="btn btn-normal btn-sm" data-frame="skip-back" title="<?php echo translate('TrainingSkipBack') ?>"><i class="fa fa-backward"></i></button>
          <button class="btn btn-normal btn-sm" data-frame="prev" title="<?php echo translate('PreviousFrame') ?>"><i class="fa fa-caret-left fa-lg"></i></button>
          <button class="btn btn-normal btn-sm frame-btn" data-frame="alarm" style="display:none"><?php echo translate('Alarm') ?></button>
          <button class="btn btn-normal btn-sm frame-btn" data-frame="snapshot" style="display:none"><?php echo translate('Snapshot') ?></button>
          <button class="btn btn-normal btn-sm" data-frame="next" title="<?php echo translate('NextFrame') ?>"><i class="fa fa-caret-right fa-lg"></i></button>
          <button class="btn btn-normal btn-sm" data-frame="skip-forward" title="<?php echo translate('TrainingSkipForward') ?>"><i class="fa fa-forward"></i></button>
          <button class="btn btn-normal btn-sm" id="annotationBrowseFramesBtn" title="<?php echo translate('TrainingBrowseFrames') ?>"><i class="fa fa-th"></i></button>
          <input type="number" class="form-control form-control-sm frame-input" id="annotationFrameInput" min="1" max="<?php echo $Event->Frames() ?>" placeholder="#" title="<?php echo translate('GoToFrame') ?>"/>
          <button class="btn btn-normal btn-sm" id="annotationGoToFrame"><?php echo translate('GoToFrame') ?></button>
          <span class="frame-total"></span>
        </div>

        <div class="annotation-workspace">
          <div class="annotation-canvas-container">
            <canvas id="annotationCanvas"></canvas>
          </div>

          <div class="annotation-sidebar-wrap">
            <div class="annotation-sidebar">
              <div class="annotation-sidebar-header">
                <button id="annotationDeleteAllBtn" class="btn-delete-all" title="<?php echo translate('TrainingDeleteAll') ?>"><i class="fa fa-trash"></i></button>
                <span><?php echo translate('Objects') ?></span>
                <button id="annotationBrowseBtn" class="btn-browse" title="<?php echo translate('TrainingBrowse') ?>"><i class="fa fa-folder-open"></i></button>
              </div>
              <ul id="annotationObjectList" class="annotation-object-list">
              </ul>
              <div id="annotationStats" class="annotation-stats">
              </div>
            </div>
            <div id="annotationStatus" class="annotation-status"></div>
          </div>
        </div>

        <div id="annotationFrameInfo" class="annotation-frame-info"></div>
        <div class="annotation-hint"><?php echo translate('TrainingShiftDrawHint') ?></div>

        <div class="annotation-actions">
          <button id="annotationDetectBtn" class="btn btn-warning btn-sm" style="display:none" title="<?php echo translate('TrainingDetectObjects') ?>"><i class="fa fa-search"></i> <?php echo translate('TrainingDetect') ?></button>
          <button id="annotationDeleteBtn" class="btn btn-danger btn-sm"><?php echo translate('TrainingDeleteBox') ?></button>
          <button id="annotationSaveBtn" class="btn btn-success btn-sm"><?php echo translate('TrainingSave') ?></button>
          <button id="annotationCancelBtn" class="btn btn-normal btn-sm"><?php echo translate('Exit') ?></button>
        </div>
      </div>
    </div>

    </div><!--content-->
  </div><!--page-->
<?php
  xhtmlFooter();
?>
