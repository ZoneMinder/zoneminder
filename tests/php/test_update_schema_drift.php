<?php

/**
 * An upgraded database must match zm_create.sql.in. zm_update-1.39.57.sql brings
 * Zones.MonitorId to ON DELETE CASCADE and the EventId of the Events_Hour, Day,
 * Week, Month and Archived tables to bigint, and version.txt must name the newest
 * update script so zmupdate.pl runs it.
 * Usage: php test_update_schema_drift.php [repo_root]
 */

$root = $argv[1] ?? __DIR__ . '/../..';
$failures = 0;
function check($ok, $msg) {
  global $failures;
  echo ($ok ? 'ok' : 'FAIL') . ' - ' . $msg . "\n";
  if (!$ok) $failures++;
}

$create = file_get_contents($root . '/db/zm_create.sql.in');
function table($create, $name) {
  return preg_match('/CREATE TABLE `' . $name . '` \((.*?)\) ENGINE=/s', $create, $m) ? $m[1] : '';
}

$versions = [];
foreach (glob($root . '/db/zm_update-*.sql*') as $file) {
  if (preg_match('/zm_update-([\d.]+)\.sql(\.in)?$/', basename($file), $m)) $versions[] = $m[1];
}
usort($versions, 'version_compare');
check(trim(file_get_contents($root . '/version.txt')) === end($versions), 'version.txt is the newest update script version ' . end($versions));

$path = $root . '/db/zm_update-1.39.57.sql';
$update = is_file($path) ? file_get_contents($path) : '';
check($update !== '', 'zm_update-1.39.57.sql exists');

check(preg_match('/FOREIGN KEY \(`MonitorId`\) REFERENCES `Monitors` \(`Id`\) ON DELETE CASCADE/', table($create, 'Zones')) === 1,
  'zm_create.sql.in cascades Zones.MonitorId');
check(preg_match('/DROP FOREIGN KEY .*ADD FOREIGN KEY \(`MonitorId`\) REFERENCES `Monitors` \(`Id`\) ON DELETE CASCADE/', $update) === 1,
  'the update replaces the Zones foreign key with a cascading one');

foreach (['Hour', 'Day', 'Week', 'Month', 'Archived'] as $bucket) {
  $t = 'Events_' . $bucket;
  check(preg_match('/`EventId` BIGINT unsigned/i', table($create, $t)) === 1, "zm_create.sql.in has $t.EventId bigint");
  check(preg_match('/ALTER TABLE `' . $t . '` MODIFY `EventId` BIGINT unsigned NOT NULL/i', $update) === 1, "the update widens $t.EventId");
}

// MySQL 8 reports bigint without a display width, so a column_type comparison never matches there.
check(!preg_match('/column_type\s*=\s*.int/i', $update), 'the update tests data_type, not column_type');

exit($failures ? 1 : 0);
