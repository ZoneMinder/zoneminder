<?php
// ajax/event.php ignored the result of the UPDATE/DELETE behind eventdetail, archive,
// unarchive and removetag, so a failed query still answered Ok and the page refreshed as if
// it had worked. eventdetail also read $_REQUEST['newEvent'] without checking it was there,
// so a request without it set Cause and Notes to NULL. They now answer an error instead.
//
// Run as: php tests/php/test_ajax_event_save_results.php [root]   (root defaults to the repo)

namespace ZM {
  class Event {
    private $id;
    function __construct($id) { $this->id = $id; }
    function Id() { return $this->id; }
    function MonitorId() { return 1; }
    function Archived() { return false; }
  }
}

namespace {

class Done extends Exception {}
function canView($a) { return false; }
function canEdit($a) { return true; }
function validCardinal($v) { return is_scalar($v) ? preg_replace('/\D/', '', $v) : ''; }
function visibleMonitor($id) { return true; }
function ajaxError($m) { $GLOBALS['out'] = 'error: '.$m; throw new Done(); }
function ajaxResponse($r = array()) { $GLOBALS['out'] = 'ok'; throw new Done(); }
function dbQuery($sql, $values = array()) {
  $GLOBALS['queries'][] = array($sql, $values);
  return $GLOBALS['dbResult'];
}
function dbNumRows($sql, $values = array()) { return 1; }

// ajax/event.php does require_once('includes/Event.php'); give it an empty one so the stub
// class above is used.
$stubdir = sys_get_temp_dir().'/zm_ajax_event_results_'.getmypid();
@mkdir($stubdir.'/includes', 0700, true);
file_put_contents($stubdir.'/includes/Event.php', "<?php\n");
set_include_path($stubdir.PATH_SEPARATOR.get_include_path());

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$file = $root.'/web/ajax/event.php';

function run($request, $dbResult) {
  global $file, $user;
  $user = null;
  $GLOBALS['out'] = 'nothing';
  $GLOBALS['queries'] = array();
  $GLOBALS['dbResult'] = $dbResult;
  $_REQUEST = $request;
  try {
    include $file;
  } catch (Done $e) {
  } catch (Throwable $e) {
    return 'threw: '.$e->getMessage();
  }
  return $GLOBALS['out'];
}

$failures = 0;
function check($n, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $n\n";
  else { $failures++; echo "FAIL - $n (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

$detail = array('action' => 'eventdetail', 'id' => '5', 'newEvent' => array('Cause' => 'Motion', 'Notes' => 'x'));
check('eventdetail answers Ok when the UPDATE works', run($detail, true), 'ok');
check('eventdetail answers an error when the UPDATE fails', run($detail, false), 'error: Failed to save event detail');

$warnings = array();
set_error_handler(function($no, $str) use (&$warnings) { $warnings[] = $str; return true; });
$out = run(array('action' => 'eventdetail', 'id' => '5'), true);
restore_error_handler();
check('eventdetail without newEvent is refused', $out, 'error: No event detail supplied');
check('eventdetail without newEvent runs no UPDATE', count($GLOBALS['queries']), 0);
check('eventdetail without newEvent raises no PHP warnings', $warnings, array());

foreach (array('archive', 'unarchive') as $action) {
  check("$action answers Ok when the UPDATE works", run(array('action' => $action, 'id' => '5'), true), 'ok');
  check("$action answers an error when the UPDATE fails", run(array('action' => $action, 'id' => '5'), false), "error: Failed to $action event");
}

check('removetag answers Ok when the DELETE works', run(array('action' => 'removetag', 'id' => '5', 'tid' => '3'), true), 'ok');
check('removetag answers an error when the DELETE fails', run(array('action' => 'removetag', 'id' => '5', 'tid' => '3'), false), 'error: Failed to remove tag');

@unlink($stubdir.'/includes/Event.php');
@rmdir($stubdir.'/includes');
@rmdir($stubdir);
echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
}
