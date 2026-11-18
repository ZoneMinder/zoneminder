<?php
// Legacy pixel zones (coordinates written without a decimal point, which zmc
// reads as pixels, with pixel-count thresholds) must be shown by the zone
// editor as percentages. The values alone cannot tell them from percentages
// when every coordinate is 100 or less, and a threshold of 100 or less was
// shown as that many percent of the zone instead of that many pixels.
//
// The helpers are taken from web/includes/functions.php by name, as that file
// needs the generated config to be included.
//
//   php tests/php/test_zone_legacy_pixels.php

$source = file_get_contents(__DIR__.'/../../web/includes/functions.php');
foreach (array('getPolyArea', 'mapCoords', 'pointsToCoords', 'convertPixelPointsToPercent', 'convertZoneThresholdsToPercent') as $name) {
  if (!preg_match('/^(?:\/\/[^\n]*\n)*function '.$name.'\(.*?^}\n/ms', $source, $m)) {
    echo "not ok - $name not found in functions.php\n";
    exit(1);
  }
  eval(preg_replace('/^\/\/[^\n]*\n/m', '', $m[0]));
}

$passes = 0;
$failures = 0;
function check($name, $actual, $expected) {
  global $passes, $failures;
  if ($actual === $expected) {
    $passes++;
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "not ok - $name: expected ".var_export($expected, true).', got '.var_export($actual, true)."\n";
  }
}

// 640x480 monitor, zone in its top left corner: 90x90 pixels
$points = array(array('x'=>0.0, 'y'=>0.0), array('x'=>90.0, 'y'=>0.0), array('x'=>90.0, 'y'=>90.0), array('x'=>0.0, 'y'=>90.0));
$copy = $points;
convertPixelPointsToPercent($copy, 640, 480);
check('small pixel zone is left alone without force', $copy[1]['x'], 90.0);
$copy = $points;
check('forced conversion reports pixels', convertPixelPointsToPercent($copy, 640, 480, true), true);
check('small pixel zone x converted', $copy[1]['x'], 14.06);
check('small pixel zone y converted', $copy[2]['y'], 18.75);

// 90x90 px of 640x480 is 8100 px; a threshold of 50 px is 0.62%
$zone = array('MinAlarmPixels'=>'50', 'MaxAlarmPixels'=>null, 'MinFilterPixels'=>'', 'MinBlobPixels'=>'8100');
convertZoneThresholdsToPercent($zone, 8100, true);
check('legacy pixel threshold of 50 becomes a percentage', $zone['MinAlarmPixels'], 0.62);
check('legacy null threshold stays null', $zone['MaxAlarmPixels'], null);
check('legacy empty threshold stays empty', $zone['MinFilterPixels'], '');
check('legacy threshold of the whole area is 100', (float)$zone['MinBlobPixels'], 100.0);

$zone = array('MinAlarmPixels'=>'50', 'MaxAlarmPixels'=>'200');
convertZoneThresholdsToPercent($zone, 8100, false);
check('percent zone keeps a threshold of 50', $zone['MinAlarmPixels'], '50');
check('percent zone converts a stray pixel count', $zone['MaxAlarmPixels'], 2.47);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
