<?php
// ajax/stats.php returns the zone stats of a frame. It only checked the
// Events view permission, so a user limited to some monitors could read the
// stats of an event on a monitor they cannot view.
//
// Run as: php tests/php/test_ajax_stats_event_view.php

class Done extends Exception {}
$stubdir = sys_get_temp_dir().'/zm_stats_view_stubs_'.getmypid();
@mkdir($stubdir.'/includes', 0700, true);
file_put_contents($stubdir.'/includes/Event.php', "<?php\nnamespace ZM; class Event { private \$id; public function __construct(\$id) { \$this->id = \$id; } public function Id() { return \$this->id; } public function canView() { return \$this->id == 5; } }\n");
set_include_path($stubdir.PATH_SEPARATOR.get_include_path());
register_shutdown_function(function() use ($stubdir) { @unlink($stubdir.'/includes/Event.php'); @rmdir($stubdir.'/includes'); @rmdir($stubdir); });

function canView($a) { return true; }
function ajaxError($m) { $GLOBALS['out'] = "error: $m"; throw new Done(); }
function ajaxResponse($r) { $GLOBALS['out'] = $r; throw new Done(); }
function validHtmlStr($s) { return htmlspecialchars((string)$s); }
function validCardinal($v) { return is_scalar($v) ? preg_replace('/\D/', '', $v) : ''; }
function translate($s) { return $s; }
function getStatsTableHTML($eid, $fid, $row) { return 'stats for '.$eid; }

function run($eid) {
  $GLOBALS['out'] = null;
  $_REQUEST = array('eid' => $eid, 'fid' => '7');
  try {
    include (isset($GLOBALS['argv'][1]) ? rtrim($GLOBALS['argv'][1], '/') : __DIR__.'/../..').'/web/ajax/stats.php';
  } catch (Done $e) {
  }
  return $GLOBALS['out'];
}

$failures = 0;
function check($n, $got, $want) {
  global $failures;
  if ($got === $want) { echo "ok - $n\n"; } else { $failures++; echo "FAIL - $n (got ".var_export($got, true).")\n"; }
}
$out = run('5');
check('a viewable event returns its stats', is_array($out) ? $out['html'] : $out, 'stats for 5');
$out = run('6');
check('an event on a monitor the user cannot view is refused', is_array($out) ? 'returned data' : $out, 'error: Insufficient permissions');
$out = run('99');
check('an unknown event is refused', is_array($out) ? 'returned data' : $out, 'error: Insufficient permissions');
$out = run('5x');
check('the stats query gets the eid that was checked', is_array($out) ? $out['html'] : $out, 'stats for 5');
exit($failures ? 1 : 0);
