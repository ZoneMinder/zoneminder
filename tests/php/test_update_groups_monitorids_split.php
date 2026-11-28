<?php

/**
 * zm_update-1.31.16.sql splits Groups.MonitorIds into Groups_Monitors rows. A list
 * with an empty entry (",3" is what adding a monitor to an empty group wrote, "1,,2"
 * and "1," also occur) gives an empty MonitorId that MySQL 8 and MariaDB refuse in
 * strict mode, so the upgrade stops. The split must keep only numeric entries.
 * Usage: php test_update_groups_monitorids_split.php [repo_root]
 */

$root = $argv[1] ?? __DIR__ . '/../..';
$file = $root . '/db/zm_update-1.31.16.sql';
$sql = @file_get_contents($file);
if ($sql === false) {
  echo "FAIL: cannot read $file\n";
  exit(1);
}

$checked = 0;
$failures = 0;
foreach (explode(';', $sql) as $statement) {
  if (stripos($statement, 'INTO Groups_Monitors') === false or stripos($statement, 'SUBSTRING_INDEX') === false) {
    continue;
  }
  $checked++;
  $split = 'SUBSTRING_INDEX(SUBSTRING_INDEX(t.MonitorIds, \',\', n.n), \',\', -1)';
  if (!preg_match('/WHERE\b.*' . preg_quote($split, '/') . '\s+REGEXP\s+\'[^\']*\[0-9\]\+/is', $statement)) {
    echo "FAIL: the Groups_Monitors insert does not filter the split entries to numbers\n";
    $failures++;
  }
}

if ($checked == 0) {
  echo "FAIL: no Groups_Monitors split found in $file\n";
  exit(1);
}
if ($failures > 0) {
  exit(1);
}
echo "PASS: $checked Groups_Monitors split keeps numeric entries only\n";
exit(0);
