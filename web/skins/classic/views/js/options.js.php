<?php global $restartWarning; ?>
var restartWarning = <?php echo empty($restartWarning)?'false':'true' ?>;
if ( restartWarning ) {
  alert( "<?php echo translate('OptionRestartWarning') ?>" );
}

var canEditSystem = <?php echo canEdit('System') ? 'true' : 'false' ?>;
