<?php
// This is the HTML representing the Save data confirmation modal on the Monitor page and other pages

$saveTextKey = isset($_REQUEST['key']) ? $_REQUEST['key'] : 'ConfirmAction';

?>  
<div id="saveConfirm" class="modal fade" class="modal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo translate('ConfirmAction') ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p><?php echo translate($saveTextKey) ?></p>
      </div>
      <div class="modal-footer">
        <button id="saveCancelBtn" type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo translate('Cancel') ?></button>
        <button id ="dontSaveBtn" type="button" class="btn btn-danger"><?php echo translate('DontSave') ?></button>
        <button id ="saveConfirmBtn" type="button" class="btn btn-success"><?php echo translate('Save') ?></button>
      </div>
    </div>
  </div>
</div>
