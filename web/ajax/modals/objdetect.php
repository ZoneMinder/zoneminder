<?php
// This is the HTML representing the Object Detection modal on the Events page

$eid = isset($_REQUEST['eid']) ? $_REQUEST['eid'] : '';

if ( !validInt($eid) ) {
  ZM\Error("Invalid event id: $eid");
  return;
}

$Event = ZM\Event::find_one(array('Id'=>$eid));
if ( !$Event or !$Event->canView() ) {
  // An event the user may not view is treated as unknown, as views/image.php does.
  ZM\Error("Event $eid not found");
  return;
}

$remote = defined('ZM_SERVER_ID') && $Event->Server()->Id() != ZM_SERVER_ID;
$has_image = true;
if ( !$remote ) {
  $path = $Event->Path();
  $has_image = file_exists($path.'/objdetect.gif') || file_exists($path.'/objdetect.jpg');
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
<?php if ( $has_image ) { ?>
        <img src="?view=image&amp;eid=<?php echo $eid ?>&amp;fid=objdetect">
<?php } else { ?>
        <p class="text-muted">No object detection image is available for this event.</p>
<?php } ?>
      </div>
      <div class="modal-footer">
<?php if (defined('ZM_OPT_TRAINING') and ZM_OPT_TRAINING) { ?>
        <a href="?view=training&eid=<?php echo $eid ?>" class="btn btn-primary mr-auto"><i class="fa fa-crosshairs"></i> <?php echo translate('ObjectTraining') ?></a>
<?php } ?>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
