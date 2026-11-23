<?php
// Monitors, Events, Servers, ServerStats and Zones add()/edit() answered 200 when a save failed
// (the error only in `message`), unlike UsersController and NotificationsController which answer
// 400. add()/edit() are taken from the source and run against stubs: a failed save must answer
// 400.
// Run as: php tests/php/test_api_save_failure_status_more.php [repo root]
$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$failures = 0;
error_reporting(E_ALL & ~E_WARNING & ~E_DEPRECATED);
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}
eval('namespace ZM; function Error($m) {} function Warning($m) {}
class Monitor {
  function __construct($m = null) {}
  function canEdit() { return true; }
  function commandFieldChangesNotAllowed($f) { return array(); }
  function Type() { return "Ffmpeg"; }
  function Deleted() { return false; }
  function Capturing() { return "None"; }
  function Controllable() { return false; }
  function zmcControl($m) {}
  function zmtrackControl($m) {}
  function sendControlCommand($m) {}
}
class Zone { function save($d) {} }
class Event { function __construct($e = null) {} function canEdit() { return true; } }');
class BadRequestException extends Exception {}
class UnauthorizedException extends Exception {}
class NotFoundException extends Exception {}
class ForbiddenException extends Exception {}
function __($s) { return $s; }
class FakeModel {
  public $id, $primaryKey = 'Id', $ok = false;
  public $validationErrors = array('Name' => array('is required'));
  function exists($id = null) { return true; }
  function create() {}
  function save($d = null) { return $this->ok; }
  function validates() { return $this->ok; }
  function invalidFields() { return array(); }
  function find($t = null, $o = null) { return array('Monitor' => array('Id' => 1), 'Event' => array('MonitorId' => 1)); }
}
class FakeResponse { public $status = 200; function statusCode($c = null) { if ($c) $this->status = $c; return $this->status; } }
class FakeRequest {
  public $data = array('Name' => 'x'), $method;
  function __construct($m) { $this->method = $m; }
  function is($t) { foreach ((array)$t as $m) if (strtolower($m) == strtolower($this->method)) return true; return false; }
  function allowMethod() { return true; }
}
class HarnessBase {
  public $request, $response, $set = array(), $models = array(), $ok = false;
  function __construct($method, $ok) {
    $this->ok = $ok;
    $this->request = new FakeRequest($method);
    $this->response = new FakeResponse;
  }
  function __get($n) { if (!isset($this->models[$n])) { $this->models[$n] = new FakeModel; $this->models[$n]->ok = $this->ok; } return $this->models[$n]; }
  function __call($n, $a) { return $n == 'requestCommandFields' ? array() : (in_array($n, array('requestMonitorId', 'zoneMonitorId', 'requestField')) ? 1 : null); }
  function pinRequestId($m, $id) { $m->id = $id; }
  function set($a) { $this->set = $a; }
}
function methodSource($source, $name) {
  $start = strpos($source, 'public function '.$name.'(');
  $open = strpos($source, '{', $start);
  $depth = 0;
  for ($i = $open; $i < strlen($source); $i++) {
    if ($source[$i] == '{') $depth++;
    else if ($source[$i] == '}' && --$depth == 0) break;
  }
  // The method's require_once paths are relative to its own file; the stubs above stand in.
  return preg_replace('/^\s*require_once .*$/m', '', substr($source, $start, $i - $start + 1))."\n";
}
$cases = array('Monitors', 'Events', 'Servers', 'ServerStats', 'Zones');
$n = 0;
foreach ($cases as $ctl) {
  $source = file_get_contents($root.'/web/api/app/Controller/'.$ctl.'Controller.php');
  $class = 'Harness'.(++$n);
  eval('class '.$class.' extends HarnessBase { '.methodSource($source, 'add').methodSource($source, 'edit').' }');
  foreach (array('add' => array(), 'edit' => array(3)) as $m => $args) {
    $h = new $class($m == 'add' ? 'POST' : 'PUT', false);
    try { call_user_func_array(array($h, $m), $args); } catch (Throwable $e) { echo "  ($ctl::$m threw ".get_class($e).": ".$e->getMessage().")\n"; }
    check("$ctl::$m failed save answers 400", $h->response->status, 400);
  }
}
exit($failures ? 1 : 0);
