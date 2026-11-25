<?php
// The multi-monitor edit (console, select monitors, Edit) posts every field for every
// selected monitor. actions/monitors.php stopped and restarted capture (zmc, zmtrack) on
// each of them on every save, even for a monitor the form changed nothing on, so each save
// left a gap in every selected camera's recording. A monitor with no changes is now left
// running.
//
// php tests/php/test_monitors_bulk_save_unchanged.php [action-file]

namespace ZM {
  function Warning($s) {}
  function AuditAction() {}
  class Monitor {
    static $current = array();
    static $calls = array();
    private $id;
    function __construct($id) { $this->id = $id; }
    function Id() { return $this->id; }
    function Deleted() { return false; }
    function Type() { return 'Ffmpeg'; }
    function Capturing() { return 'Always'; }
    function commandFieldChangesNotAllowed($v) { return array(); }
    function changes($new) {
      $changes = array();
      foreach ($new as $k => $v) if ((self::$current[$this->id][$k] ?? null) != $v) $changes[$k] = $v;
      return $changes;
    }
    function save($v) { self::$calls[] = "save $this->id"; return true; }
    function get_last_error() { return ''; }
    function zmcControl($m) { self::$calls[] = "zmc $m $this->id"; }
    function zmtrackControl($m) { self::$calls[] = "zmtrack $m $this->id"; }
  }
}

namespace {
  function canEdit($p, $id = null) { return true; }
  function ValidInt($v) { return (int)$v; }

  $action_file = isset($argv[1]) ? $argv[1] : __DIR__.'/../../web/includes/actions/monitors.php';

  $failures = 0;
  function check($n, $got, $want) {
    global $failures;
    if ($got === $want) echo "ok - $n\n";
    else { $failures++; echo "FAIL - $n (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
  }

  function run($newMonitor) {
    global $action_file, $action, $error_message, $redirect, $view;
    ZM\Monitor::$calls = array();
    ZM\Monitor::$current = array(
      1 => array('Capturing' => 'Always', 'Importance' => 'Normal'),
      2 => array('Capturing' => 'Always', 'Importance' => 'Less'),
    );
    $action = 'save';
    $error_message = '';
    $redirect = null;
    $_REQUEST = array('action' => 'save', 'mids' => array('1', '2'), 'newMonitor' => $newMonitor);
    include $action_file;
    return ZM\Monitor::$calls;
  }

  $calls = run(array('Capturing' => 'Always', 'Importance' => 'unchanged'));
  check('a save that changes nothing restarts no monitor', $calls, array());
  check('a save that changes nothing still returns to the console', $redirect, '?view=console');

  $calls = run(array('Capturing' => 'Always', 'Importance' => 'Normal'));
  check('only the monitor that changes is stopped, saved and started',
    $calls, array('zmc stop 2', 'save 2', 'zmc start 2', 'zmtrack start 2'));

  echo $failures ? "\n$failures failed\n" : "\nall passed\n";
  exit($failures ? 1 : 0);
}
