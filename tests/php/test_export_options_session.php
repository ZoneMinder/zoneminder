<?php
// ajax/event.php action=export only wrote an option into $_SESSION['export'] when it was
// ticked, so an option the user unticked came back ticked on the next visit to the Export
// view (which restores the options from the session).
//
// Run as: php tests/php/test_export_options_session.php

function canView($a) { return true; }
function canEdit($a) { return false; }
class Done extends Exception {}
function ajaxError($m) { $GLOBALS['out'] = "error: $m"; throw new Done(); }
function ajaxResponse($r) { $GLOBALS['out'] = 'ok'; throw new Done(); }
function zm_session_start() {}
function validCardinal($v) { return (int)$v; }
function exportEvents() { return 'file.zip'; }

$stubdir = sys_get_temp_dir().'/zm_export_session_stubs_'.getmypid();
@mkdir($stubdir.'/includes', 0700, true);
@mkdir($stubdir.'/skin/includes', 0700, true);
file_put_contents($stubdir.'/skin/includes/export_functions.php', "<?php\n");
define('ZM_SKIN_PATH', $stubdir.'/skin');

$_SESSION = array('export' => array('detail' => 1, 'frames' => 1, 'images' => 1, 'video' => 1, 'misc' => 1, 'compress' => 1));
$_REQUEST = array('action' => 'export', 'eids' => array('1'), 'exportImages' => '1', 'exportFormat' => 'zip');
try {
  include __DIR__.'/../../web/ajax/event.php';
} catch (Done $e) {
}

@unlink($stubdir.'/skin/includes/export_functions.php');
@rmdir($stubdir.'/skin/includes'); @rmdir($stubdir.'/skin'); @rmdir($stubdir.'/includes'); @rmdir($stubdir);

$failures = 0;
function check($n, $got, $want) {
  global $failures;
  if ($got == $want) { echo "ok - $n\n"; } else { $failures++; echo "FAIL - $n (got ".var_export($got, true).")\n"; }
}
check('request succeeded', $out, 'ok');
check('ticked option stays on', !empty($_SESSION['export']['images']), true);
foreach (array('detail', 'frames', 'video', 'misc', 'compress') as $k)
  check("unticked $k is cleared", !empty($_SESSION['export'][$k]), false);
exit($failures ? 1 : 0);
