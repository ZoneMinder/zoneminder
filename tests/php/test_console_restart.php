<?php
/*
 * The console Restart action restarts the daemons of every selected monitor the
 * user may edit, refuses the rest with an error message, and does nothing at all
 * unless the request was a POST.
 *
 * Runs restartMonitors() from the real action file against stubs.
 *
 * Run: php tests/php/test_console_restart.php [zm-root]
 */

namespace ZM {
  function Warning($m) {}
  function AuditAction($action, $type, $id, $note) {
    $GLOBALS['audits'][] = array($action, $type, $id);
  }
  class Monitor {
    public static $byId = array();
    public static function find_one($parameters = array()) {
      $id = isset($parameters['Id']) ? $parameters['Id'] : 0;
      return isset(self::$byId[$id]) ? self::$byId[$id] : null;
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
      echo "  ok $name\n";
    } else {
      $failures++;
      echo "  FAIL $name\n";
      echo '    got:  '.var_export($got, true)."\n";
      echo '    want: '.var_export($want, true)."\n";
    }
  }

  function validCardinal($value) {
    if (!is_scalar($value) or !ctype_digit((string)$value)) return 0;
    return (int)$value;
  }
  function validHtmlStr($value) { return htmlspecialchars((string)$value); }

  class FakeMonitor {
    public $id;
    public $name;
    public $editable;
    public function __construct($id, $name, $editable) {
      $this->id = $id;
      $this->name = $name;
      $this->editable = $editable;
    }
    public function Id() { return $this->id; }
    public function Name() { return $this->name; }
    public function canEdit() { return $this->editable; }
    public function zmcControl($mode) { $GLOBALS['restarted'][] = array($this->id, $mode); }
  }

  $root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
  $path = $root.'/web/includes/actions/console.php';
  $src = file_get_contents($path);
  if (!preg_match('/^function restartMonitors\(.*?\n\}/ms', $src, $m)) {
    echo "Could not find restartMonitors() in $path\n";
    exit(1);
  }
  eval($m[0]);

  function reset_state($method) {
    $_SERVER['REQUEST_METHOD'] = $method;
    $GLOBALS['error_message'] = '';
    $GLOBALS['restarted'] = array();
    $GLOBALS['audits'] = array();
    \ZM\Monitor::$byId = array(
      1 => new FakeMonitor(1, 'Front Door', true),
      2 => new FakeMonitor(2, 'Garage', false),
      3 => new FakeMonitor(3, 'Porch', true),
    );
  }

  echo "editable monitors restart, the rest are refused\n";
  reset_state('POST');
  restartMonitors(array('1', '2', '3'));
  check('only editable monitors restarted', $GLOBALS['restarted'],
    array(array(1, 'restart'), array(3, 'restart')));
  check('refused monitor reported', strpos($GLOBALS['error_message'], 'Garage') !== false, true);
  check('audit entry per restarted monitor', $GLOBALS['audits'], array(
    array('restart', 'monitor', 1),
    array('restart', 'monitor', 3),
  ));

  echo "\nnon-POST requests are refused\n";
  reset_state('GET');
  restartMonitors(array('1', '3'));
  check('nothing restarted', $GLOBALS['restarted'], array());
  check('reported as a POST-only action', strpos($GLOBALS['error_message'], 'POST') !== false, true);

  echo "\nunknown ids are reported\n";
  reset_state('POST');
  restartMonitors(array('99'));
  check('nothing restarted', $GLOBALS['restarted'], array());
  check('not found reported', strpos($GLOBALS['error_message'], '99') !== false, true);

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
