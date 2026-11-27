<?php
// Groups and Tags edit() fell through on a GET and answered 200 with an empty message.
// edit() now calls $this->request->allowMethod('post', 'put'), as UsersController::edit
// does, so other methods get a 405 and saves still answer a serialized JSON body.
// add() is checked too, so the JSON contract of both actions stays covered.
//
// The controllers can't be loaded without CakePHP, so add() and edit() are taken from
// the source and run against stubs.
// Run as: php tests/php/test_api_groups_tags_presets_json.php [root]   (root defaults to the repo)

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

if (!class_exists('BadRequestException')) { class BadRequestException extends Exception {} }
if (!class_exists('UnauthorizedException')) { class UnauthorizedException extends Exception {} }
if (!class_exists('NotFoundException')) { class NotFoundException extends Exception {} }
if (!class_exists('MethodNotAllowedException')) { class MethodNotAllowedException extends Exception {} }
if (!class_exists('InternalErrorException')) { class InternalErrorException extends Exception {} }
if (!function_exists('__')) { function __($s) { return $s; } }
if (!function_exists('ZM\Error')) { eval('namespace ZM; function Error($s) {}'); }
if (!class_exists('ZM\Group')) {
  eval('namespace ZM; class Group {
    private $id;
    function __construct($id = null) { $this->id = $id; }
    function ParentId() { return null; }
    function MonitorIds() { return array(); }
  }');
}

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

class FakeModel {
  public $id = null;
  public $alias;
  public $primaryKey = 'Id';
  public $validationErrors = array();
  public $saveResult = true;
  public $saveAssociatedResult = true;
  public $saved = null;
  function __construct($alias) { $this->alias = $alias; }
  function create() { $this->id = null; }
  function exists($id = null) { return true; }
  function field($field, $conditions = array()) { return null; }
  function save($data = null) {
    $this->saved = $data;
    if ($this->saveResult) { if (!$this->id) $this->id = 42; }
    return $this->saveResult;
  }
  function saveAssociated($data, $options = array()) {
    $this->saved = $data;
    if ($this->saveAssociatedResult) { $this->id = 42; return true; }
    return false;
  }
  function validates($options = array()) { return !$this->validationErrors; }
  function findById($id) { return array($this->alias => array('Id' => $id)); }
}

function extract_method($file, $name) {
  $source = file_get_contents($file);
  $start = strpos($source, "public function $name(");
  if ($start === false) return null;
  $depth = 0;
  for ($i = strpos($source, '{', $start); $i < strlen($source); $i++) {
    if ($source[$i] == '{') $depth++;
    else if ($source[$i] == '}' && --$depth == 0) break;
  }
  return substr($source, $start, $i - $start + 1);
}

// Per-controller private helpers the extracted methods call.
$helpers = array(
  'Groups' => '
    private function requestField($alias, $field) { return null; }
    private function requestAssociatedIds($alias, $assoc) { return null; }
    private function requireMembershipEdit($ids) {}',
  'Tags' => '
    private function requestField($alias, $field) { return null; }
    private function requestAssociatedIds($alias, $assoc) { return null; }
    private function requireEventsView($ids) {}',
);

$serialize = array(
  'Groups' => array('add' => array('message', 'id'), 'edit' => array('message', 'group'), 'key' => 'group'),
  'Tags' => array('add' => array('message', 'id'), 'edit' => array('message', 'tag'), 'key' => 'tag'),
);

$models = array('Groups' => 'Group', 'Tags' => 'Tag');

function run_action($class, $model, $action, $method, $save_result, $validation_errors = array()) {
  global $user, $warnings;
  $user = null;
  $warnings = array();
  $h = new $class();
  $h->request = new FakeRequest($method, array($model => array('Name' => 'x'))); $h->response = new FakeResponse;
  $h->$model = new FakeModel($model);
  $h->$model->saveResult = $save_result;
  $h->$model->saveAssociatedResult = $save_result;
  $h->$model->validationErrors = $validation_errors;
  try {
    if ($action == 'add') $h->add(); else $h->edit(7);
  } catch (Throwable $e) {
    return array(get_class($e), $h, $warnings);
  }
  return array('ok', $h, $warnings);
}

