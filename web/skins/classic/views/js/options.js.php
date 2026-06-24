<?php
global $restartWarning;
global $tab;
?>
  const tab = '<?php echo $tab ?>';
const menuItemStrings = {
  menuKey: <?php echo json_encode(translate('Name')) ?>,
  label: <?php echo json_encode(translate('Custom Label')) ?>,
  remove: <?php echo json_encode(translate('Remove')) ?>,
  confirmDelete: <?php echo json_encode(translate('Delete this menu entry?')) ?>,
};
var restartWarning = <?php echo empty($restartWarning)?'false':'true' ?>;
if ( restartWarning ) {
  alert( "<?php echo translate('OptionRestartWarning') ?>" );
}

