<?php
  global $filterQuery;
  global $sortQuery;
?>

var filterQuery = '<?php echo isset($filterQuery)?validJsStr(htmlspecialchars_decode($filterQuery)):'' ?>';
var sortQuery = '<?php echo isset($sortQuery)?validJsStr(htmlspecialchars_decode($sortQuery)):'' ?>';

const confirmDeleteEventsString = "<?php echo addslashes(translate('ConfirmDeleteEvents')) ?>";
const archivedString = "<?php echo translate('Archived') ?>";
const unarchivedString = "<?php echo translate('Unarchived') ?>";
const archiveString = "<?php echo translate('Archive') ?>";
const unarchiveString = "<?php echo translate('Unarchive') ?>";
const emailedString = "<?php echo translate('Emailed') ?>";
const yesString = "<?php echo translate('Yes') ?>";
const noString = "<?php echo translate('No') ?>";
var WEB_LIST_THUMBS = <?php echo ZM_WEB_LIST_THUMBS?'true':'false' ?>;
