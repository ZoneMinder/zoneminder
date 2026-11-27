<?php
// Storage::MonitorCount() and EventCount() run on every Options > Storage page
// and before a storage area is deleted. MonitorCount() queried
// Monitors.SecondaryStorageId, a column that only Events has, so every call
// logged "Unknown column 'SecondaryStorageId'" and the count came back empty.
//
// Checks every column the two count queries filter on against the table
// definitions in db/zm_create.sql.in.
//
// php tests/php/test_storage_count_columns.php

$passes = 0;
$failures = 0;
function check($name, $ok, $detail = '') {
  global $passes, $failures;
  if ($ok) {
    $passes++;
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "FAIL - $name $detail\n";
  }
}

$schema = file_get_contents(__DIR__.'/../../db/zm_create.sql.in');
function table_columns($schema, $table) {
  if (!preg_match('/CREATE TABLE `'.$table.'` \((.*?)\n\) ENGINE/s', $schema, $m)) return array();
  preg_match_all('/^\s*`(\w+)`/m', $m[1], $cols);
  return $cols[1];
}

$src = file_get_contents(__DIR__.'/../../web/includes/Storage.php');
preg_match_all("/SELECT COUNT\(\*\) AS (\w+) FROM (\w+) WHERE ([^']+)'/", $src, $queries, PREG_SET_ORDER);
check('found the count queries', count($queries) >= 2);

foreach ($queries as $q) {
  list(, $alias, $table, $where) = $q;
  $columns = table_columns($schema, $table);
  check("$table table found in the schema", count($columns) > 0);
  preg_match_all('/(\w+)\s*=\s*\?/', $where, $used);
  foreach ($used[1] as $col) {
    check("$alias filters on $table.$col, which exists", in_array($col, $columns), "($table has no $col)");
  }
}

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
