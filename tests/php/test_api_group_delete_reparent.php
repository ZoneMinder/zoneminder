<?php
// DELETE /api/groups/<id> moved the deleted group's children to the top level. They now move
// up to its parent, as ZM\Group::delete() does since #6394.
//
// GroupsController can't be loaded without CakePHP, so delete() is taken from the source
// and run against stubs.
// Run as: php tests/php/test_api_group_delete_reparent.php

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

$source = file_get_contents(__DIR__.'/../../web/api/app/Controller/GroupsController.php');
$start = strpos($source, 'public function delete(');
$open = strpos($source, '{', $start);
$depth = 0;
for ($i = $open; $i < strlen($source); $i++) {
  if ($source[$i] == '{') $depth++;
  else if ($source[$i] == '}' && --$depth == 0) break;
}
$method = substr($source, $start, $i - $start + 1);

class NotFoundException extends Exception {}
class UnauthorizedException extends Exception {}
class InternalErrorException extends Exception {}
function __($s) { return $s; }

// Groups table: Id => ParentId
$groups = array();
function dbQuery($sql, $params = array()) {
  global $groups;
  if (preg_match('/^UPDATE `Groups` SET `ParentId`=(\?|NULL) WHERE `ParentId`=\?$/', $sql, $m)) {
    $new = $m[1] == '?' ? $params[0] : null;
    $old = end($params);
    foreach ($groups as $id => $parent) if ($parent == $old) $groups[$id] = $new;
  }
  return true;
}

eval('namespace ZM; class Group {
  private $id;
  function __construct($id) { $this->id = $id; }
  function ParentId() { global $groups; return $groups[$this->id]; }
  function MonitorIds() { return array(); }
}');

class FakeModel {
  public $id;
  function exists() { global $groups; return array_key_exists($this->id, $groups); }
  function delete() { global $groups; unset($groups[$this->id]); return true; }
}
class FakeRequest { function allowMethod() {} }
eval('class FakeController { public $Group; public $request; public $out;
  function set($a) { $this->out = $a; }
  private function requireMembershipEdit($ids) {}
  '.$method.' }');

function run_delete($id) {
  $c = new FakeController();
  $c->Group = new FakeModel();
  $c->request = new FakeRequest();
  $c->delete($id);
  return $c->out['message'];
}
$user = null;

$groups = array(1 => null, 2 => 1, 3 => 2);
check('deleting a middle group answers Deleted', run_delete(2), 'Deleted');
check('its child moves up to its parent', $groups, array(1 => null, 3 => 1));

$groups = array(1 => null, 2 => 1, 3 => 1);
run_delete(1);
check('children of a top-level group move to the top level', $groups, array(2 => null, 3 => null));

echo $failures ? "$failures failed\n" : "all passed\n";
exit($failures ? 1 : 0);
