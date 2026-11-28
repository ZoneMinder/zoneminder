<?php
// NotificationsController add() and edit() read the Notification from the request body. A body such as
// {"Notification":"x"} left a string where they unset and set keys, an Error, and a Token that is a nested
// array went into the find() that looks the token up, which MySQL answers with "Unknown column 'Array'".
// Both were a 500. A Notification that is not an object is a 400 now, and a Token that is not a plain value
// skips the lookup so save() refuses it.
// Run as: php tests/php/test_api_notification_nonscalar.php [root]
error_reporting(E_ALL);
$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}
$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
foreach (array('BadRequestException', 'UnauthorizedException', 'NotFoundException') as $c) if (!class_exists($c)) eval("class $c extends Exception {}");
if (!function_exists('__')) { function __($s) { return $s; } }

class FakeRequest {
  public $data;
  function __construct($data) { $this->data = $data; }
  function is($t) { return $t == 'post'; }
  function allowMethod() {}
}
class FakeModel {
  public $id = null; public $saved = null; public $validationErrors = array();
  public $row = array('Notification' => array('Id' => 7, 'UserId' => 1, 'Token' => 'T'));
  function create() { $this->id = null; }
  function exists() { return true; }
  // Cake writes a nested array into the SQL as the word Array, and MySQL refuses it.
  function find($type, $o = array()) {
    foreach ($o['conditions'] as $value) {
      if (is_array($value)) foreach ($value as $inner) if (is_array($inner)) throw new Exception("Unknown column 'Array' in 'WHERE'");
    }
    return false;
  }
  function save($d) { $this->saved = $d; if (!$this->id) $this->id = 8; return true; }
}
class Resp { function statusCode($c) {} }

$source = file_get_contents("$root/web/api/app/Controller/NotificationsController.php");
function method($source, $name) {
  $start = strpos($source, "public function $name(");
  $depth = 0;
  for ($i = strpos($source, '{', $start); $i < strlen($source); $i++) {
    if ($source[$i] == '{') $depth++;
    else if ($source[$i] == '}' && --$depth == 0) break;
  }
  return substr($source, $start, $i - $start + 1);
}
$code = method($source, 'add').' '.method($source, 'edit');
eval('class Ctl { public $request; public $Notification; public $response; public $vars;
  function set($v) { $this->vars = $v; }
  '.str_replace(array('$this->_isAdmin()', '$this->_userId()'), array('true', '1'), $code).' }');

// What a call does: the exception class and message, or "saved" with the data that reached save().
function run($action, $data) {
  $c = new Ctl; $c->request = new FakeRequest($data);
  $c->Notification = new FakeModel; $c->response = new Resp;
  try {
    $action == 'add' ? $c->add() : $c->edit(7);
  } catch (Throwable $e) {
    return get_class($e).': '.$e->getMessage();
  }
  return $c->Notification->saved ? 'saved' : 'not saved';
}

foreach (array('add', 'edit') as $action) {
  check("$action: a string Notification is a bad request", run($action, array('Notification' => 'x')), 'BadRequestException: Notification must be an object');
  check("$action: a number Notification is a bad request", run($action, array('Notification' => 5)), 'BadRequestException: Notification must be an object');
  check("$action: an object Notification is saved", run($action, array('Notification' => array('Token' => 'T2', 'Platform' => 'web'))), 'saved');
  check("$action: a body without the Notification key is saved", run($action, array('Token' => 'T2', 'Platform' => 'web')), 'saved');
}
check('add: a nested Token is left to save(), not looked up', run('add', array('Token' => array(array('a')), 'Platform' => 'web')), 'saved');
check('add: a nested Token inside Notification is left to save()', run('add', array('Notification' => array('Token' => array(array('a')), 'Platform' => 'web'))), 'saved');
check('add: a plain Token is still looked up', run('add', array('Token' => 'T2', 'Platform' => 'web')), 'saved');
exit($failures ? 1 : 0);
