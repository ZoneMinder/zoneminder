<?php
// zmc fatals on a WebSite monitor ("Bogus monitor type"), so the zones API
// must not start or restart one when a zone of such a monitor is added, edited
// or deleted. Every zmcControl() call in ZonesController has to skip them.
//
// Pure source scan:
//   php tests/php/test_api_zone_website_zmc.php

$path = __DIR__.'/../../web/api/app/Controller/ZonesController.php';

$passes = 0;
$failures = 0;

foreach (file($path) as $n => $line) {
  if (strpos($line, '->zmcControl(') === false) continue;
  if (strpos($line, "Type() != 'WebSite'") !== false) {
    $passes++;
    echo 'ok - line '.($n+1)." skips WebSite monitors\n";
  } else {
    $failures++;
    echo 'not ok - line '.($n+1).' calls zmcControl() for WebSite monitors: '.trim($line)."\n";
  }
}

if (!$passes and !$failures) {
  $failures++;
  echo "not ok - no zmcControl() calls found\n";
}

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
?>
