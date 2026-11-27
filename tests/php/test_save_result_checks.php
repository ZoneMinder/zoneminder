<?php
// Runs the real web/includes/actions/{snapshot,zone,state}.php save paths with
// stubbed model and database layers:
//
// With strict SQL mode a Name longer than varchar(64) makes the write fail.
// These actions used to ignore the result, so they audited, told zmc to
// reload and redirected to the success page as if the row had been saved.
// A failed save must fill $error_message and must not audit, reload or
// redirect.
//
// Run as: php tests/php/test_save_result_checks.php [actions-dir]
// Pass a directory holding copies of the action files to run those instead,
// e.g. ones made with `git show HEAD:web/includes/actions/zone.php`.

namespace ZM {
  function Warning($s) {}
  function Error($s) {}
  function Debug($s) {}
  function AuditAction($action, $object, $id, $message) {
    Rec::$audits[] = "$action $object $id";
  }

  class Rec {
    public static $audits = array();
    public static $reloads = array();
    public static $queries = array();
    public static $saves = array();
    public static $fail = false;
    public static $db_error = 'Data too long for column \'Name\' at row 1';
  }

  // actions/zone.php reads the built-in preset list on every request.
  class Zone { const BUILT_IN_PRESETS = array(); }

  class Snapshot {
    private $f = array('Id' => 5, 'CreatedBy' => 2, 'Name' => 'Original');
    public function __construct($id = null) {}
    public function Id() { return $this->f['Id']; }
    public function CreatedBy() { return $this->f['CreatedBy']; }
    public function changes($new) {
      $changes = array();
      foreach ($new as $k => $v) {
        if (!array_key_exists($k, $this->f) or (string)$this->f[$k] !== (string)$v) $changes[$k] = $v;
      }
      return $changes;
    }
    public function save($changes) {
      Rec::$saves[] = $changes;
      if (Rec::$fail) return false;
      foreach ($changes as $k => $v) $this->f[$k] = $v;
      return true;
    }
    public function get_last_error() { return Rec::$db_error; }
  }

  class Monitor {
    public function __construct($id = null) {}
    public function Type() { return 'Ffmpeg'; }
    public function Controllable() { return false; }
    public function ViewWidth() { return 640; }
    public function ViewHeight() { return 480; }
    public function zmcControl($command) { Rec::$reloads[] = $command; }
    public function sendControlCommand($command) {}
  }
}

namespace {
  $actionsDir = isset($argv[1]) ? rtrim($argv[1], '/') : __DIR__.'/../../web/includes/actions';
  foreach (array('snapshot', 'zone', 'state') as $name) {
    if (!is_file("$actionsDir/$name.php")) {
      fwrite(STDERR, "No $name.php in $actionsDir\n");
      exit(2);
    }
  }

  $stubDir = sys_get_temp_dir().'/zm_save_result_stubs_'.getmypid();
  @mkdir($stubDir.'/includes', 0700, true);
  foreach (array('Snapshot', 'Monitor', 'Zone', 'ZonePreset') as $f) {
    file_put_contents($stubDir."/includes/$f.php", "<?php\n");
  }
  set_include_path($stubDir.PATH_SEPARATOR.get_include_path());

  class StubUser { public function Id() { return 2; } }

  function canEdit($area, $id = null) { return true; }
  function canView($area, $id = null) { return true; }
  function validInt($v) { return (int)$v; }
  function validCardinal($v) { return (int)$v; }
  function validHtmlStr($v) { return htmlspecialchars((string)$v); }
  function daemonCheck() { return true; }
  function localRedirectUrl($referer, $default) { return $referer ? $referer : $default; }
  function coordsToPoints($coords) { return array(); }
  function pointsToCoords($points) { return ''; }
  function getPolyArea($points) { return 10000; }
  function getFormChanges($old, $new, $types = array()) {
    $changes = array();
    foreach ($new as $k => $v) {
      if (!array_key_exists($k, $old) or (string)$old[$k] !== (string)$v) {
        $changes[] = "`$k` = '".addslashes($v)."'";
      }
    }
    return $changes;
  }
  function dbFetchOne($sql, $col = null, $params = null) {
    return array('Id' => 7, 'MonitorId' => 5, 'Name' => 'Front', 'Type' => 'Active');
  }
  function dbFetchAll($sql, $col = null, $params = null) {
    return array(array('Id' => 1, 'Capturing' => 'Always', 'Analysing' => 'Always', 'Recording' => 'OnMotion'));
  }
  function dbQuery($sql, $params = null, $debug = false) {
    ZM\Rec::$queries[] = array($sql, $params);
    return ZM\Rec::$fail ? null : true;
  }
  function dbLastError() { return ZM\Rec::$db_error; }
  function packageControl($state) {}
  function getHomeView() { return 'console'; }

