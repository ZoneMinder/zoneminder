<?php
// web/ajax/events.php resolves a sort column to SQL. The event view's filter
// sort field and ZM_WEB_EVENT_SORT_FIELD use 'MonitorName', which is a display
// name for the Monitor column (M.Name). If the resolver rejects it the sort is
// silently dropped.
//
// Run: php tests/php/test_events_sort_monitorname.php [root]
$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$src = file_get_contents($root.'/web/ajax/events.php');
if ($src === false
  || !preg_match('/^\s*\$columns = array\(.*?;$/m', $src, $c)
  || !preg_match('/^\s*\$col_alt = array\(.*?;$/m', $src, $a)
  || !preg_match('/^\s*\$whitelist = .*?;$/m', $src, $w)
  || !preg_match('/^\s*\$resolve = function.*?^\s*\};$/ms', $src, $r)) {
  echo "FAIL could not extract the sort resolver from events.php\n";
  exit(1);
}
eval($c[0].$a[0].$w[0].$r[0]);

$failures = 0;
function check($name, $got, $ok) {
  global $failures;
  echo ($ok ? 'PASS' : 'FAIL')." $name: ".var_export($got, true)."\n";
  if (!$ok) $failures++;
}

check('MonitorName resolves to M.Name', $resolve('MonitorName'), $resolve('MonitorName') === 'M.Name');
check('Monitor resolves to M.Name', $resolve('Monitor'), $resolve('Monitor') === 'M.Name');
check('unknown column rejected', $resolve('Nope'), $resolve('Nope') === null);
exit($failures ? 1 : 0);
