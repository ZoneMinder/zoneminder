<?php
// Checks that the monitor save action reports failed follow-up writes (group
// assignment, zone updates on resize) in $error_message.
//
// php tests/php/test_monitor_save_followup_writes.php <repo root>

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
    public static $all = array();
    public static $failSql = array();
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
  $root = isset($argv[1]) ? rtrim($argv[1], '/') : dirname(__DIR__, 2);
  if (!$root or !is_file("$root/web/includes/actions/monitor.php")) {
    fwrite(STDERR, "usage: php test_monitor_save_followup_writes.php [repo root]\n");
    exit(2);
  }

  define('ZM_OPT_X10', false);
  define('ZM_OPT_CONTROL', false);

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
  function dbQuery($sql, $params = null) {
    ZM\Rec::$all[] = array($sql, $params);
    if (strpos($sql, 'UPDATE Zones') === 0) ZM\Rec::$queries[] = array($sql, $params);
    foreach (ZM\Rec::$failSql as $p) { if (strpos($sql, $p) === 0) return null; }
    return true;
  }
  function dbLastError() { return 'boom'; }
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
  $src = file_get_contents("$root/web/includes/functions.php");
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

  function save($request, $db = array(), $zones = array(), $fail = array()) {
    global $root, $error_message, $user, $action, $redirect;
    ZM\Rec::$saved = ZM\Rec::$inserted = ZM\Rec::$queries = array();
    ZM\Rec::$all = array();
    ZM\Rec::$failSql = $fail;
    ZM\Rec::$db = $db;
    ZM\Rec::$zones = $zones;
    $_REQUEST = $request;
    $error_message = '';
    $redirect = null;
    $user = new FakeUser();
    $action = 'save';
    ob_start();
    include "$root/web/includes/actions/monitor.php";
    ob_end_clean();
    return $error_message;
  }

  function sqls() {
    return array_map(function($q) { return $q[0]; }, ZM\Rec::$all);
  }
  function countSql($prefix) {
    $n = 0;
    foreach (sqls() as $s) { if (strpos($s, $prefix) === 0) $n++; }
    return $n;
  }

  $form = array(
    'ManufacturerId' => '', 'Manufacturer' => '', 'ModelId' => '', 'Model' => '',
    'Name' => 'Door', 'Type' => 'Ffmpeg', 'Width' => '640', 'Height' => '480',
  );
  $existing = array('Id' => 5, 'Name' => 'Garage', 'Type' => 'Ffmpeg', 'Deleted' => 0,
    'Width' => 640, 'Height' => 480, 'Capturing' => 'None', 'Controllable' => 0);

  // Saving still works
  $err = save(array('newMonitor' => $form + array('Id' => '5')),
    array(5 => array('Deleted' => 1) + $existing));
  check('a deleted monitor id is reused', count(ZM\Rec::$saved), 1);
  check('reusing a deleted id reports no error', $err, '');

  save(array('newMonitor' => $form + array('Id' => '')), array());
  check('a new monitor with no id is inserted', count(ZM\Rec::$inserted), 1);

  // Group assignment follow-up writes
  // Not covered: X10 and MonitorActions sites (ZM_OPT_X10 is a compile-time
  // constant here).
  $edit = array('mid' => '5', 'newMonitor' => $form + array('Id' => '5', 'GroupIds' => array('3', '4')));

  // Failing the DELETE reports an error and adds no memberships
  $err = save($edit, array(5 => $existing), array(), array('DELETE FROM Groups_Monitors'));
  check('a failed group clear is reported', strpos($err, 'Error clearing groups') !== false, true);
  check('a failed group clear adds no memberships', countSql('INSERT INTO Groups_Monitors'), 0);
  global $redirect;
  check('a failed group clear does not redirect', $redirect, null);

  // Failing the INSERT reports an error
  $err = save($edit, array(5 => $existing), array(), array('INSERT INTO Groups_Monitors'));
  check('a failed group insert is reported', strpos($err, 'Error adding monitor to group') !== false, true);
  check('both group inserts were attempted', countSql('INSERT INTO Groups_Monitors'), 2);
  check('a failed group insert does not redirect', $redirect, null);

  // No failure: no error and a redirect
  $err = save($edit, array(5 => $existing));
  check('a successful group assignment has no error', $err, '');
  check('a successful group assignment redirects', $redirect, '?view=console');
  check('both group inserts ran', countSql('INSERT INTO Groups_Monitors'), 2);
  check('groups are cleared before being added',
    countSql('DELETE FROM Groups_Monitors'), 1);

  // Zone updates on a resize
  $legacy = array('Id' => 2, 'Name' => 'Old', 'Units' => 'Pixels',
    // As the zone editor stores them: every coordinate gets two decimals
    'Coords' => '0.00,0.00 639.00,0.00 639.00,479.00 0.00,479.00',
    'MinAlarmPixels' => 100, 'MaxAlarmPixels' => 1000, 'MinFilterPixels' => 0,
    'MaxFilterPixels' => 0, 'MinBlobPixels' => 0, 'MaxBlobPixels' => 0);
  $big = array('Width' => '1280', 'Height' => '960');

  $err = save(array('mid' => '5', 'newMonitor' => $big + $form), array(5 => $existing),
    array($legacy), array('UPDATE Zones'));
  check('a failed zone update is reported on resize', strpos($err, 'Error updating zone') !== false, true);
  check('a failed zone update does not redirect', $redirect, null);

  $err = save(array('mid' => '5', 'newMonitor' => $big + $form), array(5 => $existing),
    array($legacy));
  check('a successful zone update has no error', $err, '');
  check('a successful zone update redirects', $redirect, '?view=console');

  system('rm -rf '.escapeshellarg($dir).' '.escapeshellarg(sys_get_temp_dir().'/zm_test_storage'));
  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
