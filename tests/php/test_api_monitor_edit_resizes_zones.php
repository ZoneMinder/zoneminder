<?php
// PUT /api/monitors/<id> with a new Width or Height must keep the zones on the same part of
// the picture, as the console's save does. zmc reads integer coordinates as pixels, so a
// pixel zone that is left alone covers the wrong area at the new size; it is turned into
// percent, a rotation swaps x and y, and the pixel thresholds of a non-percent zone follow
// the area.
//
// MonitorsController can't be loaded without CakePHP, so edit() and resizeZones() are taken
// from the source and run against stubs.
// Run as: php tests/php/test_api_monitor_edit_resizes_zones.php [root]

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$file = $root.'/web/api/app/Controller/MonitorsController.php';
if (!is_file($file)) {
  echo "FAIL - controller not found at $file\n";
  exit(1);
}
$source = file_get_contents($file);

function extractMethod($source, $signature) {
  $start = strpos($source, $signature);
  if ($start === false) return '';
  $open = strpos($source, '{', $start);
  $depth = 0;
  for ($i = $open; $i < strlen($source); $i++) {
    if ($source[$i] == '{') $depth++;
    else if ($source[$i] == '}' && --$depth == 0) break;
  }
  return substr($source, $start, $i - $start + 1);
}
$edit = extractMethod($source, 'public function edit(');
if ($edit === '') {
  echo "FAIL - edit() not found in $file\n";
  exit(1);
}
// The original has no helper: the zones are then simply never touched.
$resize = extractMethod($source, 'private function resizeZones(');

$scratch = '/tmp/numes-tests';
if (!is_dir($scratch)) @mkdir($scratch, 0777, true);
$empty = tempnam($scratch, 'mresize');
$helpers = tempnam($scratch, 'mresizefn');
// The real functions.php needs the web bootstrap; these are the helpers the fix uses.
$fn = file_get_contents($root.'/web/includes/functions.php');
$wanted = '';
foreach (array('getPolyArea', 'mapCoords', 'pointsToCoords', 'coordsToPoints', 'convertPixelPointsToPercent', 'convertZoneThresholdsToPercent') as $name) {
  $wanted .= extractMethod($fn, 'function '.$name.'(')."\n";
}
file_put_contents($helpers, "<?php\n".$wanted);
$edit = str_replace('__DIR__ .\'/../../../includes/Monitor.php\'', var_export($empty, true), $edit);
$resize = str_replace('__DIR__ .\'/../../../includes/functions.php\'', var_export($helpers, true), $resize);
$resize = str_replace('__DIR__ .\'/../../../includes/Zone.php\'', var_export($empty, true), $resize);
require_once $helpers;

if (!class_exists('NotFoundException')) { class NotFoundException extends Exception {} }
if (!class_exists('UnauthorizedException')) { class UnauthorizedException extends Exception {} }
if (!class_exists('BadRequestException')) { class BadRequestException extends Exception {} }
if (!function_exists('__')) { function __($s) { return $s; } }

eval('namespace ZM; class Monitor {
  private $row;
  function __construct($row) { $this->row = is_array($row) ? $row : array(); }
  function canEdit() { return true; }
  function commandFieldChangesNotAllowed($fields) { return array(); }
  function Type() { return "Local"; }
  function Controllable() { return false; }
  function Capturing() { return "None"; }
  function Deleted() { return false; }
  function zmcControl($cmd) {}
  function sendControlCommand($cmd) {}
}
class Zone {
  static $zones = array();
  static $saves = array();
  private $row;
  function __construct($row) { $this->row = $row; }
  static function find($parameters = array(), $options = array()) {
    $out = array();
    foreach (self::$zones as $row) if ($row["MonitorId"] == $parameters["MonitorId"]) $out[] = new Zone($row);
    return $out;
  }
  function __call($name, $args) { return array_key_exists($name, $this->row) ? $this->row[$name] : null; }
  function save($values = null) { self::$saves[$this->row["Id"]] = $values; return true; }
}');

class FakeModel {
  public $id;
  public $rows;
  public $finds = 0;
  function __construct($before, $after) { $this->rows = array($before, $after); }
  function exists($id = null) { return true; }
  // The row before the save, then the saved one.
  function find($type = 'first', $options = array()) {
    $row = $this->rows[min($this->finds, 1)];
    if ($this->finds == 1) $this->finds = 1; else $this->finds++;
    return array('Monitor' => $row);
  }
  function save($data = null) { $this->finds = 1; return true; }
  function invalidFields() { return array(); }
}
class FakeRequest {
  public $data = array();
  function is($types) { return true; }
}
class FakeController {
  public $Monitor;
  public $request;
  public $out;
  function set($a) { $this->out = $a; }
  function requestCommandFields() { return array(); }
  function pinRequestId($model, $id) { $model->id = $id; }
}
eval('class FakeControllerWithEdit extends FakeController { '.$edit."\n".$resize.' }');

