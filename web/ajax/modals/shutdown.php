<?php
$error='';

if ( !canEdit('System') ) {
  $error = 'Insufficient permissions';
} else if ( !defined('ZM_PATH_SHUTDOWN') or ZM_PATH_SHUTDOWN == '' ) {
  $error = 'ZM_PATH_SHUTDOWN is not defined. This is normally configured in /etc/zm/conf.d/01-system-paths.conf';
} else if ( !file_exists(ZM_PATH_SHUTDOWN) ) {
  $error = 'Path does not exist for ZM_PATH_SHUTDOWN. Current value is '.ZM_PATH_SHUTDOWN;
}

if ( $error ) {
  ZM\Error($error);
  return;
}

?>
<div class="modal" id="shutdownModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo translate('Shutdown').' '.translate('Restart') ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p class="warning"><h2>Warning</h2>
          This command will either shutdown or restart all ZoneMinder Servers<br/>
        </p>
        <p>
          <input type="radio" name="when" value="now" id="whennow"/><label for="whennow">Now</label>
          <input type="radio" name="when" value="1min" id="when1min" checked="checked"/><label for="when1min">1 Minute</label>
        </p>
        <p id="respText" class="invisible">
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-command="cancel" data-on-click-this="manageShutdownBtns" id="cancelBtn" disabled><?php echo translate('Cancel') ?></button>
        <button type="button" class="btn btn-primary" data-command="restart" data-on-click-this="manageShutdownBtns"><?php echo translate('Restart') ?></button>
        <button type="button" class="btn btn-primary" data-command="shutdown" data-on-click-this="manageShutdownBtns"><?php echo translate('Shutdown') ?></button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo translate('Close') ?></button>
      </div>
    </form>
    </div>
  </div>
</div>
