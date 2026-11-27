<?php
// web/ajax/trigger.php answered Ok to enable, disable and off when the monitor
// was not running (no shared memory), although nothing was changed. 'on'
// already reported that case as an error.
//
// Run as: php tests/php/test_ajax_trigger_not_running.php [web root]

namespace ZM {
  class Monitor {
    public static $running = false;
    public static function find_one($f) { return new Monitor(); }
    public function canEdit() { return true; }
    public function enable() { return self::$running; }
    public function disable() { return self::$running; }
    public function TriggerEventOn($s, $c, $t) { return self::$running; }
    public function TriggerShowtext($t) {}
    public function GetLastEventId() { return self::$running ? 7 : false; }
    public function TriggerEventOff() { if (!self::$running) return false; }
    public function InAlarm() { return false; }
    public function TriggerEventCancel() { if (!self::$running) return false; }
  }
  function Info($s) {}
  function Warning($s) {}
}

namespace {
  class AjaxError extends Exception {}
  class AjaxDone extends Exception {}
  function ajaxError($m, $c = 0, $r = '') { throw new AjaxError($m); }
  function ajaxResponse($d = array()) { throw new AjaxDone(); }
  function canEdit($r) { return true; }
  function validCardinal($v) { return (int)$v; }

  $root = isset($argv[1]) ? $argv[1] : __DIR__.'/../../web';
  $failures = 0;
  function check($name, $got, $want) {
    global $failures;
    if ($got === $want) echo "ok - $name\n";
    else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
  }
  function run($root, $req) {
    $_REQUEST = $req;
    try { include $root.'/ajax/trigger.php'; return 'returned'; }
    catch (AjaxError $e) { return 'error'; }
    catch (AjaxDone $e) { return 'ok'; }
  }

  foreach (array('enable', 'disable', 'on', 'off') as $a) {
    ZM\Monitor::$running = false;
    check("$a on a monitor that is not running is an error", run($root, array('mid' => 1, 'action' => $a, 'score' => 5)), 'error');
    ZM\Monitor::$running = true;
    check("$a on a running monitor is ok", run($root, array('mid' => 1, 'action' => $a, 'score' => 5)), 'ok');
  }

  echo $failures ? "\n$failures failed\n" : "\nall passed\n";
  exit($failures ? 1 : 0);
}
