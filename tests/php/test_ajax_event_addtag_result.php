<?php
// ajax/event.php addtag ran its INSERT IGNORE and the Tags LastAssignedDate UPDATE
// with dbFetchAll(), which swallows a failed query and returns an empty array, so a
// failed add still answered success. Both statements must go through dbQuery() and
// ajaxError() on failure, like the neighbouring rename/archive/removetag cases.
//
// Run as: php tests/php/test_ajax_event_addtag_result.php [repo-root]

class Done extends Exception {}
$GLOBALS['queries'] = array();
$GLOBALS['failSql'] = null;
function canView($a) { return true; }
function canEdit($a) { return true; }
function visibleMonitor($m) { return true; }
function validCardinal($v) { return is_scalar($v) ? preg_replace('/\D/', '', $v) : ''; }
function ajaxError($m) { $GLOBALS['out'] = array('error'=>$m); throw new Done(); }
function ajaxResponse($r=false) { $GLOBALS['out'] = array('ok'=>$r); throw new Done(); }
function dbFetchAll($sql, $col=null, $params=array()) { return array(); }
function dbFetchOne($sql, $col=null, $params=array()) {
  if (strpos($sql, 'FROM Tags WHERE Id = ?') !== false) return array('Id'=>2);
  return false;
}
function dbQuery($sql, $params=array()) {
  $GLOBALS['queries'][] = array($sql, $params);
  if ($GLOBALS['failSql'] !== null && strpos($sql, $GLOBALS['failSql']) !== false) return null;
  return true;
}
$user = new class { function Id() { return 1; } };

eval('namespace ZM; class Event {
  private $id;
  function __construct($id) { $this->id = $id; }
  function Id() { return $this->id; }
  function MonitorId() { return 1; }
}');

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$stubdir = sys_get_temp_dir().'/zm_addtag_stubs_'.getmypid();
@mkdir($stubdir.'/includes', 0700, true);
file_put_contents($stubdir.'/includes/Event.php', "<?php\n");
file_put_contents($stubdir.'/includes/TagOrder.php', "<?php\nnamespace ZM;\nclass TagOrder { static function recordUsage(\$u, \$t) {} }\n");
set_include_path($stubdir.PATH_SEPARATOR.get_include_path());

function run($request) {
  global $user;
  $GLOBALS['out'] = null;
  $GLOBALS['queries'] = array();
  $_REQUEST = $request;
  try {
    include $GLOBALS['root'].'/web/ajax/event.php';
  } catch (Done $e) {
  }
  return $GLOBALS['out'];
}

$failures = 0;
function check($n, $got, $want) {
  global $failures;
  if ($got === $want) { echo "ok - $n\n"; } else { $failures++; echo "FAIL - $n (got ".var_export($got, true).")\n"; }
}

$GLOBALS['root'] = $root;
$request = array('action'=>'addtag', 'id'=>'7', 'tid'=>'2');

$GLOBALS['failSql'] = null;
$out = run($request);
check('addtag reports success', isset($out['ok']), true);
check('addtag keeps the response shape', isset($out['ok']) ? $out['ok']['response'] : null, array());
check('addtag runs the insert then the date update', array_column($GLOBALS['queries'], 0), array(
  'INSERT IGNORE INTO Events_Tags (TagId, EventId, AssignedBy) VALUES (?, ?, ?)',
  'UPDATE Tags SET LastAssignedDate = NOW() WHERE Id = ?',
));

$GLOBALS['failSql'] = 'INSERT IGNORE INTO Events_Tags';
$out = run($request);
check('a failed insert is an error', isset($out['error']) ? $out['error'] : null, 'Failed to add tag');
check('a failed insert does not run the update', count(array_filter($GLOBALS['queries'], function($q) {
  return strpos($q[0], 'UPDATE Tags') !== false;
})), 0);

$GLOBALS['failSql'] = 'UPDATE Tags';
$out = run($request);
check('a failed date update is an error', isset($out['error']) ? $out['error'] : null, 'Failed to add tag');

@unlink($stubdir.'/includes/TagOrder.php');
@unlink($stubdir.'/includes/Event.php');
@rmdir($stubdir.'/includes'); @rmdir($stubdir);
exit($failures ? 1 : 0);
