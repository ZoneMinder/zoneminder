var currGroup = "<?= isset($_REQUEST['group'])?validJsStr($_REQUEST['group']):'' ?>";
var nextMid = "<?= isset($nextMid)?$nextMid:'' ?>";
var mode = "<?= $mode ?>";

var cycleRefreshTimeout = <?= 1000*ZM_WEB_REFRESH_CYCLE ?>;
