<?php
// RolesController (API) add()/edit(): a failed save (e.g. a validation error) answered 200,
// and add() without POST answered 200 'Add without post data'. UsersController and
// NotificationsController answer 400. Methods are taken from the source and run against stubs.
// Run as: php tests/php/test_api_roles_add_edit_status.php [repo root]
$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}
$source = file_get_contents($root.'/web/api/app/Controller/RolesController.php');
$methods = '';
foreach (array('add', 'edit') as $name) {
  $start = strpos($source, 'public function '.$name.'(');
  $open = strpos($source, '{', $start);
  $depth = 0;
  for ($i = $open; $i < strlen($source); $i++) {
    if ($source[$i] == '{') $depth++;
    else if ($source[$i] == '}' && --$depth == 0) break;
  }
  $methods .= substr($source, $start, $i - $start + 1)."\n";
}
class BadRequestException extends Exception {}
class UnauthorizedException extends Exception {}
class NotFoundException extends Exception {}
function __($s) { return $s; }
class FakeModel {
  public $id, $primaryKey = 'Id', $alias = 'Role', $saveOk = false;
  public $validationErrors = array('Name' => array('A role name is required'));
  function exists($id = null) { return true; }
  function create() {}
  function save($d) { return $this->saveOk; }
  function validates() { return false; }
  function read($a, $b) { return array(); }
}
class FakeResponse { public $status = 200; function statusCode($c = null) { if ($c) $this->status = $c; return $this->status; } }
class FakeRequest {
  public $data = array(); public $method;
  function __construct($m) { $this->method = $m; }
  function is($t) { return strtolower($t) == strtolower($this->method); }
}
eval('class Harness {
  public $Role, $request, $response, $set = array();
  function __construct($method) { $this->Role = new FakeModel; $this->request = new FakeRequest($method); $this->response = new FakeResponse; }
  function pinRequestId($m, $id) { $m->id = $id; }
  function set($a) { $this->set = $a; }
  '.$methods.'
}');

$user = null;
$h = new Harness('POST');
$h->add();
check('add: failed save answers 400', $h->response->status, 400);
check('add: failed save reports the validation errors', $h->set['message'], $h->Role->validationErrors);

$h = new Harness('POST');
$h->Role->saveOk = true;
$h->add();
check('add: good save answers 200', $h->response->status, 200);
check('add: good save message', $h->set['message'], 'Saved');

$h = new Harness('GET');
$threw = false;
try { $h->add(); } catch (BadRequestException $e) { $threw = true; }
check('add: non-POST is a bad request', $threw, true);

$h = new Harness('PUT');
$h->edit(3);
check('edit: failed save answers 400', $h->response->status, 400);

$h = new Harness('PUT');
$h->Role->saveOk = true;
$h->edit(3);
check('edit: good save answers 200', $h->response->status, 200);

exit($failures ? 1 : 0);
