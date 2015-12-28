//var openFilterWindow = <?php echo $_REQUEST['filter']?'true':'false' ?>;
var openFilterWindow = false;

var archivedEvents = <?php echo !empty($archived)?'true':'false' ?>;
var unarchivedEvents = <?php echo !empty($unarchived)?'true':'false' ?>;

var filterQuery = '<?php echo isset($filterQuery)?validJsStr($filterQuery):'' ?>';
var sortQuery = '<?php echo isset($sortQuery)?validJsStr($sortQuery):'' ?>';

var maxWidth = <?php echo $maxWidth?$maxWidth:0 ?>;
var maxHeight = <?php echo $maxHeight?$maxHeight:0 ?>;

var confirmDeleteEventsString = "<?php echo addslashes(translate('ConfirmDeleteEvents')) ?>";
