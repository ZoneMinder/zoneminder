<?php
// web/ajax/events.php resolves a sort column to SQL. 'Storage' is a display
// column with no Events.Storage column, so sorting by it must not produce
// E.Storage ("Unknown column" -> Database query failed).
//
// Run: php tests/php/test_events_sort_storage.php
$src = file_get_contents(__DIR__.'/../../web/ajax/events.php');
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

check('Id resolves', $resolve('Id'), $resolve('Id') === 'E.Id');
check('Monitor resolves', $resolve('Monitor'), $resolve('Monitor') === 'M.Name');
check('unknown column rejected', $resolve('Nope'), $resolve('Nope') === null);
$s = $resolve('Storage');
check('Storage is not E.Storage', $s, $s !== 'E.Storage');
check('Storage sorts on a real column or is rejected', $s, $s === null || $s === 'E.StorageId');
exit($failures ? 1 : 0);
