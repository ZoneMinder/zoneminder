<?php
// POST /api/events used to answer with an HTML redirect (flash) on success, so a
// JSON client got no id back, and a failed save rendered a non-existent
// View/Events/json/add.ctp (500). add() now always sets JSON: message/id on
// success and message/id=null on failure, with _serialize.
//
// EventsController can't be loaded without CakePHP, so add() is taken from the
// source and run against stubs.
// Run as: php tests/php/test_api_events_add_json.php [root]

$failures = 0;
function field($out, $key) {
  return is_array($out) && array_key_exists($key, $out) ? $out[$key] : null;
}
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$source = file_get_contents($root.'/web/api/app/Controller/EventsController.php');
$start = strpos($source, 'public function add(');
$open = strpos($source, '{', $start);
$depth = 0;
for ($i = $open; $i < strlen($source); $i++) {
  if ($source[$i] == '{') $depth++;
  else if ($source[$i] == '}' && --$depth == 0) break;
}
$method = substr($source, $start, $i - $start + 1);

class NotFoundException extends Exception {}
class UnauthorizedException extends Exception {}
class BadRequestException extends Exception {}
class InternalErrorException extends Exception {}
function __($s) { return $s; }

class FakeMonitorModel {
  function find($type, $options = array()) { return array(); }
}
class FakeEventModel {
  public $id;
  public $Monitor;
  public $saveResult = true;
  public $validatesResult = true;
  public $validationErrors = array();
  public $saveCalls = 0;
  function __construct() { $this->Monitor = new FakeMonitorModel(); }
  function create() {}
  function save($data) { $this->saveCalls++; return $this->saveResult; }
  function validates() { return $this->validatesResult; }
}
class FakeResponse { public $status = 200; function statusCode($c = null) { if ($c) $this->status = $c; return $this->status; } }
class FakeRequest {
  public $data = array('Event' => array('MonitorId' => 1));
  function is($type) { return $type === 'post'; }
}
eval('class FakeController { public $Event; public $request; public $response; public $out; public $flashCalls = 0;
  function requestField($model, $field) { return 1; }
  function requireMonitorView($monitorId) {}
  function pinRequestId($model, $id) {}
  function set($a) { $this->out = $a; }
  function flash($message, $url) { $this->flashCalls++; return "flashed"; }
  '.$method.' }');

global $user;
$user = null;

$c = new FakeController();
$c->Event = new FakeEventModel();
$c->request = new FakeRequest();

$c->Event->saveResult = true;
$c->Event->id = 42;
$c->add();
check('a saved event returns message Saved', field($c->out, 'message'), 'Saved');
check('a saved event returns the new id', field($c->out, 'id'), 42);
check('the response serializes message and id', field($c->out, '_serialize'), array('message', 'id'));
check('flash is not called', $c->flashCalls, 0);

$c = new FakeController();
$c->Event = new FakeEventModel();
$c->request = new FakeRequest();
$c->response = new FakeResponse();
$c->Event->saveResult = false;
$c->Event->validatesResult = false;
$c->Event->validationErrors = array('Name' => array('bad'));
$c->add();
check('a failed save returns the validation errors', field($c->out, 'message'), array('Name' => array('bad')));
check('a failed save returns a null id', field($c->out, 'id'), null);
check('a failed save answers 400', $c->response->status, 400);

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
?>
