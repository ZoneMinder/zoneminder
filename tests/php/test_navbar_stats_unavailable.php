<?php

$failures = 0;
$passes = 0;

function check($name, $got, $want) {
  global $failures, $passes;
  if ($got === $want) {
    $passes++;
    echo "  ok $name\n";
  } else {
    $failures++;
    echo "  FAIL $name\n";
    echo '    got:  '.var_export($got, true)."\n";
    echo '    want: '.var_export($want, true)."\n";
  }
}

class FakeServer {
  public $CpuLoad = -1;
  public $CpuUsagePercent = -1;
  public function ReadStats() {}
}

function canView($p) { return true; }
function translate($s) { return $s; }

$path = __DIR__.'/../../web/skins/classic/includes/functions.php';
$src = file_get_contents($path);
foreach (array('getCpuUsageHTML', 'getSysLoadHTML') as $fn) {
  if (!preg_match('/^function '.$fn.'\(.*?\n\}/ms', $src, $m)) {
    echo "Could not find $fn() in $path\n";
    exit(1);
  }
  eval($m[0]);
}

$thisServer = new FakeServer();
$load = function() { return trim(preg_replace('/^.*?(Load:.*)$/s', '$1', strip_tags(getSysLoadHTML()))); };
$cpu = function() { return trim(preg_replace('/^.*?(Cpu:.*)$/s', '$1', strip_tags(getCpuUsageHTML()))); };

check('load before any stats', $load(), 'Load: n/a');
check('cpu before any stats', $cpu(), 'Cpu: n/a');

$thisServer->CpuLoad = 0.5;
$thisServer->CpuUsagePercent = 12.34;
check('load with stats', $load(), 'Load: 0.50');
check('cpu with stats', $cpu(), 'Cpu: 12.3%');

$thisServer->CpuLoad = 0;
$thisServer->CpuUsagePercent = 0;
check('an idle load of zero is a real value', $load(), 'Load: 0.00');
check('an idle cpu of zero is a real value', $cpu(), 'Cpu: 0.0%');

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
