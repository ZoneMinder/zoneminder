<?php
// This is the HTML representing the Object Detection modal on the Events page

$eid = isset($_REQUEST['eid']) ? $_REQUEST['eid'] : '';

if ( !validInt($eid) ) {
  ZM\Error("Invalid event id: $eid");
  return;
}

?>
<div id="objdetectModal" class="modal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Object Detection</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <img src="?view=image&amp;eid=<?php echo $eid ?>&amp;fid=objdetect">
      </div>
      <div class="modal-footer">
<?php if (defined('ZM_OPT_TRAINING') and ZM_OPT_TRAINING) { ?>
        <a href="?view=event&eid=<?php echo $eid ?>&annotate=1" class="btn btn-primary mr-auto"><i class="fa fa-crosshairs"></i> <?php echo translate('ObjectTraining') ?></a>
<?php } ?>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
