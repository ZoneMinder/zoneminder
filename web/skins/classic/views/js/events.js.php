//var openFilterWindow = <?= $_REQUEST['filter']?'true':'false' ?>;
var openFilterWindow = false;

var archivedEvents = <?= !empty($archived)?'true':'false' ?>;
var unarchivedEvents = <?= !empty($unarchived)?'true':'false' ?>;

var filterQuery = '<?= isset($filterQuery)?addslashes($filterQuery):'' ?>';
var sortQuery = '<?= isset($sortQuery)?addslashes($sortQuery):'' ?>';

var maxWidth = <?= $maxWidth?$maxWidth:0 ?>;
var maxHeight = <?= $maxHeight?$maxHeight:0 ?>;

var confirmDeleteEventsString = "<?= addslashes($SLANG['ConfirmDeleteEvents']) ?>";
