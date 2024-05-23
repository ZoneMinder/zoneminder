<?php
// This is the HTML representing the Delete confirmation modal on the Events page and other pages

$delTextKey = isset($_REQUEST['key']) ? $_REQUEST['key'] : 'ConfirmDeleteEvents';

?>  
<div id="deleteConfirm" class="modal fade" class="modal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo translate('ConfirmDeleteTitle') ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p><?php echo translate($delTextKey) ?></p>
      </div>
      <div id="deleteProgressTicker"></div>
      <div class="modal-footer">
        <button id="delCancelBtn" type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo translate('Cancel') ?></button>
        <button id ="delConfirmBtn" type="button" class="btn btn-danger"><?php echo translate('Delete') ?></button>
      </div>
    </div>
  </div>
</div>

