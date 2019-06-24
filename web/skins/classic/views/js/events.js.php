//var openFilterWindow = <?php echo $_REQUEST['filter']?'true':'false' ?>;
var openFilterWindow = false;

var archivedEvents = <?php echo !empty($archived)?'true':'false' ?>;
var unarchivedEvents = <?php echo !empty($unarchived)?'true':'false' ?>;

var filterQuery = '<?php echo isset($filterQuery)?validJsStr(htmlspecialchars_decode($filterQuery)):'' ?>';
var sortQuery = '<?php echo isset($sortQuery)?validJsStr(htmlspecialchars_decode($sortQuery)):'' ?>';

var confirmDeleteEventsString = "<?php echo addslashes(translate('ConfirmDeleteEvents')) ?>";
