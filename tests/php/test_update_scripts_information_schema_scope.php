<?php

/**
 * An update script that reads INFORMATION_SCHEMA without limiting the query to the
 * current database also sees same-named tables in every other database on the
 * server, so it can skip a change or a foreign key the ZoneMinder database needs.
 * Usage: php test_update_scripts_information_schema_scope.php [repo_root]
 */

$root = $argv[1] ?? __DIR__ . '/../..';
$files = array_merge(glob($root . '/db/zm_update-*.sql'), glob($root . '/db/zm_update-*.sql.in'));
sort($files);

$checked = 0;
$failures = 0;
foreach ($files as $file) {
  $checked++;
  $sql = file_get_contents($file);
  $bad = 0;
  if (preg_match_all('/[^;]*information_schema\.\w+[^;]*/i', $sql, $statements)) {
    foreach ($statements[0] as $statement) {
      if (!preg_match('/(table|constraint|routine|trigger|schema)_schema/i', $statement)) {
        $bad++;
      }
    }
  }
  if ($bad > 0) {
    echo 'FAIL ' . basename($file) . ": $bad INFORMATION_SCHEMA quer" . ($bad == 1 ? 'y' : 'ies') . " without a schema condition\n";
    $failures++;
  }
}

if ($checked == 0) {
  echo "FAIL: no update scripts found under $root/db\n";
  exit(1);
}
if ($failures > 0) {
  echo "SUMMARY: $failures of $checked update scripts read INFORMATION_SCHEMA across all databases\n";
  exit(1);
}
echo "SUMMARY: all $checked update scripts limit INFORMATION_SCHEMA queries to the current database\n";
exit(0);
