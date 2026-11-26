<?php
// MonitorsController::daemonControl() used to answer status=ok whatever happened:
// the model logged the no-op cases and returned nothing, and it returned the last
// line zmdc.pl printed even when zmdc.pl exited non-zero. It also reported ok when
// the monitor's Capturing is None or when the monitor belongs to another server.
//
// This runs the real API model and controller with Cake and zmdc.pl stubbed, using
// a temporary ZM_PATH_BIN:
//   (a) zmdc.pl prints "Unable to connect to server using socket ..." and exits 255
//   (b) Capturing=None with start (the command must not run)
//   (c) zmdc.pl prints "'zmc -m 1' starting at ..." and exits 0
// It also checks stop is still allowed when Capturing is None, and that a monitor
// assigned to another server is reported as an error.
//
// Run as: php tests/php/test_api_daemon_control_status.php
// To run against the pre-fix files (e.g. `git show HEAD:<path>` copies):
//   php tests/php/test_api_daemon_control_status.php /tmp/zm-orig
// where /tmp/zm-orig holds Model/Monitor.php and Controller/MonitorsController.php
// (flat Monitor.php / MonitorsController.php names are accepted too). The test
// copies them into web/api/app layout so the controller's relative
// ../../../includes/Monitor.php finds the repository's web/includes.

$root = __DIR__.'/../..';
$model_file = $root.'/web/api/app/Model/Monitor.php';
$ctrl_file = $root.'/web/api/app/Controller/MonitorsController.php';
$orig_mirror = null;
if (isset($argv[1])) {
  $dir = rtrim($argv[1], '/');
  $model_file = is_file($dir.'/Model/Monitor.php') ? $dir.'/Model/Monitor.php' : $dir.'/Monitor.php';
  $ctrl_file = is_file($dir.'/Controller/MonitorsController.php') ? $dir.'/Controller/MonitorsController.php' : $dir.'/MonitorsController.php';
  if (!is_file($model_file) or !is_file($ctrl_file)) {
    fwrite(STDERR, "No Model/Monitor.php and Controller/MonitorsController.php under $dir\n");
    exit(2);
  }
  $orig_mirror = sys_get_temp_dir().'/zm_daemon_control_orig_'.getmypid();
  @mkdir($orig_mirror.'/web/api/app/Model', 0700, true);
  @mkdir($orig_mirror.'/web/api/app/Controller', 0700, true);
  copy($model_file, $orig_mirror.'/web/api/app/Model/Monitor.php');
  copy($ctrl_file, $orig_mirror.'/web/api/app/Controller/MonitorsController.php');
  // The controller requires ../../../includes/Monitor.php, which was already
  // loaded from here; the symlink makes require_once resolve to the same file.
  if (!file_exists($orig_mirror.'/web/includes')) {
    symlink(realpath($root.'/web/includes'), $orig_mirror.'/web/includes');
  }
  $model_file = $orig_mirror.'/web/api/app/Model/Monitor.php';
  $ctrl_file = $orig_mirror.'/web/api/app/Controller/MonitorsController.php';
}

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
function check_contains($name, $haystack, $needle) {
  global $failures, $passes;
  if (is_string($haystack) and strpos($haystack, $needle) !== false) {
    $passes++;
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "FAIL - $name (got ".var_export($haystack, true).", want a string containing ".var_export($needle, true).")\n";
  }
}

$bin = sys_get_temp_dir().'/zm_daemon_control_bin_'.getmypid();
@mkdir($bin, 0700, true);
define('ZM_PATH_BIN', $bin);
define('ZM_SERVER_ID', 1);

// Stubs for the includes that web/includes/Monitor.php loads. Same approach as
// test_monitor_model.php: the database, the related ZM classes and the log
// functions are stubbed; Object.php and Monitor.php are the real ones.
$stub_dir = sys_get_temp_dir().'/zm_daemon_control_stubs_'.getmypid();
@mkdir($stub_dir, 0700, true);
file_put_contents($stub_dir.'/database.php', <<<'STUB'
<?php
namespace {
function dbQuery($sql, $params = null) { return true; }
function dbLastError() { return ''; }
function dbError($sql) { return ''; }
function dbInsertId() { return 1; }
function dbFetchAll($sql, $col = null, $params = null) { return array(); }
function dbFetchOne($sql, $col = null, $params = null) { return null; }
function translate($s) { return $s; }
}
namespace ZM { function Debug($m) {} function Warning($m) {} function Error($m) {} function Info($m) {} }
STUB
);
foreach (array('Control', 'Group', 'Manufacturer', 'Model', 'Server', 'Storage', 'Zone') as $class) {
  file_put_contents($stub_dir."/$class.php", "<?php namespace ZM; class $class extends ZM_Object { protected static \$table='{$class}s'; protected \$defaults=array('Id'=>null,'Name'=>''); }");
}
// Monitor::canEdit() asks whether the id is editable; every monitor in the test is.
function editableMonitor($id) { return true; }
set_include_path($stub_dir.PATH_SEPARATOR.$root.'/web/includes'.PATH_SEPARATOR.get_include_path());
require_once($root.'/web/includes/Object.php');
require_once($root.'/web/includes/Monitor.php');

