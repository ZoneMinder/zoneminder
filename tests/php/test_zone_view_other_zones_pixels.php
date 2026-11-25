<?php
// The zone editor draws the monitor's other zones behind the one being edited, in a
// 0..100 viewBox. A legacy pixel zone (Coords without a decimal point, which zmc
// reads as pixels) was drawn with its pixel values as if they were percent, so a
// zone covering the left half of a 640x480 image became a polygon far outside the
// box. The zones list and the zone being edited are converted; these now are too.
//
// The helpers come from web/includes/functions.php and the loop from
// web/skins/classic/views/zone.php, both by name, as they need the generated config.
//
//   php tests/php/test_zone_view_other_zones_pixels.php   (from the repo root)

$root = __DIR__.'/../..';
$source = file_get_contents($root.'/web/includes/functions.php');
foreach (array('mapCoords', 'pointsToCoords', 'coordsToPoints', 'convertPixelPointsToPercent') as $name) {
  if (!preg_match('/^(?:\/\/[^\n]*\n)*function '.$name.'\(.*?^}\n/ms', $source, $m)) {
    echo "not ok - $name not found in functions.php\n";
    exit(1);
  }
  eval(preg_replace('/^\/\/[^\n]*\n/m', '', $m[0]));
}

$view = file_get_contents($root.'/web/skins/classic/views/zone.php');
if (!preg_match('/^if \( count\(\$other_zones\) \) \{\n.*?^  echo \$html;\n\}\n/ms', $view, $m)) {
  echo "not ok - other zones block not found in zone.php\n";
  exit(1);
}
$block = $m[0];

class FakeMonitor {
  public function ViewWidth() { return 640; }
  public function ViewHeight() { return 480; }
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

function draw($block, $zones) {
  $monitor = new FakeMonitor();
  $other_zones = $zones;
  ob_start();
  eval($block);
  return ob_get_clean();
}

$out = draw($block, array(array('Id'=>5, 'Type'=>'Active', 'Coords'=>'0,0 320,0 320,240 0,240')));
check('pixel zone is drawn in percent', $out,
  '<polygon id="zonePoly5" points="0.00,0.00,50.00,0.00,50.00,50.00,0.00,50.00" class="Active"/>');

// Every value is 100 or less, so only the missing decimal point says these are pixels.
$out = draw($block, array(array('Id'=>6, 'Type'=>'Inclusive', 'Coords'=>'0,0 64,0 64,48 0,48')));
check('small pixel zone is drawn in percent', $out,
  '<polygon id="zonePoly6" points="0.00,0.00,10.00,0.00,10.00,10.00,0.00,10.00" class="Inclusive"/>');

$out = draw($block, array(array('Id'=>7, 'Type'=>'Active', 'Coords'=>'10.00,10.00 60.00,10.00 60.00,50.00 10.00,50.00')));
check('percent zone is left alone', $out,
  '<polygon id="zonePoly7" points="10.00,10.00,60.00,10.00,60.00,50.00,10.00,50.00" class="Active"/>');

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
