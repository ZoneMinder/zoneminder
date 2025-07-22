<?php
// This is the HTML representing the Unarchive confirmation modal on the Events page and other pages

$unarchiveTextKey = isset($_REQUEST['key']) ? $_REQUEST['key'] : 'ConfirmUnarchiveEvents';

?>  
<div id="unarchiveConfirm" class="modal fade" class="modal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo translate('ConfirmUnarchiveTitle') ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p><?php echo translate($unarchiveTextKey) ?></p>
      </div>
      <div id="unarchiveProgressTicker"></div>
      <div class="modal-footer">
        <button id="unarchiveCancelBtn" type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo translate('Cancel') ?></button>
        <button id ="unarchiveConfirmBtn" type="button" class="btn btn-danger"><?php echo translate('Unarchive') ?></button>
      </div>
    </div>
  </div>
</div>

