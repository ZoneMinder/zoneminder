<?php
// GroupsController::edit() used to save Group[MonitorIds] as given and ignore it, so a PUT
// that only named the group's monitors saved no Monitor links (and failed to associate).
// edit() now moves Group[MonitorIds] to the top-level Monitor list and rewrites it to the
// plain id list Model::save() reads, before save() is called.
//
// The controller can't be loaded without CakePHP, so edit() and the real requestField()
// and requestAssociatedIds() helpers are taken from the source and run against stubs.
// Run as: php tests/php/test_api_groups_edit_members_only.php [root]   (root defaults to the repo)

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
if (!class_exists('NotFoundException')) { class NotFoundException extends Exception {} }
if (!class_exists('MethodNotAllowedException')) { class MethodNotAllowedException extends Exception {} }
if (!class_exists('InternalErrorException')) { class InternalErrorException extends Exception {} }
if (!function_exists('__')) { function __($s) { return $s; } }
if (!function_exists('ZM\Error')) { eval('namespace ZM; function Error($s) {}'); }
if (!class_exists('ZM\Group')) {
  eval('namespace ZM; class Group {
    private $id;
    function __construct($id = null) { $this->id = $id; }
    function ParentId() { return null; }
    function MonitorIds() { return array(); }
  }');
}
// The fixed edit() asks for the current links to work out which monitors move.
if (!function_exists('dbFetchAll')) {
  function dbFetchAll($sql = '', $key = '', $params = array()) { return array(); }
}

class FakeRequest {
  public $data;
  private $method;
  function __construct($method, $data = array()) { $this->method = $method; $this->data = $data; }
  function is($type) { return in_array(strtolower($this->method), array_map('strtolower', (array)$type)); }
  function allowMethod() {
    foreach (func_get_args() as $m) if ($this->is($m)) return true;
    throw new MethodNotAllowedException();
  }
}

class FakeModel {
  public $id = null;
  public $alias;
  public $primaryKey = 'Id';
  public $validationErrors = array();
  public $saveResult = true;
  public $saved = null;
  public $hasAndBelongsToMany = array();
  function __construct($alias) { $this->alias = $alias; }
  function create() { $this->id = null; }
  function exists($id = null) { return true; }
  function field($field, $conditions = array()) { return null; }
  function save($data = null) {
    $this->saved = $data;
    if ($this->saveResult) { if (!$this->id) $this->id = 42; }
    return $this->saveResult;
  }
  function validates($options = array()) { return !$this->validationErrors; }
  function findById($id) { return array($this->alias => array('Id' => $id)); }
}

// Brace-match a method from its declaration line, keeping the public/protected modifier.
function extract_method($file, $name) {
  $source = file_get_contents($file);
  $pos = strpos($source, "function $name(");
  if ($pos === false) return null;
  $lineStart = strrpos(substr($source, 0, $pos), "\n");
  $start = ($lineStart === false) ? 0 : $lineStart + 1;
  $depth = 0;
  for ($i = strpos($source, '{', $pos); $i < strlen($source); $i++) {
    if ($source[$i] == '{') $depth++;
    else if ($source[$i] == '}' && --$depth == 0) break;
  }
  return substr($source, $start, $i - $start + 1);
}

function run_edit($class, $data, $method = 'PUT', $id = 7) {
  global $user, $warnings;
  $user = null;
  $warnings = array();
  $h = new $class();
  $h->request = new FakeRequest($method, $data);
  $h->Group = new FakeModel('Group');
  $h->Group->hasAndBelongsToMany = array('Monitor' => array('associationForeignKey' => 'MonitorId'));
  try {
    $h->edit($id);
  } catch (Throwable $e) {
    return array(get_class($e), $h, $warnings);
  }
  return array('ok', $h, $warnings);
}

$controller = $root.'/web/api/app/Controller/GroupsController.php';
$app = $root.'/web/api/app/Controller/AppController.php';
$edit = is_file($controller) ? extract_method($controller, 'edit') : null;
$requestField = is_file($app) ? extract_method($app, 'requestField') : null;
$requestAssociatedIds = is_file($app) ? extract_method($app, 'requestAssociatedIds') : null;

if ($edit === null or $requestField === null or $requestAssociatedIds === null) {
  $failures++;
  echo "FAIL - no edit()/requestField()/requestAssociatedIds() in $controller or $app\n";
  echo $failures ? "\n$failures failed\n" : "\nall passed\n";
  exit(1);
}

$class = 'GroupsHarness';
eval('class '.$class.' {
  public $request, $Group, $vars = array(), $flashed = array(), $membershipChecked = array();
  function set($v, $value = null) { $this->vars = array_merge($this->vars, is_array($v) ? $v : array($v => $value)); }
  function flash($message, $url = null) { $this->flashed[] = $message; return "FLASH"; }
  function pinRequestId($model, $id) { $model->id = $id; }
  private function requireMembershipEdit($ids) { $this->membershipChecked[] = $ids; }
  '.$requestField.' '.$requestAssociatedIds.' '.$edit.' }');

# 1: a comma-separated MonitorIds string becomes the Monitor id list and is removed from Group.
list($r, $h, $w) = run_edit($class, array('Group' => array('MonitorIds' => '3,4')));
check('edit: comma string saves', $r, 'ok');
check('edit: comma string keeps no MonitorIds in Group', array_key_exists('MonitorIds', $h->Group->saved['Group']), false);
check('edit: comma string saves Monitor ids', $h->Group->saved['Monitor'], array('Monitor' => array(3, 4)));
check('edit: comma string answers Saved', isset($h->vars['message']) ? $h->vars['message'] : null, 'Saved');
check('edit: comma string checks the moved membership', $h->membershipChecked, array(array(3, 4)));
check('edit: comma string raises no PHP warning', $w, array());

# 2: an array MonitorIds is moved the same way.
list($r, $h, $w) = run_edit($class, array('Group' => array('MonitorIds' => array(5))));
check('edit: array ids saves', $r, 'ok');
check('edit: array ids saves Monitor ids', $h->Group->saved['Monitor'], array('Monitor' => array(5)));
check('edit: array ids answers Saved', isset($h->vars['message']) ? $h->vars['message'] : null, 'Saved');
check('edit: array ids raises no PHP warning', $w, array());

# 3: an explicit top-level Monitor list wins over Group[MonitorIds].
list($r, $h, $w) = run_edit($class, array('Monitor' => array(9), 'Group' => array('MonitorIds' => '3')));
check('edit: explicit Monitor saves', $r, 'ok');
check('edit: explicit Monitor keeps its ids', $h->Group->saved['Monitor'], array('Monitor' => array(9)));
check('edit: explicit Monitor raises no PHP warning', $w, array());

# 4: a group-only edit still saves and adds no Monitor links.
list($r, $h, $w) = run_edit($class, array('Group' => array('Name' => 'x')));
check('edit: group-only saves', $r, 'ok');
check('edit: group-only saves the submitted data', $h->Group->saved, array('Group' => array('Name' => 'x')));
check('edit: group-only adds no Monitor key', array_key_exists('Monitor', $h->Group->saved), false);
check('edit: group-only answers Saved', isset($h->vars['message']) ? $h->vars['message'] : null, 'Saved');
check('edit: group-only raises no PHP warning', $w, array());

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