// Cake, the controller base and the model base are stubbed.
class App { public static function uses($class, $package) {} }
class AppModel {
  public $useTable;
  public $primaryKey = 'Id';
}
function __($s) { return $s; }
class BadRequestException extends Exception {}
class NotFoundException extends Exception {}
class UnauthorizedException extends Exception {}

$GLOBALS['monitor_row'] = array(
  'Id' => 1, 'Type' => 'Ffmpeg', 'Capturing' => 'Modect', 'Device' => '', 'ServerId' => 1,
);

require_once $model_file;

class StubMonitorModel extends Monitor {
  public function exists($id = null) { return true; }
  public function find($type = 'first', $options = array()) {
    return array('Monitor' => $GLOBALS['monitor_row']);
  }
}

class AppController {
  public $Monitor;
  public $request;
  public $viewVars = array();
  public function __construct() {
    $this->Monitor = new StubMonitorModel();
  }
  public function set($vars) { $this->viewVars = $vars; }
}

require_once $ctrl_file;

// A zmdc.pl that records that it ran, then does $body.
function stub_zmdc($body) {
  global $bin;
  $marker = escapeshellarg($bin.'/ran');
  file_put_contents($bin.'/zmdc.pl', "#!/bin/sh\necho ran > $marker\n$body\n");
  chmod($bin.'/zmdc.pl', 0755);
  @unlink($bin.'/ran');
}
function zmdc_ran() {
  global $bin;
  return file_exists($bin.'/ran');
}
function control($row, $command, $daemon = 'zmc') {
  $GLOBALS['monitor_row'] = $row;
  $controller = new MonitorsController();
  $controller->daemonControl($row['Id'], $command, $daemon);
  return $controller->viewVars;
}

$running = array('Id' => 1, 'Type' => 'Ffmpeg', 'Capturing' => 'Modect', 'Device' => '', 'ServerId' => 1);

// (a) zmdc.pl cannot reach the daemon server: its non-zero exit is a failure.
stub_zmdc(<<<'SH'
echo "Unable to connect to server using socket at /run/zm/zmdc.sock"
exit 255
SH
);
$r = control($running, 'start');
check('failed zmdc.pl run is an error', $r['status'], 'error');
check_contains('zmdc.pl error is passed on', $r['statustext'], 'Unable to connect to server using socket at /run/zm/zmdc.sock');
check('failed run really invoked zmdc.pl', zmdc_ran(), true);

// (b) Capturing=None means the monitor has no daemon to start.
stub_zmdc('echo "should not run"');
$r = control(array('Id' => 1, 'Type' => 'Ffmpeg', 'Capturing' => 'None', 'Device' => '', 'ServerId' => 1), 'start');
check('start with Capturing None is an error', $r['status'], 'error');
check('Capturing None is explained', $r['statustext'], 'Monitor capturing is set to None');
check('start with Capturing None does not run zmdc.pl', zmdc_ran(), false);

// A monitor on another server is not this server's to start or stop.
$r = control(array('Id' => 1, 'Type' => 'Ffmpeg', 'Capturing' => 'Modect', 'Device' => '', 'ServerId' => 2), 'start');
check('monitor of another server is an error', $r['status'], 'error');
check('other server is explained', $r['statustext'], 'Monitor is assigned to a different server');
check('other server does not run zmdc.pl', zmdc_ran(), false);

// (c) A successful start keeps zmdc.pl's output and answers ok.
stub_zmdc(<<<'SH'
echo "'zmc -m 1' starting at 26/11/16 10:00:00, pid = 100"
exit 0
SH
);
$r = control($running, 'start');
check('successful start is ok', $r['status'], 'ok');
check_contains('successful start keeps the daemon output', $r['statustext'], "'zmc -m 1' starting at");

// stop is allowed when Capturing is None, which is how a stale daemon is stopped.
$r = control(array('Id' => 1, 'Type' => 'Ffmpeg', 'Capturing' => 'None', 'Device' => '', 'ServerId' => 1), 'stop');
check('stop with Capturing None is ok', $r['status'], 'ok');
check('stop with Capturing None ran zmdc.pl', zmdc_ran(), true);

array_map('unlink', glob($stub_dir.'/*'));
@rmdir($stub_dir);
array_map('unlink', glob($bin.'/*'));
@rmdir($bin);
if ($orig_mirror) {
  @unlink($orig_mirror.'/web/api/app/Model/Monitor.php');
  @unlink($orig_mirror.'/web/api/app/Controller/MonitorsController.php');
  @rmdir($orig_mirror.'/web/api/app/Model');
  @rmdir($orig_mirror.'/web/api/app/Controller');
  @rmdir($orig_mirror.'/web/api/app');
  @unlink($orig_mirror.'/web/includes');
  @rmdir($orig_mirror.'/web');
  @rmdir($orig_mirror);
}

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
