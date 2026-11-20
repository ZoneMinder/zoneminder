<?php
// The zone presets and logs APIs answered a successful add or edit with an HTML flash page
// instead of JSON (and add never returned the new id), and a GET to add/edit fell through to
// a view that doesn't exist. add() and edit() now answer JSON and refuse other methods.
//
// The controllers can't be loaded without CakePHP, so add() and edit() are taken from the
// source and run against stubs.
// Run as: php tests/php/test_api_zonepreset_log_save_json.php [root]   (root defaults to the repo)

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
if (!defined('ZM_LOG_INJECT')) define('ZM_LOG_INJECT', false);

class FakeRequest {
  public $data;
  private $method;
  function __construct($method, $data = array()) { $this->method = $method; $this->data = $data; }
  function is($type) { return in_array(strtolower($this->method), array_map('strtolower', (array)$type)); }
}

class FakeModel {
  public $id = null;
  public $primaryKey = 'Id';
  public $validationErrors = array();
  public $saveResult = true;
  function create() { $this->id = false; }
  function exists($id) { return true; }
  function find($type, $options = array()) { return array(); }
  function save($data = null) {
    if ($this->saveResult) { if (!$this->id) $this->id = 42; }
    else $this->validationErrors = array('Name' => array('required'));
    return $this->saveResult;
  }
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

function run($class, $model, $action, $method, $save_result) {
  global $user;
  $user = null;
  $h = new $class();
  $h->request = new FakeRequest($method, array($model => array('Name' => 'x')));
  $h->$model = new FakeModel();
  $h->$model->saveResult = $save_result;
  try {
    if ($action == 'add') $h->add(); else $h->edit(7);
  } catch (Throwable $e) {
    return array(get_class($e), $h);
  }
  return array('ok', $h);
}

foreach (array('ZonePresets' => 'ZonePreset', 'Logs' => 'Log') as $name => $model) {
  $file = $root.'/web/api/app/Controller/'.$name.'Controller.php';
  $add = is_file($file) ? extract_method($file, 'add') : null;
  $edit = is_file($file) ? extract_method($file, 'edit') : null;
  if ($add === null or $edit === null) {
    $failures++;
    echo "FAIL - $name: no add()/edit() in $file\n";
    continue;
  }
  $class = $name.'Harness';
  eval('class '.$class.' { public $request, $'.$model.', $vars = array(), $flashed = array();
    function set($v, $value = null) { $this->vars = array_merge($this->vars, is_array($v) ? $v : array($v => $value)); }
    function flash($message, $url = null) { $this->flashed[] = $message; return "FLASH"; }
    function pinRequestId($model, $id) { $model->id = $id; }
    '.$add.' '.$edit.' }');

  list($r, $h) = run($class, $model, 'add', 'POST', true);
  check("$name add: POST save answers Saved", isset($h->vars['message']) ? $h->vars['message'] : null, 'Saved');
  check("$name add: POST save returns the new id", isset($h->vars['id']) ? $h->vars['id'] : null, 42);
  check("$name add: POST save serializes message and id", isset($h->vars['_serialize']) ? $h->vars['_serialize'] : null, array('message', 'id'));
  check("$name add: POST save does not flash an HTML page", $h->flashed, array());
  list($r, $h) = run($class, $model, 'add', 'POST', false);
  check("$name add: failed save returns a null id", array_key_exists('id', $h->vars) ? $h->vars['id'] : 'missing', null);
  list($r, $h) = run($class, $model, 'add', 'GET', true);
  check("$name add: GET is a BadRequestException", $r, 'BadRequestException');

  list($r, $h) = run($class, $model, 'edit', 'PUT', true);
  check("$name edit: PUT save answers Saved", isset($h->vars['message']) ? $h->vars['message'] : null, 'Saved');
  check("$name edit: PUT save serializes message", isset($h->vars['_serialize']) ? $h->vars['_serialize'] : null, array('message'));
  check("$name edit: PUT save does not flash an HTML page", $h->flashed, array());
  list($r, $h) = run($class, $model, 'edit', 'PUT', false);
  check("$name edit: failed save answers the errors", isset($h->vars['message']) ? $h->vars['message'] : null, array('Name' => array('required')));
  list($r, $h) = run($class, $model, 'edit', 'GET', true);
  check("$name edit: GET is a BadRequestException", $r, 'BadRequestException');
}

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
