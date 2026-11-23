<?php
// ajax/tags.php getavailabletags leaves out tags used only on monitors the user
// cannot see (the same visibility filter gettags applies), while still offering
// unused tags and tags the user has seen on a visible monitor.
//
// Run as: php tests/php/test_ajax_tags_available.php [repo root]

class Done extends Exception {}
$GLOBALS['usage'] = array(
  array('TagId'=>1, 'MonitorId'=>1),
  array('TagId'=>1, 'MonitorId'=>2),
  array('TagId'=>3, 'MonitorId'=>2),
);
$GLOBALS['tags'] = array(
  array('Id'=>1, 'Name'=>'car'),
  array('Id'=>2, 'Name'=>'unused'),
  array('Id'=>3, 'Name'=>'secret'),
);
function canView($a) { return true; }
function visibleMonitor($m) { return $m == 1; }
function ajaxError($m) { $GLOBALS['out'] = array('error'=>$m); throw new Done(); }
function ajaxResponse($r=false) { $GLOBALS['out'] = array('ok'=>$r); throw new Done(); }
function dbFetchAll($sql, $col=null, $params=array()) {
  if (strpos($sql, 'FROM Events_Tags AS ET INNER JOIN Events') !== false) return $GLOBALS['usage'];
  if (strpos($sql, 'FROM Tags') !== false) return $GLOBALS['tags'];
  return array();
}
function dbFetchOne($sql, $col=null, $params=array()) { return false; }
$user = new class { function Id() { return 1; } };

$root = realpath(isset($argv[1]) ? $argv[1] : __DIR__.'/../..');
$tmp = sys_get_temp_dir().'/zm_tags_avail_'.getmypid();
@mkdir($tmp);
file_put_contents($tmp.'/database.php', '');
set_include_path($tmp.PATH_SEPARATOR.$root.'/web');

function run($request) {
  global $user;
  $GLOBALS['out'] = null;
  $_REQUEST = $request;
  try {
    include $GLOBALS['root'].'/web/ajax/tags.php';
  } catch (Done $e) {
  }
  return $GLOBALS['out'];
}

$GLOBALS['root'] = $root;
$failures = 0;
function check($n, $got, $want) {
  global $failures;
  if ($got === $want) { echo "ok - $n\n"; } else { $failures++; echo "FAIL - $n (got ".var_export($got, true).")\n"; }
}

$out = run(array('action'=>'getavailabletags'));
$rows = isset($out['ok']['response']) ? $out['ok']['response'] : array();
check('a tag used only on hidden monitors is not offered', array_column($rows, 'Name'), array('car', 'unused'));

unlink($tmp.'/database.php');
rmdir($tmp);
exit($failures ? 1 : 0);
