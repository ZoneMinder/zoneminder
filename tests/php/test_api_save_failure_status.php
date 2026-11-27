<?php
// API controllers answered 200 when a save failed (validation errors in `message`), unlike
// UsersController and NotificationsController which answer 400. add()/edit() are taken from
// the source and run against stubs: a failed save must answer 400, a good save 200.
// Run as: php tests/php/test_api_save_failure_status.php [repo root]
$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$failures = 0;
error_reporting(E_ALL & ~E_WARNING & ~E_DEPRECATED);
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}
eval('namespace ZM; function Error($m) {} function Warning($m) {}
class Group { function __construct($id) {} function ParentId() { return null; } function MonitorIds() { return array(); } }');
class BadRequestException extends Exception {}
class UnauthorizedException extends Exception {}
class NotFoundException extends Exception {}
class ForbiddenException extends Exception {}
class InternalErrorException extends Exception {}
function __($s) { return $s; }
function dbFetchAll() { return array(); }
function dbQuery() { return array(); }
define('ZM_LOG_INJECT', true);
class FakeModel {
  public $id, $primaryKey = 'Id', $ok = false;
  public $validationErrors = array('Name' => array('is required'));
  function exists($id = null) { return true; }
  function create() { return $this->ok; }
  function save($d = null, $a = null, $b = null) { return $this->ok; }
  function saveAssociated($d = null, $o = null) { return $this->ok; }
  function validates() { return $this->ok; }
  function updateAll() { return $this->ok; }
  function find() { return array(); }
  function findById($id) { return array(); }
  function field() { return null; }
  function set($d) {}
  function getColumnType($f) { return 'string'; }
  function getDataSource() { return $this; }
  function value($v) { return $v; }
}
class FakeResponse { public $status = 200; function statusCode($c = null) { if ($c) $this->status = $c; return $this->status; } }
class FakeRequest {
  public $data = array('UserId' => 1, 'Name' => 'x'), $method;
  function __construct($m) { $this->method = $m; }
  function is($t) { foreach ((array)$t as $m) if (strtolower($m) == strtolower($this->method)) return true; return false; }
  function allowMethod() { return true; }
  function input() { return array(); }
}
class HarnessBase {
  public $request, $response, $RequestHandler, $set = array(), $models = array(), $ok = false;
  function __construct($method, $ok) {
    $this->ok = $ok;
    $this->request = new FakeRequest($method);
    $this->response = new FakeResponse;
    $this->RequestHandler = new class { function requestedWith() { return false; } };
  }
  function __get($n) { if (!isset($this->models[$n])) { $this->models[$n] = new FakeModel; $this->models[$n]->ok = $this->ok; } return $this->models[$n]; }
  function __call($n, $a) { return $n == 'requestColumns' ? array('Type' => 'x') : null; }
  function pinRequestId($m, $id) { $m->id = $id; }
  function set($a) { $this->set = $a; }
  function currentUserId() { return 1; }
  function assertOwned($id) {}
}
function methodSource($source, $name) {
  $start = strpos($source, 'public function '.$name.'(');
  $open = strpos($source, '{', $start);
  $depth = 0;
  for ($i = $open; $i < strlen($source); $i++) {
    if ($source[$i] == '{') $depth++;
    else if ($source[$i] == '}' && --$depth == 0) break;
  }
  return substr($source, $start, $i - $start + 1)."\n";
}
// controller => methods to run with the argument lists they take
$cases = array(
  'CameraModels' => array('add' => array(), 'edit' => array(3)),
  'Storage' => array('add' => array(), 'edit' => array(3)),
  'Manufacturers' => array('add' => array(), 'edit' => array(3)),
  'States' => array('add' => array(), 'edit' => array(3)),
  'Tags' => array('add' => array(), 'edit' => array(3)),
  'Groups' => array('add' => array(), 'edit' => array(3)),
  'Snapshots' => array('add' => array(), 'edit' => array(3)),
  'EventData' => array('add' => array(), 'edit' => array(3)),
  'Configs' => array('edit' => array(3)),
  'Logs' => array('add' => array(), 'edit' => array(3)),
  'ZonePresets' => array('add' => array(), 'edit' => array(3)),
  'Frames' => array('add' => array(), 'edit' => array(1, 2)),
  'UserPreference' => array('add' => array(), 'edit' => array(3)),
);
$n = 0;
foreach ($cases as $ctl => $methods) {
  $source = file_get_contents($root.'/web/api/app/Controller/'.$ctl.'Controller.php');
  $body = '';
  foreach (array_keys($methods) as $m) $body .= methodSource($source, $m);
  $class = 'Harness'.(++$n);
  eval('class '.$class.' extends HarnessBase { '.$body.' }');
  foreach ($methods as $m => $args) {
    foreach (array(false, true) as $ok) {
      $h = new $class($m == 'add' ? 'POST' : 'PUT', $ok);
      try { call_user_func_array(array($h, $m), $args); } catch (Throwable $e) { echo "  ($ctl::$m threw ".get_class($e).")\n"; }
      check("$ctl::$m ".($ok ? 'good save answers 200' : 'failed save answers 400'), $h->response->status, $ok ? 200 : 400);
    }
  }
}
// add() without POST: UserPreference answered 200, siblings throw BadRequestException.
$source = file_get_contents($root.'/web/api/app/Controller/UserPreferenceController.php');
eval('class HarnessUP extends HarnessBase { '.methodSource($source, 'add').' }');
$h = new HarnessUP('GET', false);
$threw = false;
try { $h->add(); } catch (BadRequestException $e) { $threw = true; }
check('UserPreference::add non-POST is a bad request', $threw, true);
exit($failures ? 1 : 0);
