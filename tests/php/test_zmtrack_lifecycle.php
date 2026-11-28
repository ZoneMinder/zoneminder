<?php
// zmtrack.pl lifecycle from the web interface:
//
// zmpkg.pl starts zmtrack.pl only on a full ZoneMinder start, and only for a
// monitor that is capturing, controllable, has TrackMotion set and is
// analysing. Saving or deleting a monitor while ZM runs has to start or stop
// it, otherwise changing TrackMotion in the UI leaves the old daemon running
// (or the new one missing) until the next ZM restart.
//
// The real Monitor.php and web/includes/actions/monitor.php are used, with the
// database, the related models and daemonControl() stubbed. Every
// daemonControl() call is recorded.
//
// php tests/php/test_zmtrack_lifecycle.php
// php tests/php/test_zmtrack_lifecycle.php /path/to/originals
//
// The optional argument is a directory holding copies of the files as they
// were before the fix (git show HEAD:web/includes/Monitor.php etc.), so that
// the same test can be shown failing against the originals.

namespace ZM {
  class Rec {
    public static $db = array();
    public static $daemons = array();
    public static $queries = array();
    public static $zmtrackRunning = false;
  }
}

namespace {
  $root = __DIR__.'/../..';
  $orig = isset($argv[1]) ? rtrim($argv[1], '/') : null;
  $monitor_file = ($orig and file_exists($orig.'/Monitor.php')) ?
    $orig.'/Monitor.php' : $root.'/web/includes/Monitor.php';
  $action_file = ($orig and file_exists($orig.'/monitor.php')) ?
    $orig.'/monitor.php' : $root.'/web/includes/actions/monitor.php';

  $failures = 0;
  $passes = 0;
  function check($label, $got, $want) {
    global $failures, $passes;
    if ($got === $want) {
      $passes++;
      echo "ok - $label\n";
    } else {
      $failures++;
      echo "FAIL - $label (got ".var_export($got, true).", want ".var_export($want, true).")\n";
    }
  }
  function called($command, $daemon = 'zmtrack.pl', $args = '-m 5') {
    return in_array(array($command, $daemon, $args), ZM\Rec::$daemons, true);
  }
  function resetCalls($zmtrack_running = false) {
    ZM\Rec::$daemons = array();
    ZM\Rec::$zmtrackRunning = $zmtrack_running;
  }

  // The monitor under test, as the database would return it.
  function row($overrides = array()) {
    return $overrides + array(
      'Id' => 5, 'Name' => 'Garage', 'Notes' => 'old', 'Type' => 'Ffmpeg',
      'Device' => '', 'Host' => '', 'Port' => '', 'Path' => '',
      'Capturing' => 'Always', 'Analysing' => 'Always', 'Recording' => 'Always',
      'Controllable' => 1, 'TrackMotion' => 1, 'Deleted' => 0,
      'Width' => 640, 'Height' => 480, 'StorageId' => 1, 'ServerId' => 0,
    );
  }

  // --- stubs the real classes load through include_path -------------------
  $stub_dir = sys_get_temp_dir().'/zm_zmtrack_stubs_'.getmypid();
  @mkdir($stub_dir);
  $storage_dir = sys_get_temp_dir().'/zm_zmtrack_storage_'.getmypid();
  @mkdir($storage_dir);

  file_put_contents($stub_dir.'/database.php', <<<'STUB'
<?php
namespace {
function dbFetchOne($sql, $col = null, $args = null) {
  if (stripos($sql, 'MAX(Sequence)') !== false) return 7;
  if (isset($args[0]) and stripos($sql, 'FROM `Monitors`') !== false
      and isset(\ZM\Rec::$db[$args[0]])) {
    return \ZM\Rec::$db[$args[0]];
  }
  return null;
}
function dbFetchAll($sql, $col = null, $args = null) { return array(); }
function dbQuery($sql, $args = null) { \ZM\Rec::$queries[] = $sql; return true; }
function dbInsertId() { return 1; }
function dbLastError() { return ''; }
function dbError($sql = '') { return ''; }
function translate($s) { return $s; }
}
namespace ZM {
function Debug($m) {}
// Copied from web/includes/functions.php; Object.php changes() calls it.
function scalar_values_differ($old, $new) {
  if (is_array($old) or is_array($new)) {
    return $old != $new;
  }
  if (is_bool($old)) $old = (int)$old;
  if (is_bool($new)) $new = (int)$new;
  return (string)($old === null ? '' : $old) !== (string)($new === null ? '' : $new);
}
function Warning($m) {}
function Error($m) {}
function Info($m) {}
function AuditAction() {}
}
STUB
  );

