<?php
// GroupsController::add() moved Group[MonitorIds] to the Monitor list only when it was a
// comma-separated string, so a POST that gave the ids as an array (Group[MonitorIds][]=3, or
// {"MonitorIds":[3,4]} in JSON, the shapes edit() accepts) created the group, answered Saved
// and linked none of the monitors. add() now takes an array the way edit() does, and the ids
// go through the same membership check.
//
// The controller can't be loaded without CakePHP, so add() and the real requestField() and
// requestAssociatedIds() helpers are taken from the source and run against stubs.
// Run as: php tests/php/test_api_groups_add_monitor_ids.php [root]   (root defaults to the repo)

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
if (!function_exists('ZM\Error')) { eval('namespace ZM; function Error($s) {}'); }

class FakeRequest {
  public $data;
  private $method;
  function __construct($method, $data = array()) { $this->method = $method; $this->data = $data; }
  function is($type) { return strtolower($type) == strtolower($this->method); }
}

class FakeGroup {
  public $id = null;
  public $alias = 'Group';
  public $primaryKey = 'Id';
  public $validationErrors = array();
  public $hasAndBelongsToMany = array('Monitor' => array('associationForeignKey' => 'MonitorId'));
  public $saved = null;
  function create() {}
  function saveAssociated($data, $options = array()) {
    $this->saved = $data;
    $this->id = 31;
    return true;
  }
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

$controller = $root.'/web/api/app/Controller/GroupsController.php';
$app = $root.'/web/api/app/Controller/AppController.php';
$add = is_file($controller) ? extract_method($controller, 'add') : null;
$requestField = is_file($app) ? extract_method($app, 'requestField') : null;
$requestAssociatedIds = is_file($app) ? extract_method($app, 'requestAssociatedIds') : null;
if ($add === null or $requestField === null or $requestAssociatedIds === null) {
  echo "FAIL - no add()/requestField()/requestAssociatedIds() in $controller or $app\n";
  exit(1);
}

eval('class GroupsHarness {
  public $request, $Group, $vars = array(), $membershipChecked = array();
  function set($v) { $this->vars = array_merge($this->vars, (array)$v); }
  function pinRequestId($model, $id) { $model->id = $id; }
  private function requireMembershipEdit($ids) { $this->membershipChecked[] = $ids; }
  '.$requestField.' '.$requestAssociatedIds.' '.$add.' }');

function run_add($data) {
  global $user, $warnings;
  $user = null;
  $warnings = array();
  $h = new GroupsHarness();
  $h->request = new FakeRequest('POST', $data);
  $h->Group = new FakeGroup();
  try {
    $h->add();
  } catch (Throwable $e) {
    return array(get_class($e), $h, $warnings);
  }
  return array('ok', $h, $warnings);
}

# A comma-separated string keeps working.
list($r, $h, $w) = run_add(array('Group' => array('Name' => 'Front', 'MonitorIds' => '3,4')));
check('string: saves', $r, 'ok');
check('string: links the monitors', isset($h->Group->saved['Monitor']) ? $h->Group->saved['Monitor'] : null, array('Monitor' => array(3, 4)));
check('string: checks the membership', $h->membershipChecked, array(array(3, 4)));

# An array under Group links the monitors too.
list($r, $h, $w) = run_add(array('Group' => array('Name' => 'Front', 'MonitorIds' => array(3, 4))));
check('Group[MonitorIds] array: saves', $r, 'ok');
check('Group[MonitorIds] array: links the monitors', isset($h->Group->saved['Monitor']) ? $h->Group->saved['Monitor'] : null, array('Monitor' => array(3, 4)));
check('Group[MonitorIds] array: MonitorIds is not saved as a field', array_key_exists('MonitorIds', isset($h->Group->saved['Group']) ? $h->Group->saved['Group'] : array()), false);
check('Group[MonitorIds] array: checks the membership', $h->membershipChecked, array(array(3, 4)));
check('Group[MonitorIds] array: answers Saved with the new id', array($h->vars['message'], $h->vars['id']), array('Saved', 31));
check('Group[MonitorIds] array: raises no PHP warning', $w, array());

# So does a bare JSON body.
list($r, $h, $w) = run_add(array('Name' => 'Front', 'MonitorIds' => array('5')));
check('bare array: links the monitors', isset($h->Group->saved['Monitor']) ? $h->Group->saved['Monitor'] : null, array('Monitor' => array(5)));
check('bare array: checks the membership', $h->membershipChecked, array(array(5)));

# Entries that are not ids are dropped, as edit() drops them.
list($r, $h, $w) = run_add(array('Group' => array('Name' => 'Front', 'MonitorIds' => array(array(9), '1,2', 'x', 3))));
check('mixed array: links only the numeric ids', isset($h->Group->saved['Monitor']) ? $h->Group->saved['Monitor'] : null, array('Monitor' => array(3)));
check('mixed array: raises no PHP warning', $w, array());

# No ids, or an empty list, links nothing and checks nothing.
list($r, $h, $w) = run_add(array('Group' => array('Name' => 'Front')));
check('no MonitorIds: saves', $r, 'ok');
check('no MonitorIds: no Monitor list', isset($h->Group->saved['Monitor']), false);
list($r, $h, $w) = run_add(array('Group' => array('Name' => 'Front', 'MonitorIds' => array())));
check('empty array: saves', $r, 'ok');
check('empty array: checks no membership', $h->membershipChecked, array());

# An explicit Monitor list wins over MonitorIds, as in edit().
list($r, $h, $w) = run_add(array('Group' => array('Name' => 'Front', 'MonitorIds' => array(3)), 'Monitor' => array(6)));
check('Monitor list wins: links that list', isset($h->Group->saved['Monitor']) ? $h->Group->saved['Monitor'] : null, array('Monitor' => array(6)));

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
