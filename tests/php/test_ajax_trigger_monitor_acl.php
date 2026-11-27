<?php
// web/ajax/trigger.php checked only the global Monitors=Edit permission, so a user
// denied a particular monitor could still force or cancel alarms on it.
//
// Run as: php tests/php/test_ajax_trigger_monitor_acl.php [web root]

namespace ZM {
  class Monitor {
    public static $calls = array();
    public static $editable = array(1 => true, 2 => false);
    public $id;
    public function __construct($id) { $this->id = $id; }
    public static function find_one($f) { return isset(self::$editable[$f['Id']]) ? new Monitor($f['Id']) : null; }
    public function canEdit() { return self::$editable[$this->id]; }
    public function enable() { self::$calls[] = 'enable'; }
    public function disable() { self::$calls[] = 'disable'; }
    public function TriggerEventOn($s, $c, $t) { self::$calls[] = 'on'; return true; }
    public function TriggerShowtext($t) {}
    public function GetLastEventId() { return 0; }
    public function TriggerEventOff() { self::$calls[] = 'off'; }
    public function InAlarm() { return false; }
    public function TriggerEventCancel() { self::$calls[] = 'cancel'; }
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
    ZM\Monitor::$calls = array();
    try { include $root.'/ajax/trigger.php'; return 'returned'; }
    catch (AjaxError $e) { return $e->getMessage(); }
    catch (AjaxDone $e) { return 'ok'; }
  }

  foreach (array('on', 'off', 'enable', 'disable') as $a) {
    $r = run($root, array('mid' => 2, 'action' => $a, 'score' => 5));
    check("$a on a denied monitor is refused", $r, 'Insufficient permissions');
    check("$a on a denied monitor triggers nothing", ZM\Monitor::$calls, array());
  }
  $r = run($root, array('mid' => 1, 'action' => 'on', 'score' => 5));
  check('on an editable monitor works', $r, 'ok');
  check('on an editable monitor triggers', ZM\Monitor::$calls, array('on'));

  echo $failures ? "\n$failures failed\n" : "\nall passed\n";
  exit($failures ? 1 : 0);
}
