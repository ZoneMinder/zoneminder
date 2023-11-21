<?php
// This is the HTML representing the Are You Still Watching modal
?>  
<div id="AYSWModal" class="modal fade" class="modal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo translate('Are you still watching?') ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p><?php echo translate('Video paused. Continue watching?') ?></p>
      </div>
      <div class="modal-footer">
        <button id="AYSWYesBtn" type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo translate('Yes') ?></button>
      </div>
    </div>
  </div>
</div>

