<?php
  global $filterQuery;
  global $sortQuery;
?>

var filterQuery = '<?php echo isset($filterQuery)?validJsStr(htmlspecialchars_decode($filterQuery)):'' ?>';
var sortQuery = '<?php echo isset($sortQuery)?validJsStr(htmlspecialchars_decode($sortQuery)):'' ?>';

const archiveString = "<?php echo translate('Archive') ?>";
const unarchiveString = "<?php echo translate('Unarchive') ?>";
var WEB_LIST_THUMBS = <?php echo ZM_WEB_LIST_THUMBS?'true':'false' ?>;
