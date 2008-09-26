//
// Import constants
//
var CMD_NONE = <?= CMD_NONE ?>;
var CMD_PAUSE = <?= CMD_PAUSE ?>;
var CMD_PLAY = <?= CMD_PLAY ?>;
var CMD_STOP = <?= CMD_STOP ?>;
var CMD_FASTFWD = <?= CMD_FASTFWD ?>;
var CMD_SLOWFWD = <?= CMD_SLOWFWD ?>;
var CMD_SLOWREV = <?= CMD_SLOWREV ?>;
var CMD_FASTREV = <?= CMD_FASTREV ?>;
var CMD_ZOOMIN = <?= CMD_ZOOMIN ?>;
var CMD_ZOOMOUT = <?= CMD_ZOOMOUT ?>;
var CMD_PAN = <?= CMD_PAN ?>;
var CMD_SCALE = <?= CMD_SCALE ?>;
var CMD_PREV = <?= CMD_PREV ?>;
var CMD_NEXT = <?= CMD_NEXT ?>;
var CMD_SEEK = <?= CMD_SEEK ?>;
var CMD_QUERY = <?= CMD_QUERY ?>;

var SCALE_BASE = <?= SCALE_BASE ?>;

//
// PHP variables to JS
//
var connKey = '<?= $connkey ?>';

var event = {
    Id: <?= $event['Id'] ?>,
    Width: <?= $event['Width'] ?>,
    Height: <?= $event['Height'] ?>,
    Length: <?= $event['Length'] ?>
};

var filterQuery = '<?= isset($filterQuery)?validJsStr($filterQuery):'' ?>';
var sortQuery = '<?= isset($sortQuery)?validJsStr($sortQuery):'' ?>';

var scale = <?= $scale ?>;
var canEditEvents = <?= canEdit( 'Events' )?'true':'false' ?>;
var streamTimeout = <?= 1000*ZM_WEB_REFRESH_STATUS ?>;

var canStreamNative = <?= canStreamNative()?'true':'false' ?>;

//
// Strings
//
var deleteString = "<?= $SLANG['Delete'] ?>";
var causeString = "<?= $SLANG['AttrCause'] ?>";
