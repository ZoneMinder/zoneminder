<?php
// Saving a zone whose id is not a zone of the monitor (deleted in another tab, or
// another monitor's zone) carried on with $zone === false: getFormChanges() ran
// foreach over false, the UPDATE matched no row, and the save was audited and
// answered as if it had worked. It has to refuse and set $error_message. A zone
// that exists must still save.
//
// Runs the real web/includes/actions/zone.php and web/includes/functions.php's
// getFormChanges() against a stubbed Monitor and database. The action pulls its
// classes in with relative require_once calls, so the script chdirs into a
// directory of empty stub files first.
//
//   php tests/php/test_zone_save_missing_zone.php [repo root]

namespace ZM {
  function Warning($s) {}
  function Error($s) {}
  function AuditAction($action, $type, $id, $details) {
    Rec::$audits[] = array($action, $type, $id);
  }

  class Rec {
    public static $queries = array();
    public static $audits = array();
  }

  class Monitor {
    private $id;
    public function __construct($id = null) { $this->id = $id; }
    public function Id() { return $this->id; }
    public function Deleted() { return false; }
    public function Type() { return 'Ffmpeg'; }
    public function ViewWidth() { return 640; }
    public function ViewHeight() { return 480; }
    public function Controllable() { return false; }
    public function zmcControl($m) {}
    public function sendControlCommand($m) {}
  }
}

namespace {
  $dir = sys_get_temp_dir().'/zm_test_zone_missing_'.getmypid();
  mkdir($dir.'/includes', 0755, true);
  foreach (array('Zone', 'ZonePreset') as $f) {
    file_put_contents("$dir/includes/$f.php", "<?php\n");
  }
  chdir($dir);
  set_include_path(get_include_path().PATH_SEPARATOR.$dir);

  // getFormChanges() from the real functions.php, which cannot be included whole.
  $src = file_get_contents(__DIR__.'/../../web/includes/functions.php');
  if (!preg_match('/^function getFormChanges\(.*?^}$/ms', $src, $m)) {
    echo "FAIL getFormChanges not found in functions.php\n";
    exit(1);
  }
  eval($m[0]);
  function dbEscape($s) { return "'".addslashes($s)."'"; }

  function canEdit($p, $id = null) { return true; }
  function validInt($v) { return (int)$v; }
  // Zone 5 of monitor 8 exists; anything else is not a zone of it.
  function dbFetchOne($sql, $col = null, $args = array()) {
    if ($args == array(8, 5)) return array('Id' => 5, 'Name' => 'Old', 'Type' => 'Active');
    return false;
  }
  function dbQuery($sql, $args = array()) {
    ZM\Rec::$queries[] = array($sql, $args);
    return true;
  }
  function coordsToPoints($c) { return array(); }
  function pointsToCoords($p) { return ''; }
  function getPolyArea($p) { return 10000; }
  function localRedirectUrl($referer, $default) { return $default; }
  function daemonCheck() { return false; }
  function translate($s) { return $s; }

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

  function saveZone($zid) {
    global $action, $error_message, $redirect;
    ZM\Rec::$queries = array();
    ZM\Rec::$audits = array();
    $_REQUEST = array(
      'mid' => '8',
      'action' => 'zone',
      'zid' => (string)$zid,
      'newZone' => array('Name' => 'New', 'Type' => 'Active'),
    );
    $action = 'zone';
    $error_message = '';
    $redirect = null;
    $warnings = array();
    set_error_handler(function($no, $str) use (&$warnings) { $warnings[] = $str; return true; });
    ob_start();
    include __DIR__.'/../../web/includes/actions/zone.php';
    ob_end_clean();
    restore_error_handler();
    return array($error_message, $warnings);
  }

  list($error, $warnings) = saveZone(9);
  check('saving a zone that is not found writes nothing', ZM\Rec::$queries, array());
  check('saving a zone that is not found is not audited', ZM\Rec::$audits, array());
  check('saving a zone that is not found reports an error', $error !== '', true);
  check('saving a zone that is not found raises no PHP warning', $warnings, array());

  list($error, $warnings) = saveZone(5);
  check('saving an existing zone updates it', count(ZM\Rec::$queries), 1);
  check('saving an existing zone reports no error', $error, '');

  system('rm -rf '.escapeshellarg($dir));

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