foreach ($models as $name => $model) {
  $file = $root.'/web/api/app/Controller/'.$name.'Controller.php';
  $add = is_file($file) ? extract_method($file, 'add') : null;
  $edit = is_file($file) ? extract_method($file, 'edit') : null;
  if ($add === null or $edit === null) {
    $failures++;
    echo "FAIL - $name: no add()/edit() in $file\n";
    continue;
  }

  $class = $name.'Harness';
  eval('class '.$class.' { public $request, $response, $'.$model.', $vars = array(), $flashed = array();
    function set($v, $value = null) { $this->vars = array_merge($this->vars, is_array($v) ? $v : array($v => $value)); }
    function flash($message, $url = null) { $this->flashed[] = $message; return "FLASH"; }
    function pinRequestId($model, $id) { $model->id = $id; }
    '.$helpers[$name].' '.$add.' '.$edit.' }');

  # add(): a GET is refused, not answered with an HTML page or an unset view.
  list($r, $h, $w) = run_action($class, $model, 'add', 'GET', true);
  check("$name add: GET is a BadRequestException", $r, 'BadRequestException');
  check("$name add: GET sets no response body", $h->vars, array());
  check("$name add: GET does not flash", $h->flashed, array());
  check("$name add: GET raises no PHP warning", $w, array());

  # add(): a successful POST saves and answers JSON with the new id.
  list($r, $h, $w) = run_action($class, $model, 'add', 'POST', true);
  check("$name add: POST saves", $r, 'ok');
  check("$name add: POST answers Saved", isset($h->vars['message']) ? $h->vars['message'] : null, 'Saved');
  check("$name add: POST returns the new id", isset($h->vars['id']) ? $h->vars['id'] : null, 42);
  check("$name add: POST serializes message and id", isset($h->vars['_serialize']) ? $h->vars['_serialize'] : null, $serialize[$name]['add']);
  check("$name add: POST saves the submitted data", $h->$model->saved, array($model => array('Name' => 'x')));
  check("$name add: POST does not flash", $h->flashed, array());
  check("$name add: POST raises no PHP warning", $w, array());

  # add(): a failed save answers 'Error' (or the validation errors) and no id.
  list($r, $h, $w) = run_action($class, $model, 'add', 'POST', false);
  check("$name add: failed save answers Error", isset($h->vars['message']) ? $h->vars['message'] : null, 'Error');
  check("$name add: failed save returns no id", array_key_exists('id', $h->vars) ? $h->vars['id'] : 'missing', null);
  check("$name add: failed save serializes message and id", isset($h->vars['_serialize']) ? $h->vars['_serialize'] : null, $serialize[$name]['add']);
  check("$name add: failed save does not flash", $h->flashed, array());

  list($r, $h, $w) = run_action($class, $model, 'add', 'POST', false, array('Name' => array('required')));
  check("$name add: failed save answers the validation errors", isset($h->vars['message']) ? $h->vars['message'] : null, array('Name' => array('required')));

  # edit(): a GET is refused with a 405, not answered with an empty message.
  list($r, $h, $w) = run_action($class, $model, 'edit', 'GET', true);
  check("$name edit: GET is a MethodNotAllowedException", $r, 'MethodNotAllowedException');
  check("$name edit: GET sets no response body", $h->vars, array());
  check("$name edit: GET does not flash", $h->flashed, array());
  check("$name edit: GET raises no PHP warning", $w, array());

  # edit(): POST and PUT save and answer JSON.
  foreach (array('POST', 'PUT') as $method) {
    list($r, $h, $w) = run_action($class, $model, 'edit', $method, true);
    check("$name edit: $method saves", $r, 'ok');
    check("$name edit: $method answers Saved", isset($h->vars['message']) ? $h->vars['message'] : null, 'Saved');
    check("$name edit: $method serializes message", isset($h->vars['_serialize']) ? $h->vars['_serialize'] : null, $serialize[$name]['edit']);
    check("$name edit: $method saves the submitted data", $h->$model->saved, array($model => array('Name' => 'x')));
    check("$name edit: $method does not flash", $h->flashed, array());
    check("$name edit: $method raises no PHP warning", $w, array());
    if ($serialize[$name]['key']) {
      $key = $serialize[$name]['key'];
      check("$name edit: $method returns the row", isset($h->vars[$key]) ? $h->vars[$key] : null, array($model => array('Id' => 7)));
    }
  }

  # edit(): a failed save answers 'Error' or the validation errors.
  list($r, $h, $w) = run_action($class, $model, 'edit', 'POST', false);
  check("$name edit: failed save answers Error", isset($h->vars['message']) ? $h->vars['message'] : null, 'Error');
  check("$name edit: failed save serializes message", isset($h->vars['_serialize']) ? $h->vars['_serialize'] : null, $serialize[$name]['edit']);
  check("$name edit: failed save does not flash", $h->flashed, array());

  list($r, $h, $w) = run_action($class, $model, 'edit', 'POST', false, array('Name' => array('required')));
  check("$name edit: failed save answers the validation errors", isset($h->vars['message']) ? $h->vars['message'] : null, array('Name' => array('required')));
}

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
