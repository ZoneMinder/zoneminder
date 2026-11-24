<?php
// The multi-monitor edit saved whatever ids the form posted. A monitor that was deleted in
// another tab (or whose row is already gone) loads as an empty object, and saving an empty
// object inserts a new monitor built from the defaults and the bulk fields. A deleted monitor
// was edited as well. Both are now skipped with an error on the page.
//
// php tests/php/test_monitors_bulk_save_missing_monitor.php [action-file]

namespace ZM {
  function Warning($s) {}
  function AuditAction() {}
  class Monitor {
    static $rows = array();
    static $calls = array();
    private $id = null;
    function __construct($id) { if (isset(self::$rows[$id])) $this->id = $id; }
    function Id() { return $this->id; }
    function Deleted() { return $this->id !== null and !empty(self::$rows[$this->id]['Deleted']); }
    function Type() { return 'Ffmpeg'; }
    function Capturing() { return 'Always'; }
    function commandFieldChangesNotAllowed($v) { return array(); }
    function changes($new) { return $new; }
    function save($v) { self::$calls[] = $this->id === null ? 'insert' : "save $this->id"; return true; }
    function get_last_error() { return ''; }
    function zmcControl($m) { self::$calls[] = "zmc $m"; }
    function zmtrackControl($m) { self::$calls[] = "zmtrack $m"; }
  }
}

namespace {
  function canEdit($p, $id = null) { return true; }
  function ValidInt($v) { return (int)$v; }
  function validHtmlStr($v) { return htmlspecialchars($v); }

  $action_file = isset($argv[1]) ? $argv[1] : __DIR__.'/../../web/includes/actions/monitors.php';

  $failures = 0;
  function check($n, $got, $want) {
    global $failures;
    if ($got === $want) echo "ok - $n\n";
    else { $failures++; echo "FAIL - $n (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
  }

  function run($mids) {
    global $action_file, $action, $error_message, $redirect, $view;
    ZM\Monitor::$calls = array();
    ZM\Monitor::$rows = array(1 => array(), 3 => array('Deleted' => 1));
    $action = 'save';
    $error_message = '';
    $redirect = null;
    $_REQUEST = array('action' => 'save', 'mids' => $mids, 'newMonitor' => array('Importance' => 'Less'));
    include $action_file;
    return ZM\Monitor::$calls;
  }

  $calls = run(array('1', '2'));
  check('a monitor that no longer exists is not inserted',
    $calls, array('zmc stop', 'save 1', 'zmc start', 'zmtrack start'));
  check('the missing monitor is reported', strpos($error_message, 'Monitor 2 no longer exists') !== false, true);
  check('an error keeps the console from redirecting', $redirect, null);

  $calls = run(array('3'));
  check('a deleted monitor is not edited or started', $calls, array());
  check('the deleted monitor is reported', strpos($error_message, 'Monitor 3 no longer exists') !== false, true);

  $calls = run(array('1'));
  check('an existing monitor is still saved', $calls, array('zmc stop', 'save 1', 'zmc start', 'zmtrack start'));
  check('a clean save returns to the console', $redirect, '?view=console');

  echo $failures ? "\n$failures failed\n" : "\nall passed\n";
  exit($failures ? 1 : 0);
}
