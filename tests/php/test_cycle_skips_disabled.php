<?php
// The cycle view must skip monitors that are not capturing. The legacy Function
// column is no longer maintained (it keeps its default of 'Monitor'), so the
// Capturing column is what says whether a monitor is disabled.
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

$path = __DIR__.'/../../web/skins/classic/views/cycle.php';
$src = file_get_contents($path);
if (!preg_match('/^function cycleSkipsMonitor\(.*?\n\}/ms', $src, $m)) {
  echo "FAIL could not find cycleSkipsMonitor() in $path\n";
  exit(1);
}
eval($m[0]);

check('disabled monitor is skipped', cycleSkipsMonitor(array('Function'=>'Monitor', 'Capturing'=>'None')), true);
check('always capturing is kept', cycleSkipsMonitor(array('Function'=>'Monitor', 'Capturing'=>'Always')), false);
check('on demand is kept', cycleSkipsMonitor(array('Function'=>'Monitor', 'Capturing'=>'Ondemand')), false);

echo "$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
