<?php
// Clear Logs confirmation modal
?>
<div id="clearLogsConfirm" class="modal fade" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo translate('ConfirmClearLogsTitle') ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p><?php echo translate('ConfirmClearLogs') ?></p>
      </div>
      <div id="clearLogsProgressTicker"></div>
      <div class="modal-footer">
        <button id="clearLogsCancelBtn" type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo translate('Cancel') ?></button>
        <button id="clearLogsConfirmBtn" type="button" class="btn btn-danger"><?php echo translate('ClearLogs') ?></button>
      </div>
    </div>
  </div>
</div>
