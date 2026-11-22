<?php
// POST /api/monitors used to answer only {"message":"Saved"} with no id, and a non-POST
// request fell through to a view template that echoed undefined variables (warnings, 200).
// add() now refuses non-POST with BadRequestException and serializes both message and id.
//
// MonitorsController can't be loaded without CakePHP, so add() is taken from the source and
// run against stubs. require_once targets are satisfied by empty stubs in a scratch tree.
// Run as: php tests/php/test_api_monitors_add_json.php [root]

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

$root = isset($argv[1]) ? rtrim($argv[1], '/') : realpath(__DIR__.'/../..');
$controllerPath = $root.'/web/api/app/Controller/MonitorsController.php';
if (!is_file($controllerPath)) {
  fwrite(STDERR, "missing $controllerPath\n");
  exit(2);
}
$source = file_get_contents($controllerPath);

$start = strpos($source, 'public function add(');
if ($start === false) {
  fwrite(STDERR, "add() not found in $controllerPath\n");
  exit(2);
}
$open = strpos($source, '{', $start);
$depth = 0;
for ($i = $open; $i < strlen($source); $i++) {
  if ($source[$i] == '{') $depth++;
  else if ($source[$i] == '}' && --$depth == 0) break;
}
$method = substr($source, $start, $i - $start + 1);

if (!class_exists('BadRequestException')) { class BadRequestException extends Exception {} }
if (!class_exists('UnauthorizedException')) { class UnauthorizedException extends Exception {} }
if (!function_exists('__')) { function __($s) { return $s; } }

eval('namespace ZM;
class Monitor {
  private $row;
  function __construct($row = array()) { $this->row = is_array($row) ? $row : array(); }
  function commandFieldChangesNotAllowed($fields) { return array(); }
  function Capturing() { return isset($this->row["Capturing"]) ? $this->row["Capturing"] : "None"; }
  function Type() { return isset($this->row["Type"]) ? $this->row["Type"] : "Ftp"; }
  function zmcControl($command) {}
}
class Zone {
  static $saves = array();
  function save($data) { self::$saves[] = $data; return true; }
}');

class FakeMonitorModel {
  public $alias = 'Monitor';
  public $primaryKey = 'Id';
  public $id = null;
  public $saveResult = true;
  public $validatesResult = true;
  public $validationErrors = array();
  public $createCalls = 0;
  public $saveCalls = 0;
  public $findCalls = 0;
  function create() { $this->createCalls++; }
  function save($data) {
    $this->saveCalls++;
    if (!$this->saveResult) return false;
    $this->id = 77;
    return true;
  }
  function find($type, $options) {
    $this->findCalls++;
    return array('Monitor' => array('Id' => 77, 'Capturing' => 'None', 'Type' => 'Ftp'));
  }
  function validates() { return $this->validatesResult; }
}

class FakeResponse { public $status = 200; function statusCode($c = null) { if ($c) $this->status = $c; return $this->status; } }

class FakeRequest {
  public $post;
  public $data;
  function __construct($post, $data) { $this->post = $post; $this->data = $data; }
  function is($type) { return $type === 'post' ? $this->post : false; }
}

// The method's require_once paths are relative to its own file, so the class lives in a
// scratch tree that also holds empty Monitor.php and Zone.php stubs.
$scratch = '/tmp/numes-tests/madd-run-'.getmypid();
$ctrlDir = $scratch.'/web/api/app/Controller';
if (!is_dir($ctrlDir)) mkdir($ctrlDir, 0777, true);
if (!is_dir($scratch.'/web/includes')) mkdir($scratch.'/web/includes', 0777, true);
file_put_contents($scratch.'/web/includes/Monitor.php', "<?php\n");
file_put_contents($scratch.'/web/includes/Zone.php', "<?php\n");

$classBody = "class FakeController {\n"
  ."  public \$Monitor;\n"
  ."  public \$request;\n"
  ."  public \$response;\n"
  ."  public \$out = null;\n"
  ."  public \$setCalls = 0;\n"
  ."  function set(\$data) { \$this->setCalls++; \$this->out = \$data; }\n"
  ."  function requestCommandFields() { return array(); }\n"
  ."  protected function pinRequestId(\$model, \$id) { \$model->id = \$id; }\n"
  .$method."\n"
  ."}\n";
file_put_contents($ctrlDir.'/FakeMonitorsController.php', "<?php\n".$classBody);
require $ctrlDir.'/FakeMonitorsController.php';

// POST that saves: message "Saved", the new id, both serialized.
$c = new FakeController();
$c->Monitor = new FakeMonitorModel();
$c->Monitor->saveResult = true;
$c->request = new FakeRequest(true, array('Monitor' => array('Name' => 'Front door')));
$user = null;
$c->add();
check('POST save answers message Saved', $c->out['message'], 'Saved');
check('POST save answers the new id', array_key_exists('id', $c->out) ? $c->out['id'] : 'missing', 77);
check('POST save serializes message', in_array('message', $c->out['_serialize'], true), true);
check('POST save serializes id', in_array('id', $c->out['_serialize'], true), true);

// POST that fails validation: the errors are the message and id is null.
$c = new FakeController();
$c->Monitor = new FakeMonitorModel();
$c->Monitor->saveResult = false;
$c->Monitor->validatesResult = false;
$c->Monitor->validationErrors = array('Name' => array('This field cannot be left blank'));
$c->request = new FakeRequest(true, array('Monitor' => array('Name' => '')));
$c->response = new FakeResponse();
$user = null;
$c->add();
check('failed POST answers the validation errors', $c->out['message'], $c->Monitor->validationErrors);
check('failed POST answers 400', $c->response->status, 400);
check('failed POST answers id null', array_key_exists('id', $c->out) ? $c->out['id'] : 'missing', null);
check('failed POST serializes id', in_array('id', $c->out['_serialize'], true), true);

// GET: refused before anything is created or set.
$c = new FakeController();
$c->Monitor = new FakeMonitorModel();
$c->request = new FakeRequest(false, array());
$user = null;
$thrown = false;
$message = null;
try {
  $c->add();
} catch (BadRequestException $e) {
  $thrown = true;
  $message = $e->getMessage();
}
check('GET is refused with BadRequestException', $thrown, true);
check('GET message names the method', is_string($message) && strpos($message, 'Invalid method') !== false, true);
check('GET does not set a response', $c->out, null);
check('GET does not set anything', $c->setCalls, 0);
check('GET does not create a monitor', $c->Monitor->createCalls, 0);
check('GET does not save a monitor', $c->Monitor->saveCalls, 0);

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
