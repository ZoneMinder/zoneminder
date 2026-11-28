<?php
// A deleted monitor stays in the Monitors table so it can be undeleted, and
// keeps its zones. The zone editor action must not add a zone to one of those
// deleted monitors: nothing would load it and the monitor is not running. It
// has to refuse the save and set $error_message instead. A live monitor must
// still save normally.
//
// Runs the real web/includes/actions/zone.php against a stubbed Monitor and
// database. The action pulls its classes in with relative require_once calls,
// so the script chdirs into a directory of empty stub files first.
//
//   php tests/php/test_zone_save_deleted_monitor.php [repo root]

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
  $dir = sys_get_temp_dir().'/zm_test_zone_action_'.getmypid();
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
  function coordsToPoints($c) { return array(); }
  function pointsToCoords($p) { return ''; }
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

  $newZone = array('Name' => 'Test zone', 'Type' => 'Active', 'MinAlarmPixels' => '5');

  // Monitor 7 is deleted, its zone must not be written.
  $error = saveZone(7, $newZone);
  check('a zone is not inserted for a deleted monitor', insertedZones(ZM\Rec::$queries), array());
  check('saving a zone for a deleted monitor reports an error', $error !== '', true);

  // Monitor 8 is live, saving must still work.
  saveZone(8, $newZone);
  check('a zone is still inserted for a live monitor', count(insertedZones(ZM\Rec::$queries)), 1);

  system('rm -rf '.escapeshellarg($dir));

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
