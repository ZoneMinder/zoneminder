<?php
// The Reports list had a Description column, but the Reports table has no Description and
// web/ajax/reports.php never returns one, so the column was always empty. Every data
// column on the page must be one the query returns.
//
// Run as: php tests/php/test_reports_columns_match_query.php [root]   (root defaults to the repo)

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$view = file_get_contents($root.'/web/skins/classic/views/reports.php');
$ajax = file_get_contents($root.'/web/ajax/reports.php');

$failures = 0;
function check($n, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $n\n";
  else { $failures++; echo "FAIL - $n (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

preg_match_all('/data-field="(\w+)"/', $view, $m);
$fields = array_diff($m[1], array('toggleCheck'));
preg_match('/\$columns\s*=\s*array\(([^)]*)\)/', $ajax, $c);
preg_match_all("/'(\w+)'/", $c[1] ?? '', $cm);
$columns = $cm[1];

check('found the table columns', count($fields) > 0, true);
check('found the query columns', count($columns) > 0, true);
foreach ($fields as $field) {
  check("column $field is returned by ajax/reports.php", in_array($field, $columns, true), true);
}

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
