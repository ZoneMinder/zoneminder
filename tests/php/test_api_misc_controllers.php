<?php
// REST API controllers for small global tables: Roles, Manufacturers,
// CameraModels, EncoderTemplates, Tags and UserPreference.
//
//  - Roles add()/edit() saved a Role[Id] in the body instead of the row in the URL, and
//    every action called $user->System() on a null user (authentication off).
//  - Manufacturers/CameraModels/EncoderTemplates index() ignored named filters.
//  - Manufacturers/CameraModels delete() of a row in use hit a foreign key error (500),
//    and deleting a manufacturer left its models behind.
//  - Tags index() with Name:x and Events.Id:n made an ambiguous column.
//  - UserPreference add() read an undefined property and an undefined array key.
//
// The controllers can't be loaded without CakePHP, so their source is run against stub
// models and the real FilterComponent.
//
// Run as: php tests/php/test_api_misc_controllers.php

$failures = 0;
$passes = 0;

function check($name, $got, $want) {
  global $failures, $passes;
  if ($got === $want) {
    $passes++;
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n";
  }
}

set_error_handler(function($no, $str) { throw new ErrorException($str); }, E_ALL & ~E_DEPRECATED);

class App { static function uses() {} }
class Component {}
class HttpException extends Exception {}
class BadRequestException extends HttpException {}
class UnauthorizedException extends HttpException {}
class ForbiddenException extends HttpException {}
class NotFoundException extends HttpException {}
class InternalErrorException extends HttpException {}
function __($s) { $args = func_get_args(); return count($args) > 1 ? vsprintf($s, array_slice($args, 1)) : $s; }

eval('namespace ZM; function Debug() {} function Warning() {} function Error() {} function Info() {}');

$queue = array();
$queries = array();
function dbFetchOne($sql, $col = false, $params = null) { global $queue; return array_shift($queue); }
function dbQuery($sql, $params = null) { global $queries; $queries[] = $sql; return true; }
function loadConfigFiles() { global $fileConfig; return $fileConfig; }
$fileConfig = array();
define('ZM_PATH_WEB', sys_get_temp_dir());

require_once(__DIR__.'/../../web/includes/config_values.php');
require_once(__DIR__.'/../../web/api/app/Controller/Component/FilterComponent.php');

$dir = __DIR__.'/../../web/api/app/Controller';
$app = file_get_contents($dir.'/AppController.php');
$helpers = '';
foreach (array('pinRequestId', 'requestField') as $name) {
  if (!preg_match('/^  protected function '.$name.'\(.*?^  }\n/ms', $app, $m)) {
    echo "FAIL - could not find $name in AppController.php\n";
    exit(1);
  }
  $helpers .= $m[0];
}
eval('class AppController { public $request; public $response; public $Components; public $Paginator; public $RequestHandler; public $vars = array();
  function set($v) { $this->vars = $v; }
  function viewableMonitorCondition($f) { return array(); }
  '.$helpers.' }');

class StubHandler { function requestedWith() { return false; } }
class StubResponse { public $status = 200; function statusCode($c = null) { if ($c !== null) $this->status = $c; return $this->status; } }
class StubComponents { function load($n) { return new FilterComponent(); } }

class StubModel {
  public $alias, $primaryKey, $id, $recursive, $validationErrors = array();
  public $saved = null, $saveOptions = null, $found = null, $deleted = false, $row = array();
  function __construct($alias, $primaryKey, $columns) { $this->alias = $alias; $this->primaryKey = $primaryKey; $this->columns = $columns; }
  function schema() { return array_fill_keys($this->columns, 1); }
  function exists($id = null) { return true; }
  function create() {}
  function find($type, $options = array()) { $this->found = $options; return $type == 'first' ? $this->row : array(); }
  function save($data, $options = array()) { $this->saved = $data; $this->saveOptions = $options; return true; }
  function delete() { $this->deleted = true; return true; }
  function read($a, $b) { return array(); }
  function validates() { return true; }
}

