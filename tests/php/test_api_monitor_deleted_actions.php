<?php
// alarm(), daemonStatus() and daemonControl() used to run zmu/zmdc.pl for a monitor
// whose row is marked Deleted. The row still exists (exists() is true), but the
// monitor is gone as far as the console is concerned, so these actions now answer
// 404 like delete() does for an already-deleted monitor. A live monitor must still
// run the commands.
//
// The real controller and API model run with Cake, the database and the ZM classes
// stubbed. zmu and zmdc.pl are scripts in a temporary ZM_PATH_BIN that record when
// they run, so the test checks behaviour (exception thrown / command not run), not
// source text.
//
// Run as: php tests/php/test_api_monitor_deleted_actions.php
// To run against another controller (e.g. a `git show HEAD:<path>` copy):
//   php tests/php/test_api_monitor_deleted_actions.php /tmp/zm-orig
// /tmp/zm-orig holds Controller/MonitorsController.php (or a flat
// MonitorsController.php); Model/Monitor.php is used when present, otherwise the
// repository's copy. They are copied into web/api/app layout so the controller's
// relative ../../../includes/Monitor.php finds the repository's web/includes.

$root = __DIR__.'/../..';
$model_file = $root.'/web/api/app/Model/Monitor.php';
$ctrl_file = $root.'/web/api/app/Controller/MonitorsController.php';
$orig_mirror = null;
if (isset($argv[1])) {
  $dir = rtrim($argv[1], '/');
  $given_ctrl = is_file($dir.'/Controller/MonitorsController.php') ? $dir.'/Controller/MonitorsController.php' : $dir.'/MonitorsController.php';
  if (!is_file($given_ctrl)) {
    fwrite(STDERR, "No Controller/MonitorsController.php under $dir\n");
    exit(2);
  }
  $given_model = is_file($dir.'/Model/Monitor.php') ? $dir.'/Model/Monitor.php' : $dir.'/Monitor.php';
  if (!is_file($given_model)) $given_model = $model_file;

  $orig_mirror = sys_get_temp_dir().'/zm_deleted_actions_orig_'.getmypid();
  @mkdir($orig_mirror.'/web/api/app/Model', 0700, true);
  @mkdir($orig_mirror.'/web/api/app/Controller', 0700, true);
  copy($given_model, $orig_mirror.'/web/api/app/Model/Monitor.php');
  copy($given_ctrl, $orig_mirror.'/web/api/app/Controller/MonitorsController.php');
  // The controller requires ../../../includes/Monitor.php. That file is loaded
  // from the repository before the controller; the symlink keeps require_once
  // resolving to the same file.
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

$bin = sys_get_temp_dir().'/zm_deleted_actions_bin_'.getmypid();
@mkdir($bin, 0700, true);
define('ZM_PATH_BIN', $bin);
define('ZM_SERVER_ID', 1);

// Stubs for the includes that web/includes/Monitor.php loads, as in
// test_api_daemon_control_status.php. dbFetchOne answers with the current monitor
// row so that new ZM\Monitor($id) sees its Deleted flag.
$stub_dir = sys_get_temp_dir().'/zm_deleted_actions_stubs_'.getmypid();
@mkdir($stub_dir, 0700, true);
file_put_contents($stub_dir.'/database.php', <<<'STUB'
<?php
namespace {
function dbQuery($sql, $params = null) { return true; }
function dbLastError() { return ''; }
function dbError($sql) { return ''; }
function dbInsertId() { return 1; }
function dbFetchAll($sql, $col = null, $params = null) { return array(); }
function dbFetchOne($sql, $col = null, $params = null) { return $GLOBALS['monitor_row']; }
function translate($s) { return $s; }
}
namespace ZM { function Debug($m) {} function Warning($m) {} function Error($m) {} function Info($m) {} }
STUB
);
foreach (array('Control', 'Group', 'Manufacturer', 'Model', 'Server', 'Storage', 'Zone') as $class) {
  file_put_contents($stub_dir."/$class.php", "<?php namespace ZM; class $class extends ZM_Object { protected static \$table='{$class}s'; protected \$defaults=array('Id'=>null,'Name'=>''); }");
}
// canView()/canEdit() resolve these from the global namespace.
function editableMonitor($id) { return true; }
function visibleMonitor($id) { return true; }
set_include_path($stub_dir.PATH_SEPARATOR.$root.'/web/includes'.PATH_SEPARATOR.get_include_path());
require_once($root.'/web/includes/Object.php');
require_once($root.'/web/includes/Monitor.php');

class App { public static function uses($class, $package) {} }
class AppModel {
  public $useTable;
  public $primaryKey = 'Id';
}
function __($s) { return $s; }
function zmuCredentials($password = null) { return ''; }
class BadRequestException extends Exception {}
class NotFoundException extends Exception {}
class UnauthorizedException extends Exception {}
class Set {
  static function extract($path, $data) { return array($data['Monitor']); }
}

$GLOBALS['monitor_row'] = array(
  'Id' => 1, 'Type' => 'Ffmpeg', 'Capturing' => 'Modect', 'Device' => '', 'ServerId' => 1, 'Deleted' => 0,
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

class FakeRequest {
  public $params = array('named' => array());
  function __construct($named) {
    $this->params['named'] = $named;
  }
  function query($k) { return null; }
  function data($k) { return null; }
}

// A script in ZM_PATH_BIN that records that it ran, then does $body.
function stub_command($name, $body) {
  global $bin;
  $marker = escapeshellarg($bin.'/ran');
  file_put_contents($bin.'/'.$name, "#!/bin/sh\necho ran > $marker\n$body\n");
  chmod($bin.'/'.$name, 0755);
  @unlink($bin.'/ran');
}
function command_ran() {
  global $bin;
  return file_exists($bin.'/ran');
}

function make_controller($row, $named) {
  $GLOBALS['monitor_row'] = $row;
  $controller = new MonitorsController();
  $controller->request = new FakeRequest($named);
  return $controller;
}

function expect_not_found($name, $fn) {
  try {
    $fn();
    check($name, 'answered', 'NotFoundException');
  } catch (NotFoundException $e) {
    check($name, 'NotFoundException', 'NotFoundException');
  } catch (Exception $e) {
    check($name, get_class($e), 'NotFoundException');
  }
}

$deleted = array('Id' => 1, 'Type' => 'Ffmpeg', 'Capturing' => 'Modect', 'Device' => '', 'ServerId' => 1, 'Deleted' => 1);
$live = array('Id' => 1, 'Type' => 'Ffmpeg', 'Capturing' => 'Modect', 'Device' => '', 'ServerId' => 1, 'Deleted' => 0);

stub_command('zmu', 'echo 0');
stub_command('zmdc.pl', 'echo "\'zmc -m 1\' running since 26/11/16 10:00:00"');

// Deleted monitor: every action must refuse before running its command.
@unlink($bin.'/ran');
$c = make_controller($deleted, array('id' => 1, 'command' => 'on'));
expect_not_found('alarm on a deleted monitor is refused', function () use ($c) { $c->alarm(); });
check('alarm on a deleted monitor does not run zmu', command_ran(), false);

@unlink($bin.'/ran');
$c = make_controller($deleted, array('id' => 1, 'daemon' => 'zmc'));
expect_not_found('daemonStatus of a deleted monitor is refused', function () use ($c) { $c->daemonStatus(); });
check('daemonStatus of a deleted monitor does not run zmdc.pl', command_ran(), false);

@unlink($bin.'/ran');
$c = make_controller($deleted, array());
expect_not_found('daemonControl of a deleted monitor is refused', function () use ($c) { $c->daemonControl(1, 'start', 'zmc'); });
check('daemonControl of a deleted monitor does not run zmdc.pl', command_ran(), false);

// A monitor that is not deleted still runs the commands as before.
@unlink($bin.'/ran');
$c = make_controller($live, array('id' => 1, 'command' => 'status'));
$c->alarm();
check('alarm on a live monitor runs zmu', command_ran(), true);

@unlink($bin.'/ran');
$c = make_controller($live, array('id' => 1, 'daemon' => 'zmc'));
$c->daemonStatus();
check('daemonStatus of a live monitor runs zmdc.pl', command_ran(), true);

@unlink($bin.'/ran');
$c = make_controller($live, array());
$c->daemonControl(1, 'start', 'zmc');
check('daemonControl of a live monitor runs zmdc.pl', command_ran(), true);
check('daemonControl of a live monitor still answers ok', $c->viewVars['status'], 'ok');

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
