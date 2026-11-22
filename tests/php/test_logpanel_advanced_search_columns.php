<?php
// The log panel's Advanced Search offered a DateTime field, but DateTime is built from
// TimeKey after the rows are read; web/ajax/log.php can't search on it and answers a term
// on it with no rows at all. Every column the advanced search form offers (searchable,
// i.e. not data-searchable="false", and not the checkbox) must be one log.php can search.
//
// Run as: php tests/php/test_logpanel_advanced_search_columns.php [root]

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$panel = file_get_contents($root.'/web/skins/classic/includes/logpanel.php');
$ajax = file_get_contents($root.'/web/ajax/log.php');

$failures = 0;
function check($n, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $n\n";
  else { $failures++; echo "FAIL - $n (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

// Columns log.php matches on: the $columns list plus the special-cased Server.
preg_match('/function logColumns\(\)\s*\{\s*return array\(([^)]*)\)/', $ajax, $m);
preg_match_all("/'(\w+)'/", $m[1] ?? '', $cm);
$searchable = array_merge($cm[1], array('Server'));
check('found the columns log.php searches', count($cm[1]) > 0, true);

preg_match_all('/<th\b([^>]*)>/', $panel, $ths);
foreach ($ths[1] as $attrs) {
  if (!preg_match('/data-field="(\w+)"/', $attrs, $f)) continue;
  $field = $f[1];
  if (strpos($attrs, 'data-checkbox="true"') !== false) continue;
  if (strpos($attrs, 'data-searchable="false"') !== false) continue;
  if (strpos($attrs, 'data-visible="false"') !== false and $field == 'Id') continue;
  check("advanced search field $field is searchable by log.php", in_array($field, $searchable, true), true);
}

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
