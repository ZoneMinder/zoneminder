var restartWarning = <?php echo empty($restartWarning)?'false':'true' ?>;
if ( restartWarning ) {
  alert( "<?php echo translate('OptionRestartWarning') ?>" );
}
