<?php
// PUT/POST /api/monitors/edit/<id> is the only method that may save. A GET used to fall
// through and call save() with an empty body, answering 200 with {"message":"Error ..."}.
// edit() now throws BadRequestException for anything that is not POST or PUT.
//
// MonitorsController can't be loaded without CakePHP, so edit() is taken from the source
// and run against stubs.
// Run as: php tests/php/test_api_monitors_edit_method.php [root]

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$file = $root.'/web/api/app/Controller/MonitorsController.php';
if (!is_file($file)) {
  echo "FAIL - controller not found at $file\n";
  exit(1);
}
$source = file_get_contents($file);

$start = strpos($source, 'public function edit(');
if ($start === false) {
  echo "FAIL - edit() not found in $file\n";
  exit(1);
}
$open = strpos($source, '{', $start);
$depth = 0;
for ($i = $open; $i < strlen($source); $i++) {
  if ($source[$i] == '{') $depth++;
  else if ($source[$i] == '}' && --$depth == 0) break;
}
$method = substr($source, $start, $i - $start + 1);

// The real ZM\Monitor is not loaded; the stub below stands in for it, so point the
// require_once at an empty file instead of includes/Monitor.php.
$scratch = '/tmp/numes-tests';
if (!is_dir($scratch)) @mkdir($scratch, 0777, true);
$empty = tempnam($scratch, 'medit');
if ($empty === false) {
  echo "FAIL - cannot create scratch file under $scratch\n";
  exit(1);
}
$method = str_replace('__DIR__ .\'/../../../includes/Monitor.php\'', var_export($empty, true), $method);

if (!class_exists('NotFoundException')) { class NotFoundException extends Exception {} }
if (!class_exists('UnauthorizedException')) { class UnauthorizedException extends Exception {} }
if (!class_exists('BadRequestException')) { class BadRequestException extends Exception {} }
if (!function_exists('__')) { function __($s) { return $s; } }

eval('namespace ZM; class Monitor {
  static $constructed = 0;
  static $zmcCalls = array();
  static $controlCalls = array();
  private $row;
  function __construct($row) {
    $this->row = is_array($row) ? $row : array();
    self::$constructed++;
  }
  function canEdit() { return true; }
  function commandFieldChangesNotAllowed($fields) { return array(); }
  function Type() { return isset($this->row["Type"]) ? $this->row["Type"] : "Local"; }
  function Controllable() { return false; }
  function Capturing() { return "None"; }
  function Deleted() { return false; }
  function zmcControl($cmd) { self::$zmcCalls[] = $cmd; }
  function sendControlCommand($cmd) { self::$controlCalls[] = $cmd; }
}');

class FakeModel {
  public $id;
  public $saveResult;
  public $saveCalls = 0;
  public $savedData = null;
  public $invalidFieldsCalls = 0;
  public $row = array('Id' => 7, 'Name' => 'Front', 'Type' => 'Local');
  function __construct($saveResult) { $this->saveResult = $saveResult; }
  function exists($id = null) { return true; }
  function find($type = 'first', $options = array()) {
    return array('Monitor' => $this->row);
  }
  function save($data = null) {
    $this->saveCalls++;
    $this->savedData = $data;
    return $this->saveResult;
  }
  function invalidFields() {
    $this->invalidFieldsCalls++;
    return array('Name' => 'is required');
  }
}

class FakeRequest {
  public $data = array();
  public $method;
  function __construct($method) { $this->method = $method; }
  function is($types) { return in_array($this->method, (array)$types, true); }
}

// pinRequestId() lives in AppController; the stub mirrors what edit() relies on.
class FakeController {
  public $Monitor;
  public $request;
  public $out;
  public $pinCalls = 0;
  function set($a) { $this->out = $a; }
  function requestCommandFields() { return array(); }
  function resizeZones($id, $old, $new) {}
  function pinRequestId($model, $id) {
    $this->pinCalls++;
    $model->id = $id;
  }
}

eval('class FakeControllerWithEdit extends FakeController { '.$method.' }');

// GET must be refused before anything is saved.
$c = new FakeControllerWithEdit();
$c->Monitor = new FakeModel(false);
$c->request = new FakeRequest('get');
$c->request->data = array();

$thrown = 'none';
try {
  $c->edit(7);
} catch (BadRequestException $e) {
  $thrown = 'BadRequestException';
} catch (Exception $e) {
  $thrown = get_class($e);
}
check('GET edit throws BadRequestException', $thrown, 'BadRequestException');
check('GET edit does not call save()', $c->Monitor->saveCalls, 0);
check('GET edit does not pin the request id', $c->pinCalls, 0);

// PUT with data saves once and answers Saved.
$c = new FakeControllerWithEdit();
$c->Monitor = new FakeModel(true);
$c->request = new FakeRequest('put');
$c->request->data = array('Monitor' => array('Id' => 99, 'Name' => 'Front door'));

$thrown = 'none';
try {
  $c->edit(7);
} catch (Exception $e) {
  $thrown = get_class($e);
}
check('PUT edit does not throw', $thrown, 'none');
check('PUT edit answers Saved', isset($c->out['message']) ? $c->out['message'] : null, 'Saved');
check('PUT edit calls save() once', $c->Monitor->saveCalls, 1);
check('PUT edit saves the request data', $c->Monitor->savedData, $c->request->data);
check('PUT edit serializes message', isset($c->out['_serialize']) ? $c->out['_serialize'] : null, array('message'));

@unlink($empty);
echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