class StubRequest {
  public $data = array(), $params = array('named' => array()), $query = array(), $method = 'POST';
  function is($t) { return in_array(strtolower($this->method), (array)$t); }
  function allowMethod() {}
  function input() { return null; }
}

function load($controller) {
  global $dir;
  $src = file_get_contents($dir.'/'.$controller.'Controller.php');
  $src = preg_replace('/^<\?php/', '', $src);
  $src = preg_replace('/^(App::uses|require_once)\b.*$/m', '', $src);
  eval($src);
}

function make($controller, $property, $model, $data = array(), $named = array(), $method = 'POST') {
  $class = $controller.'Controller';
  $c = new $class();
  $c->request = new StubRequest();
  $c->request->data = $data;
  $c->request->params['named'] = $named;
  $c->request->method = $method;
  $c->Components = new StubComponents();
  $c->RequestHandler = new StubHandler();
  $c->response = new StubResponse();
  $c->$property = $model;
  return $c;
}

function run($fn) { try { $fn(); } catch (Throwable $e) { echo '# '.get_class($e).': '.$e->getMessage()."\n"; } }

function thrown($fn) {
  try { $fn(); } catch (Throwable $e) { return get_class($e).':'.$e->getCode(); }
  return null;
}

class FakeUser {
  private $system;
  function __construct($system) { $this->system = $system; }
  function System() { return $this->system; }
  function Id() { return 5; }
}

foreach (array('Roles', 'Manufacturers', 'CameraModels', 'EncoderTemplates', 'Tags', 'UserPreference') as $name) load($name);

# --- Roles
global $user;
$user = new FakeUser('Edit');
$m = new StubModel('Role', 'Id', array('Id', 'Name'));
$c = make('Roles', 'Role', $m, array('Role' => array('Id' => '1', 'Name' => 'x')));
run(function() use ($c) { $c->edit('3'); });
check('Roles edit saves the row in the URL, not the body Id', array($m->id, isset($m->saved['Role']['Id'])), array('3', false));

$m = new StubModel('Role', 'Id', array('Id', 'Name'));
$c = make('Roles', 'Role', $m, array('Role' => array('Id' => '1', 'Name' => 'x')));
run(function() use ($c) { $c->add(); });
check('Roles add does not take an Id from the body', array($m->id, isset($m->saved['Role']['Id'])), array(null, false));

$m = new StubModel('Role', 'Id', array('Id', 'Name'));
$c = make('Roles', 'Role', $m, array(), array(), 'GET');
run(function() use ($c) { $c->edit('3'); });
check('Roles edit by GET reports an empty message', (isset($c->vars['message']) ? $c->vars['message'] : null), '');

$user = null;
$m = new StubModel('Role', 'Id', array('Id', 'Name'));
$c = make('Roles', 'Role', $m, array('Role' => array('Name' => 'x')));
check('Roles add works with authentication off', thrown(function() use ($c) { $c->add(); }), null);
$c = make('Roles', 'Role', $m, array('Role' => array('Name' => 'x')));
check('Roles edit works with authentication off', thrown(function() use ($c) { $c->edit('3'); }), null);
$c = make('Roles', 'Role', $m, array(), array(), 'DELETE');
check('Roles delete works with authentication off', thrown(function() use ($c) { $c->delete('3'); }), null);
$user = new FakeUser('Edit');

# --- Index filters
foreach (array(
  array('Manufacturers', 'Manufacturer', 'Name', 'Name', 'Axis'),
  array('CameraModels', 'CameraModel', 'ManufacturerId', 'CameraModel.ManufacturerId', '3'),
  array('EncoderTemplates', 'EncoderTemplate', 'Encoder', 'Encoder', 'libx264'),
) as $t) {
  list($controller, $alias, $field) = array($t[0], $t[1], $t[2]);
  $m = new StubModel($alias, 'Id', array('Id', 'Name', 'ManufacturerId', 'Encoder'));
  $m->Manufacturer = new StubModel('Manufacturer', 'Id', array('Id', 'Name'));
  $c = make($controller, $alias, $m, array(), array($field => $t[4]), 'GET');
  $c->index();
  check("$controller index applies the $field named filter", isset($m->found['conditions']) ? $m->found['conditions'] : null, array($t[3] => $t[4]));
}

