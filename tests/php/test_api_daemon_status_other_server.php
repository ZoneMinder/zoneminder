<?php
// MonitorsController::daemonStatus() asked the local zmdc.pl about any monitor, so a
// monitor assigned to another server was reported as not running (status false) even
// while it ran fine there. daemonControl() already answers that case with an error;
// daemonStatus() now answers status false with an explanatory statustext and does not
// run zmdc.pl.
//
// Run as: php tests/php/test_api_daemon_status_other_server.php [root]
// root holds web/ (default: this repo); use an extracted `git archive HEAD web` to
// check the original code.

$root = isset($argv[1]) ? rtrim($argv[1], '/') : __DIR__.'/../..';
$model_file = $root.'/web/api/app/Model/Monitor.php';
$ctrl_file = $root.'/web/api/app/Controller/MonitorsController.php';
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
function visibleMonitor($id) { return true; }
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
// Cake's Set::extract('/Monitor/.', $result) as used by daemonStatus().
class Set { public static function extract($path, $data) { return array($data['Monitor']); } }
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


function status_of($row) {
  $GLOBALS['monitor_row'] = $row;
  $c = new MonitorsController();
  $c->request = new stdClass();
  $c->request->params = array('named' => array('id' => $row['Id'], 'daemon' => 'zmc'));
  $c->daemonStatus();
  return $c->viewVars;
}

$running = "echo \"'zmc -m 1' running since 26/11/16 10:00:00, pid = 100\"";
stub_zmdc($running);
$r = status_of(array('Id' => 1, 'Type' => 'Ffmpeg', 'Capturing' => 'Modect', 'Device' => '', 'ServerId' => 1));
check('monitor of this server running', $r['status'], true);
check('zmdc.pl ran for this server', zmdc_ran(), true);

stub_zmdc($running);
$r = status_of(array('Id' => 1, 'Type' => 'Ffmpeg', 'Capturing' => 'Modect', 'Device' => '', 'ServerId' => 2));
check('monitor of another server is not reported from local zmdc', $r['status'], false);
check('other server is explained', $r['statustext'] ?? null, 'Monitor is assigned to a different server');
check('other server does not run zmdc.pl', zmdc_ran(), false);

echo "$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
