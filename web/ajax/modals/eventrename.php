<?php
if (!canEdit('Events')) return;

$eid = isset($_REQUEST['eid']) ? $_REQUEST['eid'] : '';
$eid = validInt($eid);
$Event = new ZM\Event($eid);
?>
<div class="modal" id="eventRenameModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo translate('Rename') .' '. translate('Event') ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <input type="text" value="<?php echo validHtmlStr($Event->Name()) ?>"/>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id="eventRenameBtn"><?php echo translate('Save') ?></button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo translate('Cancel') ?></button>
      </div>
    </div>
  </div>
</div>