  file_put_contents($stub_dir.'/CronTab.php', "<?php\nfunction cronRebuild() { return true; }\n");

  foreach (array('Control', 'Group', 'Manufacturer', 'Model') as $c) {
    file_put_contents($stub_dir."/$c.php",
      "<?php\nnamespace ZM;\nclass $c extends ZM_Object {\n".
      "  protected static \$table = '{$c}s';\n".
      "  protected \$defaults = array('Id'=>null, 'Name'=>'');\n".
      "  public static function find(\$p = array(), \$o = array()) { return array(); }\n".
      "  public static function find_one(\$p = array(), \$o = array()) { return null; }\n".
      "}\n");
  }

  file_put_contents($stub_dir.'/Server.php', <<<'STUB'
<?php
namespace ZM;
class Server extends ZM_Object {
  protected static $table = 'Servers';
  protected $defaults = array('Id'=>null, 'Name'=>'', 'Url'=>'');
  public static function find_one($p = array(), $o = array()) { return new Server(null); }
  public function SendToApi($path) { return true; }
}
STUB
  );

  file_put_contents($stub_dir.'/Storage.php', <<<'STUB'
<?php
namespace ZM;
class Storage extends ZM_Object {
  protected static $table = 'Storage';
  protected $defaults = array('Id'=>null, 'Name'=>'Default', 'Path'=>null);
  public static function find($p = array(), $o = array()) { return array(); }
  public static function find_one($p = array(), $o = array()) {
    $s = new Storage(null);
    if (isset($p['Id'])) $s->Id = $p['Id'];
    return $s;
  }
  public function Path($new = null) {
    if ($new !== null) $this->{'Path'} = $new;
    if (property_exists($this, 'Path') and $this->{'Path'}) return $this->{'Path'};
    return getenv('ZM_TEST_STORAGE');
  }
}
STUB
  );

  file_put_contents($stub_dir.'/Zone.php', <<<'STUB'
<?php
namespace ZM;
class Zone extends ZM_Object {
  protected static $table = 'Zones';
  protected $defaults = array('Id'=>null, 'Name'=>'', 'MonitorId'=>null);
  public function save($data = null) { return true; }
  public function get_last_error() { return ''; }
}
STUB
  );

  putenv('ZM_TEST_STORAGE='.$storage_dir);
  set_include_path($stub_dir.PATH_SEPARATOR.$root.'/web/includes'.PATH_SEPARATOR.get_include_path());

  // Global helpers the action file expects.
  define('ZM_OPT_X10', false);
  define('ZM_OPT_CONTROL', true);
  define('ZM_DIR_EVENTS', sys_get_temp_dir().'/zm_zmtrack_events_'.getmypid());
  function canEdit($p, $id = null) { return true; }
  function canView($p, $id = null) { return true; }
  function canCreate($p) { return true; }
  function validInt($v) { return preg_replace('/[^\-\d]/', '', $v); }
  function validCardinal($v) { return preg_replace('/\D/', '', $v); }
  function validHtmlStr($v) { return $v; }
  function array_recursive_diff($a, $b) { return ($a == $b) ? array() : array('diff'); }
  function coordsToPoints($c) { return array(); }
  function pointsToCoords($p) { return ''; }
  function getFormChanges($old, $new, $types = false) { return array(); }
  function deletePath($path) { return true; }

  // Record daemon starts/stops instead of running zmdc.pl.
  function daemonControl($command, $daemon = false, $args = false) {
    ZM\Rec::$daemons[] = array($command, $daemon, $args);
    return 'stub';
  }
  function daemonStatus($daemon, $args = array()) {
    if ($daemon === 'zmtrack.pl') return ZM\Rec::$zmtrackRunning;
    return false;
  }

  class FakeUser {
    public function canEdit($p) { return true; }
    public function unviewableMonitorIds() { return array(); }
  }

  require_once($root.'/web/includes/Object.php');
  require_once($monitor_file);

  // The action pulls its classes in with relative require_once calls; point
  // those at empty files, the real classes are already loaded.
  $action_dir = sys_get_temp_dir().'/zm_zmtrack_action_'.getmypid();
  @mkdir($action_dir.'/includes', 0755, true);
  foreach (array('Monitor', 'Zone', 'Storage', 'Manufacturer', 'Model') as $f) {
    file_put_contents("$action_dir/includes/$f.php", "<?php\n");
  }

