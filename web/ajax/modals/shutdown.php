<?php
global $view;

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

$output_str = '';
if ( isset($output) ) {
  $output_str = '<p>'.implode('<br/>', $output).'</p>'.PHP_EOL;
}

$cancel_str = '';
if ( isset($_POST['when']) and ($_POST['when'] != 'NOW') and ($action != 'cancel') ) {
  $cancel_str = '<p>You may cancel this shutdown by clicking '.translate('Cancel').'</p>'.PHP_EOL;
}

$cancel_btn = '';
if ( isset($_POST['when']) and ($_POST['when'] != 'NOW') and ($action != 'cancel') ) {
          $cancel_btn = '<button type="submit" class="btn btn-primary" name="action" value="cancel">' .translate('Cancel'). '</button>'.PHP_EOL;
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
      <form name="contentForm" id="shutdownForm" method="post" action="?">
        <?php
        // We have to manually insert the csrf key into the form when using a modal generated via ajax call
        echo getCSRFinputHTML();
        ?>
        <input type="hidden" name="view" value="shutdown"/>
        <?php echo $output_str ?>
        <?php echo $cancel_str ?>
        <p class="warning"><h2>Warning</h2>
          This command will either shutdown or restart all ZoneMinder Servers<br/>
        </p>
        <p>
          <input type="radio" name="when" value="now" id="whennow"/><label for="whennow">Now</label>
          <input type="radio" name="when" value="1min" id="when1min" checked="checked"/><label for="when1min">1 Minute</label>
        </p>
      </div>
      <div class="modal-footer">
        <?php echo $cancel_btn ?>
        <button type="submit" id="restartBtn" class="btn btn-primary" name="action" value="restart"><?php echo translate('Restart') ?></button>
        <button type="submit" id="shutdownBtn" class="btn btn-primary" name="action" value="shutdown"><?php echo translate('Shutdown') ?></button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo translate('Close') ?></button>
      </div>
    </form>
    </div>
  </div>
</div>
