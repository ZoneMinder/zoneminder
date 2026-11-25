<?php
// views/monitor.php reads the X10 form values from $_REQUEST['newX10Monitor'] whenever the
// request carries newMonitor. A link such as the one the Add Monitors page builds
// (?view=monitor&newMonitor[Path]=...) has newMonitor but no newX10Monitor, so with X10
// enabled the view raised "Undefined array key" and the X10 tab then read offsets of null.
// The stored values are used for whatever the request does not carry.
//
// Run as: php tests/php/test_monitor_view_x10_get.php [repo root]

$root = $argv[1] ?? dirname(__DIR__, 2);
$src = file_get_contents($root.'/web/skins/classic/views/monitor.php');
if (!preg_match('/^(if \(isset\(\$_REQUEST\[\'newMonitor\'\]\)\) \{\n.*?^\})\n/ms', $src, $m)) {
  echo "FAIL - could not find the newMonitor block in monitor.php\n";
  exit(1);
}
$block = $m[1];

define('ZM_OPT_X10', true);
class StubMonitor { function set($v) {} }
function monitorFillUncheckedFields($v) { return $v; }
set_error_handler(function ($no, $str) { throw new ErrorException($str); });

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) {
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "FAIL - $name (got ".var_export($got, true).")\n";
  }
}

function run($request, $method) {
  global $block;
  $_REQUEST = $request;
  $_SERVER['REQUEST_METHOD'] = $method;
  $monitor = new StubMonitor();
  $x10Monitor = array('Activation' => 'A1', 'AlarmInput' => 'B1', 'AlarmOutput' => 'C1');
  try {
    eval($block);
  } catch (Throwable $e) {
    return 'threw: '.$e->getMessage();
  }
  return $newX10Monitor;
}

$stored = array('Activation' => 'A1', 'AlarmInput' => 'B1', 'AlarmOutput' => 'C1');

check('a link with newMonitor and no newX10Monitor uses the stored X10 values',
  run(array('newMonitor' => array('Path' => 'rtsp://cam')), 'GET'), $stored);
check('a posted newX10Monitor is used',
  run(array('newMonitor' => array('Name' => 'x'), 'newX10Monitor' => array('Activation' => 'A2', 'AlarmInput' => 'B2', 'AlarmOutput' => 'C2')), 'POST'),
  array('Activation' => 'A2', 'AlarmInput' => 'B2', 'AlarmOutput' => 'C2'));
check('a posted newX10Monitor missing a key falls back to the stored value for it',
  run(array('newMonitor' => array('Name' => 'x'), 'newX10Monitor' => array('Activation' => 'A2')), 'POST'),
  array('Activation' => 'A2', 'AlarmInput' => 'B1', 'AlarmOutput' => 'C1'));
check('a newX10Monitor that is not an array is ignored',
  run(array('newMonitor' => array('Name' => 'x'), 'newX10Monitor' => 'junk'), 'POST'), $stored);
check('no newMonitor uses the stored X10 values', run(array(), 'GET'), $stored);
exit($failures ? 1 : 0);
