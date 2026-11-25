<?php
// REST API States and Servers controllers.
//
// - States add() and edit() saved the Id given in the request body, so
//   PUT /states/3.json {"State":{"Id":7,"Name":"x"}} rewrote state 7 and
//   POST /states.json {"State":{"Id":7,...}} overwrote an existing state, as
//   servers, storage and server stats already prevent with pinRequestId().
// - states/change with no state name read an undefined offset.
// - servers and states index ignored named filters (/servers/index/Name:x.json)
//   and returned every row, as storage and server_stats do not.
//
// Run as: php tests/php/test_api_states_servers.php

$failures = 0;
$passes = 0;
function check($name, $got, $want) {
  global $failures, $passes;
  if ($got === $want) { $passes++; echo "ok - $name\n"; }
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

error_reporting(E_ALL);
set_error_handler(function($no, $str) { throw new ErrorException($str, 0, $no); });

class BadRequestException extends Exception {}
class UnauthorizedException extends Exception {}
class NotFoundException extends Exception {}
class InternalErrorException extends Exception {}
function __($s) { return $s; }
function App_uses() {}
class Configure { static function read($k) { return '/bin'; } }

$dir = __DIR__.'/../../web/api/app/Controller';
$app = file_get_contents($dir.'/AppController.php');
if (!preg_match('/^  protected function pinRequestId\(.*?^  }\n/ms', $app, $m)) {
  echo "FAIL - could not find pinRequestId\n"; exit(1);
}
eval('#[AllowDynamicProperties] class AppController { public $request; public $vars = array(); public $Components; public $FilterComponent;
  public function beforeFilter() {}
  public function set($a) { $this->vars = $a; }
  '.$m[0].' }');

class StubModel {
  public $alias; public $primaryKey = 'Id'; public $id = null; public $saved = null; public $found = null;
  public $validationErrors = array(); public $findOptions = null; public $recursive = 0;
  function __construct($alias) { $this->alias = $alias; }
  function create() { $this->id = null; }
  function exists($id = null) { return true; }
  function save($data) {
    // Model::save() takes the id from the data when it has one.
    $this->saved = array($this->id, $data);
    if (isset($data[$this->alias][$this->primaryKey])) $this->saved[0] = $data[$this->alias][$this->primaryKey];
    elseif (isset($data[$this->primaryKey])) $this->saved[0] = $data[$this->primaryKey];
    return true;
  }
  function find($type, $options = null) { $this->findOptions = $options; return array(); }
  function schema() { return array('Id' => 1, 'Name' => 1, 'Hostname' => 1); }
}
class StubFilter {
  public $fields = null;
  function buildFilter($named, $valid = null) { $this->fields = $valid; return $named; }
}
class StubComponents { function load($n) { return new StubFilter(); } }

foreach (array('StatesController', 'ServersController') as $c) {
  $src = file_get_contents($dir.'/'.$c.'.php');
  $src = preg_replace('/^<\?php/', '', $src);
  $src = preg_replace('/^App::uses.*$/m', '', $src);
  eval($src);
}

function make($class, $model, $data, $named = array(), $pass = array()) {
  $c = new $class();
  $c->request = new class($data, $named, $pass) {
    public $data; public $params;
    function __construct($d, $n, $p) { $this->data = $d; $this->params = array('named' => $n, 'pass' => $p); }
    function is($t) { return true; }
    function allowMethod() {}
  };
  $c->{$model} = new StubModel($model);
  $c->Components = new StubComponents();
  return $c;
}

// edit pins the id from the URL
$c = make('StatesController', 'State', array('State' => array('Id' => 7, 'Name' => 'x')));
$c->edit(3);
check('state edit saves the URL id, not the body id', $c->State->saved[0], 3);
$c = make('StatesController', 'State', array('Id' => 7, 'Name' => 'x'));
$c->edit(3);
check('state edit ignores a bare body Id', $c->State->saved[0], 3);

// add never overwrites an existing row
$c = make('StatesController', 'State', array('State' => array('Id' => 7, 'Name' => 'x')));
$c->add();
check('state add does not save over an existing id', $c->State->saved[0], null);

// change without a name
$c = make('StatesController', 'State', array(), array(), array());
$thrown = '';
try { $c->change(); } catch (Exception $e) { $thrown = get_class($e); }
check('states/change without a state name is a 400', $thrown, 'BadRequestException');

// index filters
function conds($m) { return is_array($m->findOptions) && isset($m->findOptions['conditions']) ? $m->findOptions['conditions'] : null; }
$c = make('ServersController', 'Server', array(), array('Name' => 'a'));
$c->index();
check('servers index filters on named params', conds($c->Server), array('Name' => 'a'));
$c = make('ServersController', 'Server', array());
$c->index();
check('servers index without params has no conditions', conds($c->Server), array());
$c = make('StatesController', 'State', array(), array('Name' => 'a'));
$c->index();
check('states index filters on named params', conds($c->State), array('Name' => 'a'));

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
