<?php
// The zones list converts a zone whose Coords have no decimal point from pixels to
// percent, but Zone::svg_polygon() only did so when a value was over 100, so a small
// legacy pixel zone (every coordinate 100 or less) was drawn over the image as if
// the pixels were percentages.
//
// Run as: php tests/php/test_zone_svg_legacy_pixels.php [root]   (root defaults to the repo)

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$failures = 0;
function check($n, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $n\n";
  else { $failures++; echo "FAIL - $n (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

$src = file_get_contents($root.'/web/includes/functions.php');
foreach (array('mapCoords', 'pointsToCoords', 'coordsToPoints') as $name) {
  if (!preg_match('/^function '.$name.'\(.*?^}\n/ms', $src, $m)) { echo "not ok - $name not found\n"; exit(1); }
  eval($m[0]);
}
function validHtmlStr($s) { return htmlspecialchars($s); }

$zsrc = file_get_contents($root.'/web/includes/Zone.php');
if (!preg_match('/public function svg_polygon\(.*?^  }\n/ms', $zsrc, $m)) { echo "not ok - svg_polygon not found\n"; exit(1); }
eval('class FakeZone {
  public $c; function __construct($c) { $this->c = $c; }
  function Coords() { return $this->c; }
  function AreaCoords() { return preg_replace(\'/\\s+/\', \',\', $this->c); }
  function Type() { return "Active"; }
  function MonitorId() { return 1; }
  function Id() { return 2; }
  function Name() { return "z"; }
  function Monitor() { return new FakeMonitor(); }
  '.$m[0].'}');
class FakeMonitor {
  function ViewWidth() { return 640; }
  function ViewHeight() { return 480; }
}

function pts($svg) { preg_match('/points="([^"]*)"/', $svg, $m); return $m[1]; }

$z = new FakeZone('0,0 80,0 80,60 0,60');
check('small pixel zone is converted to percent', pts($z->svg_polygon(640, 480)), '0.00,0.00,12.50,0.00,12.50,12.50,0.00,12.50');
$z = new FakeZone('0.00,0.00 80.00,0.00 80.00,60.00 0.00,60.00');
check('percent zone is left alone', pts($z->svg_polygon(640, 480)), '0.00,0.00,80.00,0.00,80.00,60.00,0.00,60.00');
$z = new FakeZone('0,0 640,0 640,480 0,480');
check('large pixel zone is converted', pts($z->svg_polygon(640, 480)), '0.00,0.00,100.00,0.00,100.00,100.00,0.00,100.00');
// The event view calls svg_polygon() without a size.
$z = new FakeZone('0,0 80,0 80,60 0,60');
check('pixel zone without a size uses the monitor size', pts($z->svg_polygon()), '0.00,0.00,12.50,0.00,12.50,12.50,0.00,12.50');
echo $failures ? "$failures failure(s)\n" : "all passed\n";
exit($failures ? 1 : 0);