function zone($id, $coords, $units, $extra = array()) {
  return array_merge(array('Id' => $id, 'MonitorId' => 1, 'Coords' => $coords, 'Units' => $units, 'Area' => 0,
    'MinAlarmPixels' => null, 'MaxAlarmPixels' => null, 'MinFilterPixels' => null, 'MaxFilterPixels' => null,
    'MinBlobPixels' => null, 'MaxBlobPixels' => null), $extra);
}
function run($oldW, $oldH, $newW, $newH, $zones) {
  ZM\Zone::$zones = $zones;
  ZM\Zone::$saves = array();
  $c = new FakeControllerWithEdit();
  $c->Monitor = new FakeModel(array('Id' => 1, 'Type' => 'Local', 'Width' => $oldW, 'Height' => $oldH),
    array('Id' => 1, 'Type' => 'Local', 'Width' => $newW, 'Height' => $newH));
  $c->request = new FakeRequest();
  $c->request->data = array('Monitor' => array('Width' => $newW, 'Height' => $newH));
  $c->edit(1);
  return array($c->out['message'], ZM\Zone::$saves);
}

// A pixel zone at 640x480 is half the picture wide; at 1280x960 it must still be.
list($msg, $saves) = run(640, 480, 1280, 960, array(
  zone(1, '0,0 320,0 320,240 0,240', 'Pixels', array('Area' => 76800, 'MinAlarmPixels' => 1536, 'MaxAlarmPixels' => 0)),
  zone(2, '0.00,0.00 50.00,0.00 50.00,50.00 0.00,50.00', 'Percent', array('Area' => 2500, 'MinAlarmPixels' => 5)),
));
check('edit answers Saved', $msg, 'Saved');
check('pixel zone coords become percent of the old size', isset($saves[1]['Coords']) ? $saves[1]['Coords'] : null, '0.00,0.00 50.00,0.00 50.00,50.00 0.00,50.00');
check('pixel zone is saved as Percent', isset($saves[1]['Units']) ? $saves[1]['Units'] : null, 'Percent');
check('pixel zone area is in percent units', isset($saves[1]['Area']) ? $saves[1]['Area'] : null, 2500.0);
check('pixel zone threshold becomes a percent of the zone', isset($saves[1]['MinAlarmPixels']) ? $saves[1]['MinAlarmPixels'] : null, 2.0);
check('percent zone is left alone', array_key_exists(2, $saves), false);

// Rotation swaps x and y of a percent zone and leaves its percent thresholds.
list($msg, $saves) = run(640, 480, 480, 640, array(
  zone(3, '0.00,0.00 50.00,0.00 50.00,20.00 0.00,20.00', 'Percent', array('Area' => 1000, 'MinAlarmPixels' => 5)),
));
check('rotation swaps x and y', isset($saves[3]['Coords']) ? $saves[3]['Coords'] : null, '0.00,0.00 0.00,50.00 20.00,50.00 20.00,0.00');
check('rotation keeps percent thresholds', isset($saves[3]) && array_key_exists('MinAlarmPixels', $saves[3]), false);

// A non-percent zone with decimals keeps its coordinates and its pixel thresholds follow the area.
list($msg, $saves) = run(640, 480, 1280, 960, array(
  zone(4, '0.00,0.00 50.00,0.00 50.00,50.00 0.00,50.00', 'Pixels', array('Area' => 2500, 'MinAlarmPixels' => 100, 'MaxAlarmPixels' => 400)),
));
check('pixel thresholds are rescaled by the area', isset($saves[4]) ? array($saves[4]['MinAlarmPixels'], $saves[4]['MaxAlarmPixels']) : null, array(400, 1600));
check('coords are not rewritten on a plain resize', isset($saves[4]) && array_key_exists('Coords', $saves[4]), false);

// No size change: nothing to do.
list($msg, $saves) = run(640, 480, 640, 480, array(zone(5, '0,0 320,0 320,240 0,240', 'Pixels')));
check('same size touches no zone', $saves, array());

@unlink($empty);
@unlink($helpers);
echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
