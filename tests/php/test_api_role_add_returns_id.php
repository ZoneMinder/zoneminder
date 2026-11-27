<?php
// RolesController::add() answered {"message":"Saved"} and never said which role it had
// created, so a client had to list every role to find the new one. The other add() actions
// (Users, Servers, Groups, ...) answer {message, id}. add() now serializes the new id, and
// null when the save fails.
//
// The controller can't be loaded without CakePHP, so add() is taken from the source and run
// against stubs.
// Run as: php tests/php/test_api_role_add_returns_id.php [root]   (root defaults to the repo)

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
  function is($type) { return strtolower($type) == strtolower($this->method); }
}

class FakeResponse {
  public $status = 200;
  function statusCode($code = null) { if ($code) $this->status = $code; return $this->status; }
}

class FakeRole {
  public $id = null;
  public $alias = 'Role';
  public $primaryKey = 'Id';
  public $saveResult = true;
  public $validationErrors = array();
  function create() { $this->id = false; }
  function save($data = null) {
    if ($this->saveResult) $this->id = 17;
    return $this->saveResult;
  }
  function validates() { return !$this->validationErrors; }
}

$source = file_get_contents($root.'/web/api/app/Controller/RolesController.php');
$start = strpos($source, 'public function add(');
if ($start === false) {
  echo "FAIL - no add() in RolesController.php\n";
  exit(1);
}
$depth = 0;
for ($i = strpos($source, '{', $start); $i < strlen($source); $i++) {
  if ($source[$i] == '{') $depth++;
  else if ($source[$i] == '}' && --$depth == 0) break;
}
$method = substr($source, $start, $i - $start + 1);

eval('class RolesHarness { public $request, $Role, $response, $vars = array();
  function set($v) { $this->vars = array_merge($this->vars, (array)$v); }
  function pinRequestId($model, $id) { $model->id = $id; }
  '.$method.' }');

function run_add($method, $save_result) {
  global $user, $warnings;
  $user = null;
  $warnings = array();
  $h = new RolesHarness();
  $h->request = new FakeRequest($method, array('Role' => array('Name' => 'Guards')));
  $h->response = new FakeResponse();
  $h->Role = new FakeRole();
  $h->Role->saveResult = $save_result;
  if (!$save_result) $h->Role->validationErrors = array('Name' => array('A role name is required'));
  try {
    $h->add();
  } catch (Throwable $e) {
    return array(get_class($e), $h, $warnings);
  }
  return array('ok', $h, $warnings);
}

list($r, $h, $w) = run_add('POST', true);
check('POST save answers ok', $r, 'ok');
check('POST save answers Saved', isset($h->vars['message']) ? $h->vars['message'] : null, 'Saved');
check('POST save returns the new id', array_key_exists('id', $h->vars) ? $h->vars['id'] : 'missing', 17);
check('POST save serializes message and id', isset($h->vars['_serialize']) ? $h->vars['_serialize'] : null, array('message', 'id'));
check('POST save answers 200', $h->response->status, 200);
check('POST save raises no PHP warning', $w, array());

list($r, $h, $w) = run_add('POST', false);
check('rejected save answers 400', $h->response->status, 400);
check('rejected save returns no id', array_key_exists('id', $h->vars) ? $h->vars['id'] : 'missing', null);
check('rejected save reports the errors', isset($h->vars['message']) ? $h->vars['message'] : null, array('Name' => array('A role name is required')));
check('rejected save serializes message and id', isset($h->vars['_serialize']) ? $h->vars['_serialize'] : null, array('message', 'id'));

list($r, $h, $w) = run_add('GET', true);
check('GET is a BadRequestException', $r, 'BadRequestException');

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
