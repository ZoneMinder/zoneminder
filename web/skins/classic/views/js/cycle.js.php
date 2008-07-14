var currGroup = "<?= isset($_REQUEST['group'])?$_REQUEST['group']:'' ?>";
var nextMid = "<?= isset($nextMid)?$nextMid:'' ?>";
var mode = "<?= $_REQUEST['mode'] ?>";

var cycleRefreshTimeout = <?= 1000*ZM_WEB_REFRESH_CYCLE ?>;
