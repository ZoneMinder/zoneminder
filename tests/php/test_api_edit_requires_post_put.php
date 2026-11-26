<?php
// Seven API edit() actions saved for any request method, so a GET to
// /api/servers/edit/1.json (or events, storage, ...) ran save() with no data and
// answered 200 with a failure message, or with whatever save() made of empty data. They now
// call allowMethod('post', 'put') before saving, as UsersController,
// NotificationsController and StatesController already do.
//
// The controllers can't be loaded without CakePHP, so each edit() is taken from the source
// and run against stubs.
// Run as: php tests/php/test_api_edit_requires_post_put.php [root]   (root defaults to the repo)

namespace ZM {
  class Event {
    function __construct($row = null) {}
    function canEdit() { return true; }
  }
}

namespace {

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';

foreach (array('BadRequestException', 'UnauthorizedException', 'NotFoundException', 'MethodNotAllowedException') as $e) {
  if (!class_exists($e)) eval("class $e extends Exception {}");
}
if (!function_exists('__')) { function __($s) { return $s; } }

class FakeRequest {
  public $data;
  private $method;
  function __construct($method, $data = array()) { $this->method = $method; $this->data = $data; }
  function is($type) { return in_array(strtolower($this->method), array_map('strtolower', (array)$type)); }
  function allowMethod() {
    foreach (func_get_args() as $m) if ($this->is($m)) return true;
    throw new MethodNotAllowedException('Method Not Allowed');
  }
}

class FakeResponse {
  function statusCode($c = null) {}
  function httpCodes($c = null) {}
}

class FakeModel {
  public $id = null;
  public $primaryKey = 'Id';
  public $recursive = 0;
  public $validationErrors = array();
  public $saveCalls = 0;
  private $alias;
  function __construct($alias) { $this->alias = $alias; }
  function exists($id = null) { return true; }
  function find($type, $options = array()) { return array($this->alias => array('Id' => 7, 'MonitorId' => 1)); }
  function save($data = null, $validate = true, $fieldList = array()) { $this->saveCalls++; return true; }
  function saveField($f, $v) { $this->saveCalls++; return true; }
}

function extract_edit($file) {
  $source = file_get_contents($file);
  $start = strpos($source, 'public function edit(');
  if ($start === false) return null;
  $depth = 0;
  for ($i = strpos($source, '{', $start); $i < strlen($source); $i++) {
    if ($source[$i] == '{') $depth++;
    else if ($source[$i] == '}' && --$depth == 0) break;
  }
  return substr($source, $start, $i - $start + 1);
}

$controllers = array(
  'CameraModels' => 'CameraModel', 'EncoderTemplates' => 'EncoderTemplate', 'Events' => 'Event',
  'Manufacturers' => 'Manufacturer', 'ServerStats' => 'ServerStat', 'Servers' => 'Server',
  'Storage' => 'Storage',
);

foreach ($controllers as $name => $model) {
  $file = $root.'/web/api/app/Controller/'.$name.'Controller.php';
  $edit = is_file($file) ? extract_edit($file) : null;
  if ($edit === null) {
    $failures++;
    echo "FAIL - $name: no edit() in $file\n";
    continue;
  }
  $class = $name.'EditHarness';
  eval('class '.$class.' { public $request, $response, $'.$model.', $vars = array();
    function set($v, $value = null) { $this->vars = array_merge($this->vars, is_array($v) ? $v : array($v => $value)); }
    function pinRequestId($model, $id) { $model->id = $id; }
    function requestField($alias, $field) { return null; }
    function requireMonitorView($id) {}
    function daemonControl() {}
    '.$edit.' }');

  foreach (array('GET' => false, 'PUT' => true, 'POST' => true) as $method => $should_save) {
    global $user;
    $user = null;
    $h = new $class();
    $h->request = new FakeRequest($method, array($model => array('Name' => 'x')));
    $h->response = new FakeResponse();
    $h->$model = new FakeModel($model);
    $result = 'ok';
    try {
      $h->edit(7);
    } catch (Throwable $e) {
      $result = get_class($e);
    }
    if ($should_save) {
      check("$name: $method saves", $h->$model->saveCalls, 1);
      check("$name: $method answers Saved", isset($h->vars['message']) ? $h->vars['message'] : null, 'Saved');
    } else {
      check("$name: $method is refused", $result, 'MethodNotAllowedException');
      check("$name: $method does not save", $h->$model->saveCalls, 0);
    }
  }
}

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
}
