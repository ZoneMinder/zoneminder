<?php
// ajax/event.php export passed $_REQUEST['eids'] straight to array_map(), so eids=5
// (a string, not eids[]=5) was a TypeError on PHP 8.
//
// Run as: php tests/php/test_ajax_event_eids_scalar.php

class Done extends Exception {}
function canView($a) { return true; }
function canEdit($a) { return false; }
function ajaxError($m) { $GLOBALS['out'] = "error: $m"; throw new Done(); }
function ajaxResponse($r) { $GLOBALS['out'] = 'ok'; throw new Done(); }
function zm_session_start() {}
function validCardinal($v) { return is_scalar($v) ? preg_replace('/\D/', '', $v) : ''; }
function exportEvents($ids) { $GLOBALS['ids'] = $ids; return 'file.zip'; }

$stubdir = sys_get_temp_dir().'/zm_eids_stubs_'.getmypid();
@mkdir($stubdir.'/skin/includes', 0700, true);
file_put_contents($stubdir.'/skin/includes/export_functions.php', "<?php\n");
define('ZM_SKIN_PATH', $stubdir.'/skin');
$_SESSION = array();

function run($eids) {
  $GLOBALS['ids'] = null;
  $_REQUEST = array('action' => 'export', 'eids' => $eids, 'exportFormat' => 'zip');
  try {
    include __DIR__.'/../../web/ajax/event.php';
  } catch (Done $e) {
  } catch (Throwable $e) {
    return 'threw: '.$e->getMessage();
  }
  return $GLOBALS['ids'];
}

$failures = 0;
function check($n, $got, $want) {
  global $failures;
  if ($got === $want) { echo "ok - $n\n"; } else { $failures++; echo "FAIL - $n (got ".var_export($got, true).")\n"; }
}
check('eids=5 is treated as one id', run('5'), array('5'));
check('eids[]=5&eids[]=7 still works', run(array('5', '7')), array('5', '7'));

@unlink($stubdir.'/skin/includes/export_functions.php');
@rmdir($stubdir.'/skin/includes'); @rmdir($stubdir.'/skin'); @rmdir($stubdir);
exit($failures ? 1 : 0);
