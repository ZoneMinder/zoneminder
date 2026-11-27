<?php
// EventDataController (API): a non-POST/PUT request to add() or edit() fell through to
// `$this->set(compact('events'))`, and the View/EventData/json/{add,edit}.ctp views echo
// $event_data, which was never set. So GET /api/event_data/edit/5.json answered 200 with
// the body `null` plus a PHP "Undefined variable $event_data" warning. add() now refuses
// a non-POST with BadRequestException (as EventsController::add does) and edit() calls
// $this->request->allowMethod('post', 'put') (as UsersController::edit does), so both
// refuse other methods and successful saves still answer a serialized JSON body.
//
// EventDataController can't be loaded without CakePHP, so add() and edit() are taken from
// the source and run against stubs.
// Run as: php tests/php/test_api_eventdata_non_post.php [root]   (root defaults to the repo)

error_reporting(E_ALL);
$warnings = array();
set_error_handler(function($no, $str) use (&$warnings) { $warnings[] = $str; return true; });

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$file = $root.'/web/api/app/Controller/EventDataController.php';
if (!is_file($file)) {
  fwrite(STDERR, "no such controller: $file\n");
  exit(1);
}

if (!class_exists('BadRequestException')) { class BadRequestException extends Exception {} }
if (!class_exists('UnauthorizedException')) { class UnauthorizedException extends Exception {} }
if (!class_exists('NotFoundException')) { class NotFoundException extends Exception {} }
if (!class_exists('MethodNotAllowedException')) { class MethodNotAllowedException extends Exception {} }
if (!function_exists('__')) { function __($s) { return $s; } }

class FakeResponse { public $status = 200; function statusCode($c = null) { if ($c) $this->status = $c; return $this->status; } }
class FakeRequest {
  public $data;
  private $method;
  function __construct($method, $data = array()) { $this->method = $method; $this->data = $data; }
  function is($type) { return in_array(strtolower($this->method), array_map('strtolower', (array)$type)); }
  function allowMethod() {
    foreach (func_get_args() as $m) if ($this->is($m)) return true;
    throw new MethodNotAllowedException();
  }
}

class FakeEvent {
  function find($type, $options = array()) { return array(1 => 'Event 1'); }
}

class FakeEventData {
  public $id = null;
  public $alias = 'EventData';
  public $primaryKey = 'Id';
  public $recursive = -1;
  public $saveResult = true;
  public $validationErrors = array();
  public $saved = null;
  public $Event;
  function __construct() { $this->Event = new FakeEvent(); }
  function exists($id = null) { return true; }
  function create() { $this->id = null; }
  function save($data, $validate = true, $fieldList = array()) {
    $this->saved = $data;
    if ($this->saveResult) $this->id = 7;
    return $this->saveResult;
  }
  function find($type, $options = array()) {
    if ($type == 'list') return $this->Event->find('list');
    return array('EventData' => array('Id' => 5, 'EventId' => 1, 'MonitorId' => 1));
  }
  function findById($id) { return array('EventData' => array('Id' => $id, 'EventId' => 1, 'MonitorId' => 1)); }
}

function extract_method($source, $name) {
  $start = strpos($source, "public function $name(");
  if ($start === false) return null;
  $depth = 0;
  for ($i = strpos($source, '{', $start); $i < strlen($source); $i++) {
    if ($source[$i] == '{') $depth++;
    else if ($source[$i] == '}' && --$depth == 0) break;
  }
  return substr($source, $start, $i - $start + 1);
}

$source = file_get_contents($file);
$add = extract_method($source, 'add');
$edit = extract_method($source, 'edit');
if ($add === null or $edit === null) {
  fwrite(STDERR, "no add()/edit() in $file\n");
  exit(1);
}

eval('class EventDataHarness { public $request, $response, $EventData, $vars = array();
  function set($v, $value = null) { $this->vars = array_merge($this->vars, is_array($v) ? $v : array($v => $value)); }
  function requireEventDataEdit($id) {}
  function requireRequestEventDataEdit($required) {}
  function pinRequestId($model, $id) { $model->id = $id; }
  '.$add.' '.$edit.' }');

function run($action, $method, $save_result = true) {
  global $user, $warnings;
  $user = null;
  $warnings = array();
  $h = new EventDataHarness();
  $h->request = new FakeRequest($method, array('EventData' => array('EventId' => 1, 'MonitorId' => 1))); $h->response = new FakeResponse;
  $h->EventData = new FakeEventData();
  $h->EventData->saveResult = $save_result;
  try {
    if ($action == 'add') $h->add(); else $h->edit(5);
  } catch (Throwable $e) {
    return array(get_class($e), $h, $warnings);
  }
  return array('ok', $h, $warnings);
}

# A GET must be refused, not answered with a 200 and a body built from an unset variable.
list($r, $h, $w) = run('add', 'GET');
check('add: GET is a BadRequestException', $r, 'BadRequestException');
check('add: GET raises no PHP warning', $w, array());
check('add: GET sets no response body', $h->vars, array());

list($r, $h, $w) = run('edit', 'GET');
check('edit: GET is a MethodNotAllowedException', $r, 'MethodNotAllowedException');
check('edit: GET raises no PHP warning', $w, array());
check('edit: GET sets no response body', $h->vars, array());

# A successful POST still saves and answers JSON via _serialize.
list($r, $h, $w) = run('add', 'POST');
check('add: POST saves', $r, 'ok');
check('add: POST answers Saved', isset($h->vars['message']) ? $h->vars['message'] : null, 'Saved');
check('add: POST serializes message and id', isset($h->vars['_serialize']) ? $h->vars['_serialize'] : null, array('message', 'id'));
check('add: POST saves the submitted data', $h->EventData->saved, array('EventData' => array('EventId' => 1, 'MonitorId' => 1)));

list($r, $h, $w) = run('edit', 'PUT');
check('edit: PUT saves', $r, 'ok');
check('edit: PUT answers Saved', isset($h->vars['message']) ? $h->vars['message'] : null, 'Saved');
check('edit: PUT serializes message and event_data', isset($h->vars['_serialize']) ? $h->vars['_serialize'] : null, array('message', 'event_data'));
check('edit: PUT returns the row', isset($h->vars['event_data']) ? $h->vars['event_data'] : null, array('EventData' => array('Id' => 5, 'EventId' => 1, 'MonitorId' => 1)));

list($r, $h, $w) = run('edit', 'POST', false);
check('edit: failed POST answers the errors', isset($h->vars['message']) ? $h->vars['message'] : null, 'Error');
check('edit: failed POST still serializes', isset($h->vars['_serialize']) ? $h->vars['_serialize'] : null, array('message', 'event_data'));

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