  // monitorFormUncheckedDefaults() decides which checkboxes the form posts.
  $src = file_get_contents($root.'/web/includes/functions.php');
  if (preg_match('/^function monitorFormUncheckedDefaults\(.*?^}\n/ms', $src, $m)) {
    eval($m[0]);
  } else {
    function monitorFormUncheckedDefaults() { return array(); }
  }

  function saveMonitor($request, $db_row, $zmtrack_running = false) {
    global $error_message, $user, $action, $redirect, $action_dir, $action_file;
    ZM\Rec::$db = array(5 => $db_row);
    resetCalls($zmtrack_running);
    $_REQUEST = $request;
    $error_message = '';
    $redirect = null;
    $user = new FakeUser();
    $action = 'save';
    $cwd = getcwd();
    chdir($action_dir);
    ob_start();
    include $action_file;
    ob_end_clean();
    chdir($cwd);
    return $error_message;
  }

  $form = array(
    'ManufacturerId' => '', 'Manufacturer' => '', 'ModelId' => '', 'Model' => '',
    'Name' => 'Garage', 'Type' => 'Ffmpeg', 'Width' => '640', 'Height' => '480',
    'Capturing' => 'Always', 'Analysing' => 'Always', 'Controllable' => 1,
    'Notes' => 'new',
  );

  // A controllable, analysing monitor with TrackMotion on starts zmtrack.pl.
  $err = saveMonitor(array('mid' => '5',
    'newMonitor' => $form + array('TrackMotion' => 1)), row());
  check('saving a tracking monitor reports no error', $err, '');
  check('save starts zmtrack.pl -m 5', called('start'), true);

  // Clearing TrackMotion stops it when it is running, because it no longer
  // meets zmpkg's rules.
  $err = saveMonitor(array('mid' => '5', 'newMonitor' => $form), row(), true);
  check('clearing TrackMotion reports no error', $err, '');
  check('save stops zmtrack.pl when TrackMotion is off', called('stop'), true);

  // But when it was never started (the usual case) there is nothing to stop,
  // and asking zmdc.pl anyway just logs "Can't find process".
  $err = saveMonitor(array('mid' => '5', 'newMonitor' => $form), row(), false);
  check('clearing TrackMotion reports no error with no daemon', $err, '');
  check('save does not stop zmtrack.pl when it is not running', called('stop'), false);

  // Analysing None is the other zmpkg condition that excludes tracking.
  resetCalls(false);
  $m = new ZM\Monitor(row(array('Analysing' => 'None')));
  if (method_exists($m, 'zmtrackControl')) $m->zmtrackControl('start');
  check('nothing is stopped when Analysing is None and zmtrack is not running',
    called('stop'), false);

  resetCalls(true);
  $m = new ZM\Monitor(row(array('Analysing' => 'None')));
  if (method_exists($m, 'zmtrackControl')) $m->zmtrackControl('start');
  check('zmtrack is stopped when Analysing becomes None', called('stop'), true);

  // So is a monitor that is not controllable.
  resetCalls(true);
  $m = new ZM\Monitor(row(array('Controllable' => 0)));
  if (method_exists($m, 'zmtrackControl')) $m->zmtrackControl('start');
  check('zmtrack is stopped for a non-controllable monitor', called('stop'), true);

  // Deleting a monitor stops tracking only if it is running.
  resetCalls(false);
  ZM\Rec::$db = array(5 => row());
  $m = new ZM\Monitor(row());
  $m->delete();
  check('delete does not stop a zmtrack that is not running', called('stop'), false);

  resetCalls(true);
  ZM\Rec::$db = array(5 => row());
  $m = new ZM\Monitor(row());
  $m->delete();
  check('delete stops zmtrack.pl -m 5', called('stop'), true);

  // destroy() stops it too, before the row is removed.
  resetCalls(false);
  ZM\Rec::$db = array(5 => row());
  $m = new ZM\Monitor(row());
  $m->destroy();
  check('destroy does not stop a zmtrack that is not running', called('stop'), false);

  resetCalls(true);
  ZM\Rec::$db = array(5 => row());
  $m = new ZM\Monitor(row());
  $m->destroy();
  check('destroy stops zmtrack.pl -m 5', called('stop'), true);

  system('rm -rf '.escapeshellarg($action_dir).' '.escapeshellarg($stub_dir).
    ' '.escapeshellarg($storage_dir));

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
