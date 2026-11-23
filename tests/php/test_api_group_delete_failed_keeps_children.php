<?php
// GroupsController::delete() re-parented children with an UPDATE before deleting the group.
// When the delete failed, the children had already been moved. The UPDATE and the delete now
// run in a transaction that is rolled back when the delete fails.
//
// GroupsController can't be loaded without CakePHP, so delete() is taken from the source and
// run against stubs.
// Run as: php tests/php/test_api_group_delete_failed_keeps_children.php [root]
//   (root defaults to the repo)

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$source = file_get_contents($root.'/web/api/app/Controller/GroupsController.php');
$start = strpos($source, 'public function delete(');
$open = strpos($source, '{', $start);
$depth = 0;
for ($i = $open; $i < strlen($source); $i++) {
  if ($source[$i] == '{') $depth++;
  else if ($source[$i] == '}' && --$depth == 0) break;
}
$method = substr($source, $start, $i - $start + 1);

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

class NotFoundException extends Exception {}
class UnauthorizedException extends Exception {}
class InternalErrorException extends Exception {}
function __($s) { return $s; }

// Groups table: Id => ParentId
$groups = array();
function groups_update($sql, $params = array()) {
  global $groups;
  if (preg_match('/^UPDATE `Groups` SET `ParentId`=(\?|NULL) WHERE `ParentId`=\?$/', $sql, $m)) {
    $new = $m[1] == '?' ? $params[0] : null;
    $old = end($params);
    foreach ($groups as $id => $parent) if ($parent == $old) $groups[$id] = $new;
  }
  return true;
}
// The controller runs the UPDATE through the model; the original ran it through dbQuery().
function dbQuery($sql, $params = array()) { return groups_update($sql, $params); }

eval('namespace ZM; class Group {
  private $id;
  function __construct($id) { $this->id = $id; }
  function ParentId() { global $groups; return $groups[$this->id]; }
  function MonitorIds() { return array(); }
}');

// begin() snapshots the table, rollback() restores it, commit() discards the snapshot.
// Without begin() every change is permanent.
class FakeDataSource {
  private $snapshot = null;
  function begin() { global $groups; $this->snapshot = $groups; }
  function commit() { $this->snapshot = null; }
  function rollback() {
    global $groups;
    if ($this->snapshot !== null) $groups = $this->snapshot;
    $this->snapshot = null;
  }
}

class FakeModel {
  public $id, $deleteResult = true, $ds;
  function __construct() { $this->ds = new FakeDataSource(); }
  function exists() { global $groups; return array_key_exists($this->id, $groups); }
  function delete() {
    global $groups;
    if (!$this->deleteResult) return false;
    unset($groups[$this->id]);
    return true;
  }
  function query($sql, $params = array()) { return groups_update($sql, $params); }
  function getDataSource() { return $this->ds; }
}
class FakeRequest { function allowMethod() {} }
eval('class FakeController { public $Group; public $request; public $out;
  function set($a) { $this->out = $a; }
  private function requireMembershipEdit($ids) {}
  '.$method.' }');

function run_delete($id, $deleteResult = true) {
  $c = new FakeController();
  $c->Group = new FakeModel();
  $c->Group->deleteResult = $deleteResult;
  $c->request = new FakeRequest();
  try {
    $c->delete($id);
  } catch (Exception $e) {
    return array(get_class($e), $c);
  }
  return array('ok', $c);
}
$user = null;

// (1) A delete that works commits the move and answers Deleted.
$groups = array(1 => null, 2 => 1, 3 => 2);
list($r, $c) = run_delete(2);
check('a delete that works answers Deleted', array($r, $c->out['message'] ?? null), array('ok', 'Deleted'));
check('its child moves up to its parent', $groups, array(1 => null, 3 => 1));

// (2) A failed delete rolls the move back: the children keep their original parent.
$groups = array(1 => null, 2 => 1, 3 => 2);
list($r, $c) = run_delete(2, false);
check('a failed delete is an InternalErrorException', $r, 'InternalErrorException');
check('a failed delete leaves the children where they were', $groups, array(1 => null, 2 => 1, 3 => 2));

echo $failures ? "$failures failed\n" : "all passed\n";
exit($failures ? 1 : 0);
