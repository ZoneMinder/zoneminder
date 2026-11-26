<?php
// Zone::beforeSave() keeps the zone the way the web editor (includes/actions/zone.php) saves it:
//   - NumCoords and Area follow Coords instead of staying at the column default of 0
//   - a threshold over 100 is a pixel count, stored as a percentage of the zone,
//     also when an edit sends the thresholds without Coords
// The Cake model is stubbed, so this runs from a source checkout:
//   php tests/php/test_api_zone_save_fields.php

class App { public static function uses($class, $package) {} }
class AppModel {
  public $alias = 'Zone';
  public $id = null;
  public $data = array();
  public $Monitor;
  // The stored row an edit reads back
  public static $stored = array('MonitorId' => 7);
  public function field($f) { return isset(self::$stored[$f]) ? self::$stored[$f] : null; }
}
require_once __DIR__.'/../../web/api/app/Model/Zone.php';

class StubMonitor {
  public function find($type, $options) {
    return array('Monitor' => array('Id' => 7, 'Width' => 1000, 'Height' => 500, 'Orientation' => 'ROTATE_0'));
  }
}

$passes = 0;
$failures = 0;
function check($name, $got, $expected) {
  global $passes, $failures;
  if ($got == $expected and gettype($got) == gettype($expected)) { $passes++; echo "ok - $name\n"; }
  else { $failures++; echo "not ok - $name (got ".var_export($got, true)." expected ".var_export($expected, true).")\n"; }
}

function save($data, $id = null) {
  $z = new Zone();
  $z->Monitor = new StubMonitor();
  $z->id = $id;
  $z->data = array('Zone' => $data);
  $z->beforeSave();
  return $z->data['Zone'];
}

$full = '0.00,0.00 100.00,0.00 100.00,100.00 0.00,100.00';
$r = save(array('MonitorId' => 7, 'Name' => 'a', 'Units' => 'Percent', 'Coords' => $full));
check('NumCoords written for 4 points', $r['NumCoords'], 4);
check('Area of the full frame in percent', $r['Area'], 10000.0);

$r = save(array('MonitorId' => 7, 'Name' => 'a', 'Units' => 'Percent', 'Coords' => '0,0 50,0 0,50'));
check('NumCoords written for a triangle', $r['NumCoords'], 3);
check('Area of the triangle', $r['Area'], 1250.0);
check('Integer percent Coords still get decimals', $r['Coords'], '0.00,0.00 50.00,0.00 0.00,50.00');

$r = save(array('Name' => 'renamed'), 3);
check('No Coords, NumCoords untouched', isset($r['NumCoords']), false);
check('No Coords, Area untouched', isset($r['Area']), false);

// Full frame of a 1000x500 monitor is 500000 pixels; 25000 pixels is 5 percent.
$r = save(array('MonitorId' => 7, 'Name' => 'a', 'Units' => 'Percent', 'Coords' => $full,
  'MinAlarmPixels' => 25000, 'MaxAlarmPixels' => 75, 'MaxBlobPixels' => 99999999));
check('Pixel threshold converted to percent', $r['MinAlarmPixels'], 5.0);
check('Percent threshold left alone', $r['MaxAlarmPixels'], 75);
check('Converted threshold is capped at 100', $r['MaxBlobPixels'], 100);

// Pixel coordinates (no decimal point): zmc reads thresholds as pixel counts.
$r = save(array('MonitorId' => 7, 'Name' => 'a', 'Units' => 'Pixels', 'Coords' => '0,0 999,0 999,499 0,499', 'MinAlarmPixels' => 25000));
check('Pixel zone keeps its pixel threshold', $r['MinAlarmPixels'], 25000);
check('Pixel zone NumCoords', $r['NumCoords'], 4);

// An edit that sends only thresholds is measured against the stored zone.
AppModel::$stored = array('MonitorId' => 7, 'Units' => 'Percent', 'Coords' => $full, 'Area' => 10000);
$r = save(array('MinAlarmPixels' => 25000, 'MaxAlarmPixels' => 40), 3);
check('Thresholds-only edit converts a pixel count', $r['MinAlarmPixels'], 5.0);
check('Thresholds-only edit leaves a percentage alone', $r['MaxAlarmPixels'], 40);
check('Thresholds-only edit does not write Coords', isset($r['Coords']), false);

AppModel::$stored = array('MonitorId' => 7, 'Units' => 'Pixels', 'Coords' => '0,0 999,0 999,499 0,499', 'Area' => 498501);
$r = save(array('MinAlarmPixels' => 25000), 4);
check('Thresholds-only edit of a pixel zone keeps the pixel count', $r['MinAlarmPixels'], 25000);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
