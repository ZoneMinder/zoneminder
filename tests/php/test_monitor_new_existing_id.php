<?php
// Saving a new monitor with the Id of a live monitor typed in the Id box took
// the update path and overwrote that monitor with only the Create permission;
// it is now refused with an error, while reusing the Id of a deleted monitor
// still works.
//
// php tests/php/test_monitor_new_existing_id.php

namespace ZM {
  function Warning($s) {}
  function Debug($s) {}
  function Error($s) {}
  function AuditAction() {}
  function redactForLog($a) { return $a; }

  class Rec {
    public static $saved = array();
    public static $inserted = array();
    public static $queries = array();
    public static $zones = array();
    public static $db = array();
  }

  class Storage {
    public static function find() { return array(); }
    public function Path() { return sys_get_temp_dir().'/zm_test_storage'; }
    public function Name() { return 'Default'; }
  }

  class Monitor {
    private $f = array();
    public function __construct($id = null) {
      if ($id and isset(Rec::$db[$id])) $this->f = Rec::$db[$id];
    }
    public function __call($name, $args) {
      if (count($args)) $this->f[$name] = $args[0];
      return isset($this->f[$name]) ? $this->f[$name] : null;
    }
    public function Id($v = null) { return isset($this->f['Id']) ? $this->f['Id'] : null; }
    public function Storage() { return new Storage(); }
    public function zmcControl($m) {}
    public function zmtrackControl($m) {}
    public function sendControlCommand($m) {}
    public function commandFieldChangesNotAllowed($d) { return array(); }
    public function changes($new) {
      $c = array();
      foreach ($new as $k => $v) {
        if (!array_key_exists($k, $this->f) or (string)$this->f[$k] !== (string)$v) $c[$k] = $v;
      }
      return $c;
    }
    public function save($changes) {
      Rec::$saved[] = $changes;
      foreach ($changes as $k => $v) $this->f[$k] = $v;
      return true;
    }
    public function insert($changes) {
      Rec::$inserted[] = $changes;
      $this->f = $changes + array('Id' => 99);
      return true;
    }
    public function get_last_error() { return ''; }
  }

  class Zone {
    public function save($d) { return true; }
    public function get_last_error() { return ''; }
  }
}

namespace {
  define('ZM_OPT_X10', false);
  define('ZM_OPT_CONTROL', false);

  $root = isset($argv[1]) ? rtrim($argv[1], '/') : __DIR__.'/../..';
  if ($root === '' or $root[0] !== '/') $root = getcwd().'/'.$root;

  function canEdit($p, $id = null) { return true; }
  function canView($p, $id = null) { return true; }
  function canCreate($p) { return true; }
  function validInt($v) { return preg_replace('/[^\-\d]/', '', $v); }
  function validCardinal($v) { return preg_replace('/\D/', '', $v); }
  function validDevicePath($v) { return $v; }
  function validHtmlStr($v) { return $v; }
  function dbFetchOne($sql, $col = null, $args = array()) {
    return (strpos($sql, 'MAX(Sequence)') !== false) ? 7 : null;
  }
  function dbFetchAll($sql, $col = null, $args = array()) {
    return (strpos($sql, 'FROM Zones') !== false) ? ZM\Rec::$zones : array();
  }
  function dbQuery($sql, $args = array()) {
    if (strpos($sql, 'UPDATE Zones') === 0) ZM\Rec::$queries[] = array($sql, $args);
    return true;
  }
  function dbLastError() { return ''; }
  function coordsToPoints($c) { return array(); }
  function pointsToCoords($p) { return ''; }
  function getFormChanges($old, $new, $types = false) {
    $c = array();
    foreach ($new as $k => $v) {
      if (!array_key_exists($k, $old) or (string)$old[$k] !== (string)$v) $c[$k] = "`$k` = '$v'";
    }
    return $c;
  }

  // The action takes its checkbox defaults from this helper where it exists.
  $src = file_get_contents($root.'/web/includes/functions.php');
  if (preg_match('/^function monitorFormUncheckedDefaults\(.*?^}\n/ms', $src, $m)) {
    eval($m[0]);
  } else {
    function monitorFormUncheckedDefaults() { return array(); }
  }

  // The action pulls its classes in with relative require_once calls.
  $dir = sys_get_temp_dir().'/zm_test_action_'.getmypid();
  mkdir($dir.'/includes', 0755, true);
  mkdir(sys_get_temp_dir().'/zm_test_storage', 0755, true);
  foreach (array('Monitor', 'Zone', 'Storage', 'Manufacturer', 'Model') as $f) {
    file_put_contents("$dir/includes/$f.php", "<?php\n");
  }
  chdir($dir);

  $failures = 0;
  $passes = 0;
  function check($name, $got, $want) {
    global $failures, $passes;
    if ($got === $want) {
      $passes++;
      echo "ok - $name\n";
    } else {
      $failures++;
      echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n";
    }
  }

  class FakeUser { public function unviewableMonitorIds() { return array(); } }

  function save($request, $db = array(), $zones = array()) {
    global $root, $error_message, $user, $action, $redirect;
    ZM\Rec::$saved = ZM\Rec::$inserted = ZM\Rec::$queries = array();
    ZM\Rec::$db = $db;
    ZM\Rec::$zones = $zones;
    $_REQUEST = $request;
    $error_message = '';
    $redirect = null;
    $user = new FakeUser();
    $action = 'save';
    ob_start();
    include $root.'/web/includes/actions/monitor.php';
    ob_end_clean();
    return $error_message;
  }

  $form = array(
    'ManufacturerId' => '', 'Manufacturer' => '', 'ModelId' => '', 'Model' => '',
    'Name' => 'Door', 'Type' => 'Ffmpeg', 'Width' => '640', 'Height' => '480',
  );
  $existing = array('Id' => 5, 'Name' => 'Garage', 'Type' => 'Ffmpeg', 'Deleted' => 0,
    'Width' => 640, 'Height' => 480, 'Capturing' => 'None', 'Controllable' => 0);

  // A new monitor must not hijack a live monitor's Id.
  $err = save(array('newMonitor' => $form + array('Id' => '5')),
    array(5 => $existing));
  check('a live monitor id is not overwritten', count(ZM\Rec::$saved), 0);
  check('a live monitor id is refused with an error',
    strpos($err, 'already exists') !== false, true);

  // Still allowed to reuse the Id of a deleted monitor.
  $err = save(array('newMonitor' => $form + array('Id' => '5')),
    array(5 => array('Deleted' => 1) + $existing));
  check('a deleted monitor id is reused', count(ZM\Rec::$saved), 1);
  check('reusing a deleted id reports no error', $err, '');

  // Editing an existing monitor still updates it.
  $err = save(array('mid' => '5', 'newMonitor' => $form + array('Name' => 'Renamed')),
    array(5 => $existing));
  check('editing an existing monitor still saves', count(ZM\Rec::$saved), 1);
  check('editing an existing monitor reports no error', $err, '');

  // A new monitor with no Id is inserted.
  save(array('newMonitor' => $form + array('Id' => '')), array());
  check('a new monitor with no id is inserted', count(ZM\Rec::$inserted), 1);

  system('rm -rf '.escapeshellarg($dir).' '.escapeshellarg(sys_get_temp_dir().'/zm_test_storage'));
  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
