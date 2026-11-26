<?php
// UsersController (API): behaviour of add(), edit(), index(), delete() that was wrong:
//  - with authentication off $user is null and index/add/edit/delete called ->System() on it
//  - edit() left $message undefined (PHP warning, null body) for a non-POST request
//  - validation failures answered 200; NotificationsController answers 400
//  - an empty Password in an admin edit failed validation; the web UI (includes/actions/user.php)
//    treats an empty Password as "unchanged"
//  - add() without POST answered 200 'Add without post data'
//  - add() let an Id in the body pick the row, so it updated that user instead of creating
//    one, and it never answered the new user's id
//
// UsersController can't be loaded without CakePHP, so the methods are taken from the source
// and run against stubs.
// Run as: php tests/php/test_api_users_edit_add.php

error_reporting(E_ALL);
$warnings = array();
set_error_handler(function($no, $str) use (&$warnings) { $warnings[] = $str; return true; });

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

$source = file_get_contents(__DIR__.'/../../web/api/app/Controller/UsersController.php');
$methods = '';
foreach (array('index', 'add', 'edit', 'delete') as $name) {
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
class MethodNotAllowedException extends Exception {}
function __($s) { return $s; }
define('ZM_USER_SELF_EDIT', false);

class FakeUser {
  public $system;
  function __construct($system) { $this->system = $system; }
  function System() { return $this->system; }
  function Id() { return 1; }
}
class FakeModel {
  public $id, $primaryKey = 'Id', $alias = 'User', $recursive, $saved = null, $valid = true;
  public $validationErrors = array('Password' => array('A password is required'));
  function exists($id = null) { return true; }
  function create() {}
  function save($data, $v = true, $f = array()) {
    $this->saved = $data;
    // Like Model::save(), the primary key in the data picks the row.
    if (isset($data['User']['Id'])) $this->id = $data['User']['Id'];
    if ($this->valid and !$this->id) $this->id = 42;
    return $this->valid;
  }
  function validates() { return $this->valid; }
  function delete() { return true; }
  function deleteAll($conditions, $cascade = true) { return true; }
}
class FakeRequest {
  public $method, $data = array();
  function __construct($method, $data) { $this->method = $method; $this->data = $data; }
  function is($m) { return strtolower($m) == strtolower($this->method); }
  function allowMethod() {
    foreach (func_get_args() as $m) if ($this->is($m)) return true;
    throw new MethodNotAllowedException();
  }
}
class FakeResponse { public $code = 200; function statusCode($c = null) { if ($c) $this->code = $c; return $this->code; } }
class FakePaginator { function paginate($m) { return array(); } }

eval('class UsersHarness { public $request, $response, $User, $UserPreference, $Paginator, $vars;
  function set($v) { $this->vars = $v; } function loadModel($m) { $this->$m = new FakeModel(); }
  // Copy of AppController::pinRequestId().
  function pinRequestId($model, $id) {
    $alias = $model->alias;
    $key = $model->primaryKey;
    if (isset($this->request->data[$alias]) and is_array($this->request->data[$alias])) {
      unset($this->request->data[$alias][$key]);
    }
    unset($this->request->data[$key]);
    $model->id = $id;
  } '.$methods.' }');

function run($action, $system, $method, $data, $valid = true, $arg = 2) {
  global $user;
  $user = ($system === null) ? null : new FakeUser($system);
  $h = new UsersHarness();
  $h->request = new FakeRequest($method, $data);
  $h->response = new FakeResponse();
  $h->User = new FakeModel();
  $h->User->valid = $valid;
  $h->Paginator = new FakePaginator();
  try {
    if ($action == 'index') $h->index(); else $h->$action($arg);
  } catch (Exception $e) {
    return array(get_class($e), $h);
  }
  return array('ok', $h);
}

function runAdd($system, $method, $data, $valid = true) {
  global $user;
  $user = ($system === null) ? null : new FakeUser($system);
  $h = new UsersHarness();
  $h->request = new FakeRequest($method, $data);
  $h->response = new FakeResponse();
  $h->User = new FakeModel();
  $h->User->valid = $valid;
  try { $h->add(); } catch (Exception $e) { return array(get_class($e), $h); }
  return array('ok', $h);
}

list($r, $h) = run('index', null, 'GET', array());
check('index with auth off works', $r, 'ok');
list($r, $h) = run('edit', null, 'POST', array('User' => array('Username' => 'x')));
check('edit with auth off works', array($r, $h->vars['message']), array('ok', 'Saved'));
list($r, $h) = run('delete', null, 'POST', array());
check('delete with auth off works', $r, 'ok');
list($r, $h) = runAdd(null, 'POST', array('User' => array('Username' => 'x', 'Password' => 'y')));
check('add with auth off works', array($r, $h->vars['message']), array('ok', 'Saved'));

$warnings = array();
list($r, $h) = run('edit', 'Edit', 'GET', array());
check('edit with GET is refused, not a warning', array($r, $warnings), array('MethodNotAllowedException', array()));

list($r, $h) = run('edit', 'Edit', 'POST', array('User' => array('Username' => 'x')), false);
check('edit validation failure is 400', $h->response->code, 400);
check('edit validation failure returns the errors', $h->vars['message'], $h->User->validationErrors);
list($r, $h) = run('edit', 'Edit', 'POST', array('User' => array('Username' => 'x')));
check('edit success is 200', array($h->response->code, $h->vars['message']), array(200, 'Saved'));

list($r, $h) = run('edit', 'Edit', 'POST', array('User' => array('Username' => 'x', 'Password' => '')));
check('empty Password is dropped on edit', array_key_exists('Password', $h->User->saved['User']), false);
list($r, $h) = run('edit', 'Edit', 'POST', array('User' => array('Password' => 'abc')));
check('non-empty Password is kept on edit', $h->User->saved['User']['Password'], 'abc');

list($r, $h) = runAdd('Edit', 'POST', array('User' => array('Username' => 'x')), false);
check('add validation failure is 400', $h->response->code, 400);
list($r, $h) = runAdd('Edit', 'GET', array());
check('add without POST is 400', $r, 'BadRequestException');

list($r, $h) = runAdd('Edit', 'POST', array('User' => array('Id' => 1, 'Username' => 'x', 'Password' => 'y')));
check('add ignores an Id in the body', array_key_exists('Id', $h->User->saved['User']), false);
check('add answers the new id', array($h->vars['message'] ?? null, $h->vars['id'] ?? null), array('Saved', 42));
check('add serializes message and id', $h->vars['_serialize'] ?? null, array('message', 'id'));

echo $failures ? "$failures FAILED\n" : "all passed\n";
exit($failures ? 1 : 0);
