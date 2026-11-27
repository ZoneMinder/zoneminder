<?php
// The REST API could read control definitions but had no way to send a PTZ
// command. The new ControlsController::command() action takes a monitor id and
// the same parameters as web/ajax/control.php, then:
//   * refuses anything but POST,
//   * refuses a monitor the user may not control (canView('Control', $id)),
//   * refuses a monitor whose Controllable flag is off,
//   * refuses a command the monitor's control does not offer,
//   * builds the command with buildControlCommand(), the same function the ajax
//     path uses, and hands it to ZM\Monitor::sendControlCommand().
//
// Run as: php tests/php/test_api_control_command.php [root]
// (root defaults to the repository root)

namespace ZM {
  class FakeControl {
    private $commands;
    function __construct($commands) { $this->commands = $commands; }
    function commands() { return $this->commands; }
  }

  class FakeMonitor {
    public static $rows = array();
    public static $sent = array();
    private $id;
    private $row;
    function __construct($id) {
      $this->id = (int)$id;
      $this->row = isset(self::$rows[$this->id]) ? self::$rows[$this->id] : null;
    }
    function Id() { return $this->row ? $this->id : 0; }
    function Controllable() { return $this->row ? $this->row['controllable'] : 0; }
    function Control() {
      return $this->row ? $this->row['control'] : new FakeControl(array());
    }
    function sendControlCommand($command) {
      self::$sent[] = $command;
      return $this->row ? $this->row['send'] : false;
    }
  }
}

namespace {
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

  $root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
  $file = $root.'/web/api/app/Controller/ControlsController.php';
  if (!is_file($file)) {
    fwrite(STDERR, "no such controller: $file\n");
    exit(2);
  }

  // Stubs for the includes control_functions.php pulls in.
  $stubdir = sys_get_temp_dir().'/zm_api_control_command_stubs_'.getmypid();
  @mkdir($stubdir, 0700, true);
  file_put_contents($stubdir.'/logger.php', "<?php\nnamespace ZM;\nfunction Warning(\$s) {}\nfunction Error(\$s) {}\nfunction Debug(\$s) {}\nfunction Info(\$s) {}\n");
  file_put_contents($stubdir.'/database.php', "<?php\nfunction dbFetchOne(\$sql, \$types=NULL, \$params=array()) { return null; }\nfunction dbQuery(\$sql, \$params=array()) { return true; }\n");
  set_include_path($stubdir.PATH_SEPARATOR.$root.'/web/includes'.PATH_SEPARATOR.get_include_path());

  if (!defined('SCALE_BASE')) define('SCALE_BASE', 100);
  if (!function_exists('__')) { function __($s) { return $s; } }
  if (!function_exists('validInt')) { function validInt($v) { return (int)$v; } }
  if (!function_exists('canEdit')) { function canEdit($area, $mid=false) { return true; } }
  if (!function_exists('deScale')) {
    function deScale($dimension, $dummy) {
      $new_dimension = $dimension;
      for ($i = 1; $i < func_num_args(); $i++) {
        $scale = func_get_arg($i);
        if (!empty($scale) && $scale != SCALE_BASE)
          $new_dimension = (int)(($new_dimension * SCALE_BASE) / $scale);
      }
      return $new_dimension;
    }
  }

  $GLOBALS['control_view'] = true;
  if (!function_exists('canView')) {
    function canView($area, $mid=false) { return $GLOBALS['control_view']; }
  }

  // The controller asks for ZM\Monitor; route that to the fake above.
  if (!class_exists('ZM\Monitor')) class_alias('ZM\FakeMonitor', 'ZM\Monitor');

  // The real buildControlCommand(), so the action cannot pass a copy of it that
  // behaves differently from the ajax path.
  require_once $root.'/web/includes/control_functions.php';

  foreach (array('BadRequestException', 'UnauthorizedException', 'NotFoundException', 'MethodNotAllowedException', 'InternalErrorException') as $e) {
    if (!class_exists($e)) eval("class $e extends Exception {}");
  }

  class FakeRequest {
    public $data;
    public $query = array();
    public $params = array('named' => array());
    private $method;
    function __construct($method, $data = array()) { $this->method = $method; $this->data = $data; }
    function is($type) { return in_array(strtolower($this->method), array_map('strtolower', (array)$type)); }
    function allowMethod() {
      foreach (func_get_args() as $m) if ($this->is($m)) return true;
      throw new MethodNotAllowedException('Method Not Allowed');
    }
  }

  class FakeResponse {
    public $code = null;
    function statusCode($c = null) { if ($c !== null) $this->code = $c; return $this->code; }
  }

  class AppController {
    public $request;
    public $response;
    public $viewVars = array();
    public function beforeFilter() {}
    public function set($vars) { $this->viewVars = $vars; }
  }

  // Load the real controller, dropping its App::uses/require_once lines: the
  // dependencies are the stubs above.
  $source = file_get_contents($file);
  $source = preg_replace('/^<\?php/', '', $source);
  $source = preg_replace('/^\s*App::uses\(.*$/m', '', $source);
  $source = preg_replace('/^\s*require_once\b.*$/m', '', $source);
  eval($source);

  $commands = array('moveConUp', 'moveConDown', 'moveStop', 'presetGoto', 'presetHome');
  ZM\FakeMonitor::$rows = array(
    1 => array('controllable' => 1, 'control' => new ZM\FakeControl($commands), 'send' => true),
    2 => array('controllable' => 0, 'control' => new ZM\FakeControl($commands), 'send' => true),
  );

  function run($method, $id, $data) {
    ZM\FakeMonitor::$sent = array();
    $controller = new ControlsController();
    $controller->request = new FakeRequest($method, $data);
    $controller->response = new FakeResponse();
    $result = 'ok';
    try {
      $controller->command($id);
    } catch (Throwable $e) {
      $result = get_class($e);
    }
    return array($result, ZM\FakeMonitor::$sent, $controller->viewVars);
  }

  // A valid command for a controllable monitor reaches the sender unchanged.
  $GLOBALS['control_view'] = true;
  list($r, $sent, $vars) = run('POST', 1, array('control' => 'moveConUp'));
  check('valid command is accepted', $r, 'ok');
  check('valid command is passed to sendControlCommand()', $sent, array(' --command=moveConUp'));
  check('valid command answers Success', isset($vars['message']) ? $vars['message'] : null, 'Success');

  // An unknown command must not reach the sender.
  list($r, $sent, $vars) = run('POST', 1, array('control' => 'definitelyNotACommand'));
  check('unknown command is refused', $r, 'BadRequestException');
  check('unknown command is not sent', $sent, array());

  // A monitor with Controllable off cannot be driven.
  list($r, $sent, $vars) = run('POST', 2, array('control' => 'moveConUp'));
  check('non-controllable monitor is refused', $r, 'BadRequestException');
  check('non-controllable monitor is not sent', $sent, array());

  // A user without Control permission cannot drive any monitor.
  $GLOBALS['control_view'] = false;
  list($r, $sent, $vars) = run('POST', 1, array('control' => 'moveConUp'));
  check('user without Control permission is refused', $r, 'UnauthorizedException');
  check('user without Control permission sends nothing', $sent, array());

  // The command is a mutation, so a GET is refused before anything else.
  $GLOBALS['control_view'] = true;
  list($r, $sent, $vars) = run('GET', 1, array('control' => 'moveConUp'));
  check('GET is refused', $r, 'MethodNotAllowedException');
  check('GET sends nothing', $sent, array());

  array_map('unlink', glob($stubdir.'/*'));
  @rmdir($stubdir);

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
