<?php
// Runs the real monitor save action against stubbed Monitor, MonitorAction,
// Zone, Storage and database layers.
//
// A posted action row names a TargetMonitorId. Storing a row for a monitor the
// user may not Control lets that monitor's alarms drive a light or speaker the
// user has no rights over, so the save action must drop the row and report the
// target. A row for a permitted target is still stored.
//
// php tests/php/test_monitor_action_target_permission.php [root]
//   (root defaults to the repo, pass an unfixed checkout to see the failure)

namespace ZM {
  function Warning($s) {}
  function Debug($s) {}
  function Error($s) {}
  function AuditAction() {}
  function redactForLog($a) { return $a; }

  class Rec {
    public static $storage = '';
    public static $saved = array();
    public static $inserted = array();
    public static $queries = array();
    public static $zones = array();
    public static $db = array();
    public static $action_inserted = array();
  }

  class Storage {
    public static function find() { return array(); }
    public function Path() { return Rec::$storage; }
    public function Name() { return 'Default'; }
  }

  class Monitor {
    private $f = array();
    public static function find_one($a) { return null; }
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

  class MonitorAction {
    private $id = 0;
    public static function find_one($a) { return null; }
    public static function typesForMonitor($m) { return array('LightOn'); }
    public function insert($values) {
      Rec::$action_inserted[] = $values;
      $this->id = count(Rec::$action_inserted);
      return true;
    }
    public function save($values) { return true; }
    public function Id() { return $this->id; }
    public function changes($values) { return array(); }
    public function get_last_error() { return ''; }
  }
}

namespace {
  define('ZM_OPT_X10', false);
  define('ZM_OPT_CONTROL', false);

  // Monitor 2 is the forbidden Control target; monitors 1 and 5 are fine.
  function canEdit($p, $id = null) { return true; }
  function canView($p, $id = null) {
    if ($p == 'Control' and $id == 2) return false;
    return true;
  }
  function canCreate($p) { return true; }
  function validInt($v) { return preg_replace('/[^\-\d]/', '', $v); }
  function validCardinal($v) { return preg_replace('/\D/', '', $v); }
  function validDevicePath($v) { return $v; }
  function validHtmlStr($v) { return $v; }
  function cronRebuildScheduledActions() { return true; }
  function cronScheduleIsValid($s) { return true; }
  function cronFromParts($f, $h, $m) { return '0 0 * * *'; }
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

  $root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';

  // The action takes its checkbox defaults from this helper where it exists.
  $src = file_get_contents($root.'/web/includes/functions.php');
  if (preg_match('/^function monitorFormUncheckedDefaults\(.*?^}\n/ms', $src, $m)) {
    eval($m[0]);
  } else {
    function monitorFormUncheckedDefaults() { return array(); }
  }

  // The action pulls its classes in with relative require_once calls. Stub
  // files ahead on include_path satisfy those without the real classes.
  $stub = sys_get_temp_dir().'/zm_act_perm_stub_'.getmypid();
  mkdir($stub.'/includes', 0755, true);
  foreach (array('Monitor', 'Zone', 'Storage', 'Manufacturer', 'Model',
                 'MonitorAction', 'CronTab') as $f) {
    file_put_contents("$stub/includes/$f.php", "<?php\n");
  }
  set_include_path($stub.PATH_SEPARATOR.$root.'/web'.PATH_SEPARATOR.get_include_path());

  ZM\Rec::$storage = sys_get_temp_dir().'/zm_act_perm_storage_'.getmypid();
  mkdir(ZM\Rec::$storage, 0755, true);

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
    ZM\Rec::$action_inserted = array();
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

  $err = save(array(
    'mid' => '5',
    'newMonitor' => $form,
    'newAction' => array(
      array('TargetMonitorId' => 1, 'ActionType' => 'LightOn', 'TriggerOn' => 'EventStart', 'Enabled' => 1),
      array('TargetMonitorId' => 2, 'ActionType' => 'LightOn', 'TriggerOn' => 'EventStart', 'Enabled' => 1),
    ),
  ), array(5 => $existing));

  check('only the permitted target is inserted', count(ZM\Rec::$action_inserted), 1);
  check('the stored action targets monitor 1',
    ZM\Rec::$action_inserted ? (int)ZM\Rec::$action_inserted[0]['TargetMonitorId'] : null, 1);
  check('the forbidden target is reported', strpos($err, 'monitor 2') !== false, true);

  exec('rm -rf '.escapeshellarg($stub).' '.escapeshellarg(ZM\Rec::$storage));
  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