# CameraModels index joins Manufacturers, which also has Id and Name
$m = new StubModel('CameraModel', 'Id', array('Id', 'Name', 'ManufacturerId'));
$m->Manufacturer = new StubModel('Manufacturer', 'Id', array('Id', 'Name'));
$c = make('CameraModels', 'CameraModel', $m, array(), array('Name' => 'P3301', 'Id' => '2', 'Manufacturer.Name' => 'Axis'), 'GET');
run(function() use ($c) { $c->index(); });
check('CameraModels index qualifies Name and Id against the joined Manufacturer', isset($m->found['conditions']) ? array_keys($m->found['conditions']) : null, array('CameraModel.Name', 'CameraModel.Id', 'Manufacturer.Name'));

# --- Deleting a manufacturer or model in use
$m = new StubModel('Manufacturer', 'Id', array('Id', 'Name'));
$queue = array('2', '0'); $queries = array();
$c = make('Manufacturers', 'Manufacturer', $m, array(), array(), 'DELETE');
check('Manufacturers delete of one in use is a 409', array(thrown(function() use ($c) { $c->delete('4'); }), $m->deleted), array('HttpException:409', false));

$m = new StubModel('Manufacturer', 'Id', array('Id', 'Name'));
$queue = array('0', '0'); $queries = array();
$c = make('Manufacturers', 'Manufacturer', $m, array(), array(), 'DELETE');
run(function() use ($c) { $c->delete('4'); });
check('Manufacturers delete removes its models too', array($m->deleted, count($queries) == 1 and strpos($queries[0], 'DELETE FROM `Models`') === 0), array(true, true));

$m = new StubModel('CameraModel', 'Id', array('Id', 'Name', 'ManufacturerId'));
$queue = array('0', '1'); $queries = array();
$c = make('CameraModels', 'CameraModel', $m, array(), array(), 'DELETE');
check('CameraModels delete of one in use is a 409', array(thrown(function() use ($c) { $c->delete('4'); }), $m->deleted), array('HttpException:409', false));

$m = new StubModel('CameraModel', 'Id', array('Id', 'Name', 'ManufacturerId'));
$queue = array('0', '0');
$c = make('CameraModels', 'CameraModel', $m, array(), array(), 'DELETE');
run(function() use ($c) { $c->delete('4'); });
check('CameraModels delete of one not in use deletes it', $m->deleted, true);

# --- Tags
$m = new StubModel('Tag', 'Id', array('Id', 'Name'));
$c = make('Tags', 'Tag', $m, array(), array('Name' => 'a', 'Events.Id' => '5'), 'GET');
run(function() use ($c) { $c->index(); });
check('Tags index qualifies a bare filter field when it joins Events', array_keys($m->found['conditions']), array('Tag.Name', 'Events.Id'));

# --- UserPreference
$m = new StubModel('UserPreference', 'Id', array('Id', 'UserId', 'Name', 'Value'));
$c = make('UserPreference', 'UserPreference', $m, array('Value' => 'x'));
check('UserPreference add without a Name is a 400, not a notice', array(thrown(function() use ($c) { $c->add(); }), $m->saved), array('BadRequestException:0', null));

class FailingModel extends StubModel { function save($d, $o = array()) { $this->validationErrors = array('Name' => array('bad')); return false; } }
$m = new FailingModel('UserPreference', 'Id', array('Id', 'UserId', 'Name', 'Value'));
$c = make('UserPreference', 'UserPreference', $m, array('Name' => 'a', 'Value' => 'x'));
check('UserPreference add reports a failed save without a notice', array(thrown(function() use ($c) { $c->add(); }), (isset($c->vars['message']) ? $c->vars['message'] : null)), array(null, 'Failure'));

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
?>
