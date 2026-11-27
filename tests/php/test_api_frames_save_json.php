<?php
// The frames API answered a successful add or edit with an HTML flash page, and a failed save
// or a GET fell through to a view that doesn't exist (there are no Frames views), so the
// client got a 500. add() and edit() now answer {"message": ...} and refuse other methods.
//
// FramesController can't be loaded without CakePHP, so add() and edit() are taken from the
// source and run against stubs.
// Run as: php tests/php/test_api_frames_save_json.php [root]   (root defaults to the repo)

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$file = $root.'/web/api/app/Controller/FramesController.php';
if (!is_file($file)) {
  fwrite(STDERR, "no such controller: $file\n");
  exit(1);
}

if (!class_exists('BadRequestException')) { class BadRequestException extends Exception {} }
if (!class_exists('UnauthorizedException')) { class UnauthorizedException extends Exception {} }
if (!class_exists('NotFoundException')) { class NotFoundException extends Exception {} }
if (!function_exists('__')) { function __($s) { return $s; } }

class FakeResponse { public $status = 200; function statusCode($c = null) { if ($c) $this->status = $c; return $this->status; } }
class FakeRequest {
  public $data;
  private $method;
  function __construct($method, $data = array()) { $this->method = $method; $this->data = $data; }
  function is($type) { return in_array(strtolower($this->method), array_map('strtolower', (array)$type)); }
}

class FakeSource {
  public $model;
  function create($model, $fields, $values) { return $model->saveResult; }
  function value($v, $type) { return "'".$v."'"; }
}

class FakeEvent {
  function find($type, $options = array()) { return array(); }
}

class FakeFrame {
  public $saveResult = true;
  public $validationErrors = array();
  public $Event;
  private $source;
  function __construct() { $this->Event = new FakeEvent(); $this->source = new FakeSource(); }
  function set($columns) {}
  function validates() {
    if (!$this->saveResult) $this->validationErrors = array('Score' => array('invalid'));
    return $this->saveResult;
  }
  function getDataSource() { return $this->source; }
  function getColumnType($field) { return 'integer'; }
  function updateAll($fields, $conditions) { return $this->saveResult; }
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

eval('class FramesHarness { public $request, $response, $Frame, $vars = array(), $flashed = array(), $columns = array();
  function set($v, $value = null) { $this->vars = array_merge($this->vars, is_array($v) ? $v : array($v => $value)); }
  function flash($message, $url = null) { $this->flashed[] = $message; return "FLASH"; }
  function requireRequestEventEdit() {}
  function requireFrameEdit($eventId, $frameId) {}
  function findFrame($eventId, $frameId) { return array(); }
  function requestColumns($exclude = array()) { return array_diff_key($this->columns, array_flip($exclude)); }
  '.$add.' '.$edit.' }');

function run($action, $method, $save_result, $columns = array('EventId' => 5, 'FrameId' => 3, 'Score' => 10)) {
  global $user;
  $user = null;
  $h = new FramesHarness();
  $h->request = new FakeRequest($method, array('Frame' => $columns)); $h->response = new FakeResponse;
  $h->Frame = new FakeFrame();
  $h->Frame->saveResult = $save_result;
  $h->columns = $columns;
  try {
    if ($action == 'add') $h->add(); else $h->edit(5, 3);
  } catch (Throwable $e) {
    return array(get_class($e), $h);
  }
  return array('ok', $h);
}

list($r, $h) = run('add', 'POST', true);
check('add: POST save answers Saved', isset($h->vars['message']) ? $h->vars['message'] : null, 'Saved');
check('add: POST save serializes message', isset($h->vars['_serialize']) ? $h->vars['_serialize'] : null, array('message'));
check('add: POST save does not flash an HTML page', $h->flashed, array());
list($r, $h) = run('add', 'POST', false);
check('add: failed save answers the errors', isset($h->vars['message']) ? $h->vars['message'] : null, array('Score' => array('invalid')));
check('add: failed save serializes message', isset($h->vars['_serialize']) ? $h->vars['_serialize'] : null, array('message'));
list($r, $h) = run('add', 'GET', true);
check('add: GET is a BadRequestException', $r, 'BadRequestException');

list($r, $h) = run('edit', 'PUT', true);
check('edit: PUT save answers Saved', isset($h->vars['message']) ? $h->vars['message'] : null, 'Saved');
check('edit: PUT save does not flash an HTML page', $h->flashed, array());
list($r, $h) = run('edit', 'PUT', false);
check('edit: failed save answers Error', isset($h->vars['message']) ? $h->vars['message'] : null, 'Error');
check('edit: failed save serializes message', isset($h->vars['_serialize']) ? $h->vars['_serialize'] : null, array('message'));
list($r, $h) = run('edit', 'PUT', true, array('EventId' => 5, 'FrameId' => 3));
check('edit: PUT with only key fields says nothing was saved', isset($h->vars['message']) ? $h->vars['message'] : null, 'No fields to save');
list($r, $h) = run('edit', 'GET', true);
check('edit: GET is a BadRequestException', $r, 'BadRequestException');

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
