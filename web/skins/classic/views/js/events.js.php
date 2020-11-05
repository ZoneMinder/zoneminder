<?php
  global $filterQuery;
  global $sortQuery;
?>

var filterQuery = '<?php echo isset($filterQuery)?validJsStr(htmlspecialchars_decode($filterQuery)):'' ?>';
var sortQuery = '<?php echo isset($sortQuery)?validJsStr(htmlspecialchars_decode($sortQuery)):'' ?>';

var confirmDeleteEventsString = "<?php echo addslashes(translate('ConfirmDeleteEvents')) ?>";
var archivedString = "<?php echo translate('Archived') ?>";
var emailedString = "<?php echo translate('Emailed') ?>";
var yesString = "<?php echo translate('Yes') ?>";
var noString = "<?php echo translate('No') ?>";
var WEB_LIST_THUMBS = <?php echo ZM_WEB_LIST_THUMBS?'true':'false' ?>;
