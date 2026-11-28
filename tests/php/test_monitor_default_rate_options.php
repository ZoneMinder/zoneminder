<?php
// The Default Rate list on the Viewing tab of views/monitor.php offered every rate of the event
// view, the negative ones included. Monitors.DefaultRate is smallint unsigned, so choosing -1x
// made the save fail with "Out of range value for column 'DefaultRate'" (MariaDB and MySQL in
// their default strict mode), and in a non strict mode stored 0. Only rates the column can hold
// are offered.
//
// Run as: php tests/php/test_monitor_default_rate_options.php [repo root]

$root = $argv[1] ?? dirname(__DIR__, 2);
$monitor_src = file_get_contents($root.'/web/skins/classic/views/monitor.php');
$config_src = file_get_contents($root.'/web/skins/classic/includes/config.php');

if (!preg_match('/echo (htmlSelect\(\'newMonitor\[DefaultRate\]\'.*?\));\s*\?>/s', $monitor_src, $m)) {
  echo "FAIL - could not find the Default Rate select in monitor.php\n";
  exit(1);
}
$select = $m[1];
if (!preg_match('/^(\$rates = array\(.*?^\);)/ms', $config_src, $m)) {
  echo "FAIL - could not find \$rates in the skin config\n";
  exit(1);
}
$rates_def = $m[1];

function translate($s) { return $s; }
function htmlSelect($name, $contents, $values) { return array_keys($contents); }
class StubMonitor {
  function DefaultRate() { return 100; }
}

$monitor = new StubMonitor();
eval($rates_def);
$offered = eval('return '.$select.';');

$failures = 0;
function check($name, $ok) {
  global $failures;
  if ($ok) {
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "FAIL - $name\n";
  }
}

check('no negative rate is offered', count(array_filter($offered, function($r) { return $r < 0; })) === 0);
check('stop (0) and the forward rates are kept', $offered === array(0, 25, 50, 100, 200, 500, 1000, 1600));
check('the current default of 1x is still listed', in_array(100, $offered));
exit($failures ? 1 : 0);
