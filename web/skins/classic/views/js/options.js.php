<?php
global $restartWarning;
global $tab;
?>
  const tab = '<?php echo $tab ?>';
var restartWarning = <?php echo empty($restartWarning)?'false':'true' ?>;
if ( restartWarning ) {
  alert( "<?php echo translate('OptionRestartWarning') ?>" );
}

