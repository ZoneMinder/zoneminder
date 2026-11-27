<?php
// A deleted monitor stays in the Monitors table for an undelete, with its zones. Adding, editing
// or deleting one of those zones through the API must not start or restart zmc for it, the way
// MonitorsController::edit() already skips a Deleted monitor.
//
// Pure source scan:
//   php tests/php/test_api_zone_deleted_monitor_zmc.php [root]

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$path = $root.'/web/api/app/Controller/ZonesController.php';

$passes = 0;
$failures = 0;

foreach (file($path) as $n => $line) {
  if (strpos($line, '->zmcControl(') === false) continue;
  if (strpos($line, 'Deleted()') !== false) {
    $passes++;
    echo 'ok - line '.($n+1)." skips deleted monitors\n";
  } else {
    $failures++;
    echo 'not ok - line '.($n+1).' calls zmcControl() for deleted monitors: '.trim($line)."\n";
  }
}

if (!$passes and !$failures) {
  $failures++;
  echo "not ok - no zmcControl() calls found\n";
}

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
?>
