<?php
// EventsController::add() and EncoderTemplatesController::add() only did their work inside
// `if ($this->request->is('post'))` and fell out of it for any other method without setting a
// response. A GET to /api/events/add.json or /api/encoder_templates/add.json then rendered
// the json/add.ctp view that neither controller has and answered 500 (MissingViewException).
// Both now refuse a non-POST with a BadRequestException, as the other add() actions do.
//
// The controllers can't be loaded without CakePHP, so each add() is taken from the source and
// run against stubs.
// Run as: php tests/php/test_api_events_encoder_add_non_post.php [root]   (root defaults to the repo)

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
if (!function_exists('__')) { function __($s) { return $s; } }

class FakeRequest {
  public $data;
  private $method;
  function __construct($method, $data = array()) { $this->method = $method; $this->data = $data; }
  function is($type) { return in_array(strtolower($this->method), array_map('strtolower', (array)$type)); }
}

class FakeModel {
  public $id = null;
  public $validationErrors = array();
  function create() { $this->id = false; }
  function save($data = null) { $this->id = 42; return true; }
  function validates() { return true; }
}

class FakeUser {
  function canEdit($area) { return true; }
  function System() { return 'Edit'; }
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

function run_add($class, $model, $method) {
  global $user, $warnings;
  $user = new FakeUser();
  $warnings = array();
  $h = new $class();
  $h->request = new FakeRequest($method, array($model => array('MonitorId' => 1, 'Name' => 'x')));
  $h->$model = new FakeModel();
  try {
    $h->add();
  } catch (Throwable $e) {
    return array(get_class($e), $h, $warnings);
  }
  return array('ok', $h, $warnings);
}

foreach (array('Events' => 'Event', 'EncoderTemplates' => 'EncoderTemplate') as $name => $model) {
  $file = $root.'/web/api/app/Controller/'.$name.'Controller.php';
  $method = is_file($file) ? extract_add($file) : null;
  if ($method === null) {
    $failures++;
    echo "FAIL - $name: no add() in $file\n";
    continue;
  }
  $class = $name.'Harness';
  eval('class '.$class.' { public $request, $'.$model.', $vars = array();
    function set($v, $value = null) { $this->vars = array_merge($this->vars, is_array($v) ? $v : array($v => $value)); }
    function requestField($alias, $field) { return isset($this->request->data[$alias][$field]) ? $this->request->data[$alias][$field] : null; }
    function requireMonitorView($id) {}
    function pinRequestId($model, $id) { $model->id = $id; }
    '.$method.' }');

  foreach (array('GET', 'PUT', 'DELETE') as $verb) {
    list($r, $h, $w) = run_add($class, $model, $verb);
    check("$name: $verb is a BadRequestException", $r, 'BadRequestException');
    check("$name: $verb sets no response", $h->vars, array());
  }

  list($r, $h, $w) = run_add($class, $model, 'POST');
  check("$name: POST still saves", $r, 'ok');
  check("$name: POST answers Saved", isset($h->vars['message']) ? $h->vars['message'] : null, 'Saved');
  check("$name: POST returns the new id", isset($h->vars['id']) ? $h->vars['id'] : null, 42);
  check("$name: POST raises no PHP warning", $w, array());
}

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