  $failures = 0;
  $passes = 0;
  function check($name, $got, $want) {
    global $failures, $passes;
    if ($got === $want) {
      $passes++;
      echo "ok - $name\n";
    } else {
      $failures++;
      echo "FAIL - $name (got ".var_export($got, true).', want '.var_export($want, true).")\n";
    }
  }

  // Includes an action file the way index.php does (at global scope), with
  // fresh request/records. Returns the message and redirect it left behind.
  function runAction($file, $actionName, $request, $fail) {
    global $error_message, $redirect, $user, $action;

    ZM\Rec::$audits = array();
    ZM\Rec::$reloads = array();
    ZM\Rec::$queries = array();
    ZM\Rec::$saves = array();
    ZM\Rec::$fail = $fail;
    $_REQUEST = $request;
    $error_message = '';
    $redirect = null;
    $user = new StubUser();
    $action = $actionName;

    ob_start();
    include $file;
    ob_end_clean();

    return array($error_message, $redirect);
  }

  $snapshotRequest = array('id' => '5', 'snapshot' => array('Name' => str_repeat('x', 65)));
  $zoneRequest = array(
    'mid' => '5',
    'zid' => '7',
    'newZone' => array('Name' => str_repeat('x', 65), 'Type' => 'Active'),
  );
  $stateRequest = array('runState' => str_repeat('x', 65));

  echo "snapshot save\n";
  list($error, $redirect) = runAction("$actionsDir/snapshot.php", 'save', $snapshotRequest, true);
  check('failed save reports the error', $error !== '', true);
  check('failed save does not audit', ZM\Rec::$audits, array());
  check('failed save does not redirect', $redirect, null);

  list($error, $redirect) = runAction("$actionsDir/snapshot.php", 'save', $snapshotRequest, false);
  check('successful save reports no error', $error, '');
  check('successful save audits', ZM\Rec::$audits, array('update snapshot 5'));
  check('successful save redirects', $redirect, '?view=snapshots');

  echo "zone save\n";
  list($error, $redirect) = runAction("$actionsDir/zone.php", 'zone', $zoneRequest, true);
  check('failed query reports the error', $error !== '', true);
  check('failed query does not audit', ZM\Rec::$audits, array());
  check('failed query does not reload zmc', ZM\Rec::$reloads, array());
  check('failed query does not redirect', $redirect, null);

  list($error, $redirect) = runAction("$actionsDir/zone.php", 'zone', $zoneRequest, false);
  check('successful query reports no error', $error, '');
  check('successful query audits', ZM\Rec::$audits, array('update zone 7'));
  check('successful query reloads zmc', ZM\Rec::$reloads, array('reload'));
  check('successful query redirects', $redirect, '?view=zones&mid=5');

  echo "state save\n";
  list($error, $redirect) = runAction("$actionsDir/state.php", 'save', $stateRequest, true);
  check('failed query reports the error', $error !== '', true);
  check('failed query does not audit', ZM\Rec::$audits, array());
  check('failed query does not redirect', $redirect, null);

  list($error, $redirect) = runAction("$actionsDir/state.php", 'save', $stateRequest, false);
  check('successful query reports no error', $error, '');
  check('successful query audits', ZM\Rec::$audits, array('save state 0'));
  check('successful query redirects', $redirect, '?view=console');

  foreach (array('Snapshot', 'Monitor', 'Zone', 'ZonePreset') as $f) {
    @unlink($stubDir."/includes/$f.php");
  }
  @rmdir($stubDir.'/includes');
  @rmdir($stubDir);

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
