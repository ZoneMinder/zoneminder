<?php
// A deleted monitor keeps its Monitors row (Deleted=1) along with its Capturing
// and Controllable values, so the monitor list on the control view must filter
// it out in SQL.
//
// Run as: php tests/php/test_control_view_deleted_monitor.php <repo root>

$root = $argv[1] ?? dirname(__DIR__, 2);
$src = file_get_contents($root.'/web/skins/classic/views/control.php');
if (!preg_match('/^\$sql = "(SELECT \* FROM Monitors WHERE[^"]*)";/m', $src, $m)) {
  echo "FAIL - could not find the monitor query in control.php\n";
  exit(1);
}
if (preg_match('/`?Deleted`?\s*(=\s*(0|false)|!=\s*1)/i', $m[1])) {
  echo "ok - control view query skips deleted monitors\n";
  exit(0);
}
echo 'FAIL - control view query does not exclude Deleted: '.$m[1]."\n";
exit(1);
