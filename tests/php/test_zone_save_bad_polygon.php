<?php
// Saving a zone whose Coords are not a polygon (fewer than 3 points, or a
// malformed point) was refused with only a log warning and a redirect to the
// zones list, so the edit was lost without a word. The action now sets
// $error_message and stays on the zone editor. A valid polygon still saves.
//
// Runs the real web/includes/actions/zone.php against a stubbed Monitor and
// database. The action pulls its classes in with relative require_once calls,
// so the script chdirs into a directory of empty stub files first.
//
//   php tests/php/test_zone_save_bad_polygon.php [repo root]

namespace ZM {
  function Warning($s) {}
  function Error($s) {}
  function AuditAction() {}

  class Rec {
    public static $queries = array();
  }

  class Monitor {
    private $id;
    public function __construct($id = null) { $this->id = $id; }
    public function Id() { return $this->id; }
    public function Deleted() { return $this->id == 7; }
    public function Type() { return 'Ffmpeg'; }
    public function ViewWidth() { return 640; }
    public function ViewHeight() { return 480; }
    public function Controllable() { return false; }
    public function zmcControl($m) {}
    public function sendControlCommand($m) {}
  }
}

namespace {
  // The action's class imports are relative require_once calls.
  $dir = sys_get_temp_dir().'/zm_test_zone_polygon_'.getmypid();
  mkdir($dir.'/includes', 0755, true);
  foreach (array('Zone', 'ZonePreset') as $f) {
    file_put_contents("$dir/includes/$f.php", "<?php\n");
  }
  chdir($dir);
  set_include_path(get_include_path().PATH_SEPARATOR.$dir);

  function canEdit($p, $id = null) { return true; }
  function validInt($v) { return (int)$v; }
  function dbFetchOne($sql, $col = null, $args = array()) { return null; }
  function dbQuery($sql, $args = array()) {
    ZM\Rec::$queries[] = array($sql, $args);
  }
  // As the real helpers: "x,y x,y ..." to points and back.
  function coordsToPoints($c) {
    $points = array();
    foreach (preg_split('/\s+/', trim($c)) as $pair) {
      if (preg_match('/^(\d+(?:\.\d+)?),(\d+(?:\.\d+)?)$/', $pair, $m)) $points[] = array('x' => $m[1], 'y' => $m[2]);
    }
    return $points;
  }
  function pointsToCoords($p) { return implode(' ', array_map(function($q) { return $q['x'].','.$q['y']; }, $p)); }
  function getPolyArea($p) { return 10000; }
  function localRedirectUrl($referer, $default) { return $default; }
  function daemonCheck() { return false; }
  function translate($s) { return $s; }
  function getFormChanges($old, $new, $types = false) {
    $c = array();
    foreach ($new as $k => $v) {
      if (!array_key_exists($k, $old) or (string)$old[$k] !== (string)$v) $c[$k] = "`$k` = '$v'";
    }
    return $c;
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
      echo "FAIL - $name: expected ".var_export($expected, true).', got '.var_export($actual, true)."\n";
    }
  }

  function insertedZones($queries) {
    $found = array();
    foreach ($queries as $q) {
      if (stripos(ltrim($q[0]), 'INSERT INTO Zones') === 0) $found[] = $q;
    }
    return $found;
  }

  function saveZone($mid, $newZone) {
    global $action, $error_message, $redirect;
    ZM\Rec::$queries = array();
    $_REQUEST = array(
      'mid' => (string)$mid,
      'action' => 'zone',
      'zid' => '0',
      'newZone' => $newZone,
    );
    $action = 'zone';
    $error_message = '';
    $redirect = null;
    ob_start();
    include __DIR__.'/../../web/includes/actions/zone.php';
    ob_end_clean();
    return $error_message;
  }

  $zone = array('Name' => 'Test zone', 'Type' => 'Active', 'MinAlarmPixels' => '5');

  $error = saveZone(8, $zone + array('Coords' => '10,10 20,20'));
  check('a two point zone is not saved', insertedZones(ZM\Rec::$queries), array());
  check('it says why', strpos($error, 'polygon') !== false, true);
  check('it stays on the zone editor', $GLOBALS['redirect'], null);

  $error = saveZone(8, $zone + array('Coords' => '10,10 20,x 30,30'));
  check('a zone with a malformed point is not saved', insertedZones(ZM\Rec::$queries), array());
  check('it says why too', strpos($error, 'polygon') !== false, true);

  $error = saveZone(8, $zone + array('Coords' => '10,10 50,10 50,50'));
  check('a triangle is saved', count(insertedZones(ZM\Rec::$queries)), 1);
  check('with no error', $error, '');

  system('rm -rf '.escapeshellarg($dir));

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
