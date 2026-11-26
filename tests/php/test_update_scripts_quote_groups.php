<?php

/**
 * GROUPS is a reserved word from MySQL 8.0.2, so an update script that names the
 * Groups table without backticks stops with a syntax error there.
 * Usage: php test_update_scripts_quote_groups.php [repo_root]
 */

$root = $argv[1] ?? __DIR__ . '/../..';
$files = array_merge(glob($root . '/db/zm_update-*.sql'), glob($root . '/db/zm_update-*.sql.in'));
sort($files);

$checked = 0;
$failures = 0;
foreach ($files as $file) {
  if (!preg_match('/zm_update-([\d.]+)\.sql/', basename($file), $m) or version_compare($m[1], '1.26.0', '<')) {
    continue;
  }
  $checked++;
  $sql = file_get_contents($file);
  // Comments, backticked names and quoted strings are not table references.
  $sql = preg_replace('~/\*.*?\*/~s', '', $sql);
  $sql = preg_replace('/^\s*--.*$/m', '', $sql);
  if (preg_match_all('/\b(?:FROM|JOIN|INTO|UPDATE|TABLE)\s+Groups\b/i', $sql, $found)) {
    echo 'FAIL ' . basename($file) . ': unquoted Groups table in ' . count($found[0]) . " statement(s)\n";
    $failures++;
  }
}

if ($checked == 0) {
  echo "FAIL: no update scripts found under $root/db\n";
  exit(1);
}
if ($failures > 0) {
  echo "SUMMARY: $failures of $checked update scripts name Groups without backticks\n";
  exit(1);
}
echo "SUMMARY: $checked update scripts quote the Groups table\n";
exit(0);
