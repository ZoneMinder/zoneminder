<?php
// Return an Error No Permissions Modal

?>
<div id="ENoPerm" class="modal fade" id="staticBackdrop" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">ZoneMinder <?php echo translate('Error') ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p><?php echo translate('YouNoPerms') ?></p>
        <p><?php echo translate('ContactAdmin') ?></p>
      </div>
      <div class="modal-footer">
        <button type="button" id="enpCloseBtn" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

