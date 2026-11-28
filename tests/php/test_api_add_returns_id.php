<?php
// POST to the servers, storage, states, camera models, manufacturers and server stats APIs
// answered a save with an HTML flash page (or just a message) and never returned the new id,
// and a non-POST request fell through to a missing json/add.ctp. add() now refuses non-POST
// and answers {message, id}.
//
// The controllers can't be loaded without CakePHP, so each add() is taken from the source
// and run against stubs.
// Run as: php tests/php/test_api_add_returns_id.php [root]   (root defaults to the repo)

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';

if (!class_exists('BadRequestException')) { class BadRequestException extends Exception {} }
if (!class_exists('UnauthorizedException')) { class UnauthorizedException extends Exception {} }
if (!class_exists('NotFoundException')) { class NotFoundException extends Exception {} }
if (!function_exists('__')) { function __($s) { return $s; } }
eval('namespace ZM; function Error($s) {}');

class FakeRequest {
  public $data;
  private $method;
  function __construct($method, $data = array()) { $this->method = $method; $this->data = $data; }
  function is($type) { return in_array(strtolower($this->method), array_map('strtolower', (array)$type)); }
}

class FakeAssoc {
  function find($type, $options = array()) { return array(); }
}

class FakeModel {
  public $id = null;
  public $validationErrors = array();
  public $saveResult = true;
  public $Event, $Monitor;
  function __construct() { $this->Event = new FakeAssoc(); $this->Monitor = new FakeAssoc(); }
  function create() { $this->id = false; }
  private function result() {
    if ($this->saveResult) $this->id = 42;
    else $this->validationErrors = array('Name' => array('required'));
    return $this->saveResult;
  }
  function save($data = null) { return $this->result(); }
  function saveAssociated($data = null, $options = array()) { return $this->result(); }
}

function extract_add($file) {
  $source = file_get_contents($file);
  $start = strpos($source, 'public function add(');
  if ($start === false) return null;
  $depth = 0;
  for ($i = strpos($source, '{', $start); $i < strlen($source); $i++) {
    if ($source[$i] == '{') $depth++;
    else if ($source[$i] == '}' && --$depth == 0) break;
  }
  return substr($source, $start, $i - $start + 1);
}

// A failed save sets a 400 on the controller's response.
class HarnessResponse {
  public $code = 200;
  function statusCode($code) { $this->code = $code; }
}

function run_add($class, $model, $method, $save_result) {
  global $user;
  $user = null;
  $h = new $class();
  $h->request = new FakeRequest($method, array($model => array('Name' => 'x')));
  $h->$model = new FakeModel();
  $h->$model->saveResult = $save_result;
  try {
    $h->add();
  } catch (Throwable $e) {
    return array(get_class($e), $h);
  }
  return array('ok', $h);
}

foreach (array('Servers' => 'Server', 'Storage' => 'Storage', 'States' => 'State', 'CameraModels' => 'CameraModel', 'Manufacturers' => 'Manufacturer', 'ServerStats' => 'ServerStat') as $name => $model) {
  $file = $root.'/web/api/app/Controller/'.$name.'Controller.php';
  $method = is_file($file) ? extract_add($file) : null;
  if ($method === null) {
    $failures++;
    echo "FAIL - $name: no add() in $file\n";
    continue;
  }
  $class = $name.'Harness';
  eval('class '.$class.' { public $request, $'.$model.', $vars = array(), $flashed = array(), $response;
    function __construct() { $this->response = new HarnessResponse(); }
    function set($v, $value = null) { $this->vars = array_merge($this->vars, is_array($v) ? $v : array($v => $value)); }
    function flash($message, $url = null) { $this->flashed[] = $message; return "FLASH"; }
    function requestField($alias, $field) { return null; }
    function requestAssociatedIds($alias, $assoc) { return null; }
    function requireEventsView($ids) {}
    function pinRequestId($model, $id) { $model->id = $id; }
    '.$method.' }');

  list($r, $h) = run_add($class, $model, 'POST', true);
  check("$name: POST save answers without an error", $r, 'ok');
  check("$name: POST save answers Saved", isset($h->vars['message']) ? $h->vars['message'] : null, 'Saved');
  check("$name: POST save returns the new id", isset($h->vars['id']) ? $h->vars['id'] : null, 42);
  check("$name: POST save serializes message and id", isset($h->vars['_serialize']) ? $h->vars['_serialize'] : null, array('message', 'id'));
  check("$name: POST save does not flash an HTML page", $h->flashed, array());

  list($r, $h) = run_add($class, $model, 'POST', false);
  check("$name: failed save answers the errors", isset($h->vars['message']) ? $h->vars['message'] : null, array('Name' => array('required')));
  check("$name: failed save returns a null id", array_key_exists('id', $h->vars) ? $h->vars['id'] : 'missing', null);

  list($r, $h) = run_add($class, $model, 'GET', true);
  check("$name: GET is a BadRequestException", $r, 'BadRequestException');
}

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
