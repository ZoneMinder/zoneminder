<?php

/**
 * zm_update-1.33.13.sql widens Monitors.MaxFPS to decimal(5,3) so a rate such as
 * 0.017 frames a second is kept; a fresh install has to get the same column.
 * Usage: php test_create_monitors_maxfps_precision.php [repo_root]
 */

$root = $argv[1] ?? __DIR__ . '/../..';

$update = file_get_contents($root . '/db/zm_update-1.33.13.sql');
if (!preg_match('/ALTER TABLE `Monitors` MODIFY `MaxFPS`\s+(decimal\(\d+,\d+\))/i', $update, $m)) {
  echo "FAIL: zm_update-1.33.13.sql does not modify Monitors.MaxFPS\n";
  exit(1);
}
$expected = strtolower($m[1]);

$create = file_get_contents($root . '/db/zm_create.sql.in');
if (!preg_match('/CREATE TABLE `Monitors` \((.*?)\) ENGINE=/s', $create, $table)) {
  echo "FAIL: zm_create.sql.in has no Monitors table\n";
  exit(1);
}
if (!preg_match('/`MaxFPS`\s+(decimal\(\d+,\d+\))/i', $table[1], $col)) {
  echo "FAIL: zm_create.sql.in Monitors has no MaxFPS decimal column\n";
  exit(1);
}
$actual = strtolower($col[1]);

if ($actual !== $expected) {
  echo "FAIL: zm_create.sql.in Monitors.MaxFPS is $actual, an upgraded database has $expected\n";
  exit(1);
}
echo "SUMMARY: Monitors.MaxFPS is $actual in zm_create.sql.in and after zm_update-1.33.13.sql\n";
exit(0);
