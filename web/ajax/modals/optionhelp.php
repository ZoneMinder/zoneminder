<?php

// Returns the modal html representing the selected Option Help item
if ( empty($_REQUEST['ohndx']) ) {
  ajaxError('Option Help Index Not Provided');
  return;
}

global $OLANG;
$result = '';
$optionHelpIndex = $_REQUEST['ohndx'];
$ZMoptionHelpIndex = 'ZM_'.$optionHelpIndex;
 
if ( !empty($OLANG[$optionHelpIndex]) ) {
  $optionHelpText = $OLANG[$optionHelpIndex]['Help'];
} else {
  $optionHelpText = dbFetchOne('SELECT Help FROM Config WHERE Name=?', 'Help', array($optionHelpIndex));
}
$optionHelpText = validHtmlStr($optionHelpText);
$optionHelpText = preg_replace('/~~/', '<br/>', $optionHelpText );
$optionHelpText = preg_replace('/\[(.+)\]\((.+)\)/', '<a href="$2" target="_blank">$1</a>', $optionHelpText);

?>
<div id="optionhelp" class="modal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo translate('OptionHelp') ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <h3><?php echo validHtmlStr($optionHelpIndex) ?></h3>
        <p class="textblock"><?php echo $optionHelpText ?></p>
      </div>
      <div class="modal-footer">
        <button type="button" id="ohCloseBtn" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

