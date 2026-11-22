<?php
/*
 * Deleting a group moves its child groups up to the deleted group's parent, so
 * grandparent -> parent -> child becomes grandparent -> child. Detaching the
 * children to the top level instead drops the child's monitors out of the
 * grandparent. A multi group delete reads the parent fresh, as the earlier
 * delete in the same request already moved some rows.
 *
 * Run: php tests/php/test_group_delete_reparent.php
 */
namespace ZM {
  class ZM_Object {
    public function __construct($p = null) {
      if (is_array($p)) foreach ($p as $k => $v) $this->{$k} = $v;
    }
    public function Id() { return $this->{'Id'} ?? null; }
  }
  class Monitor extends ZM_Object {}
  class Group_Permission extends ZM_Object {}
  function zm_setcookie() {}
}
namespace {
  // id => ParentId
  $G = array(1 => null, 2 => 1, 3 => 2);
  function dbFetchOne($sql, $col = null, $params = array()) {
    global $G;
    if (strpos($sql, 'SELECT `ParentId`') !== false) {
      return isset($G[$params[0]]) || array_key_exists($params[0], $G) ? array('ParentId' => $G[$params[0]]) : false;
    }
    return false;
  }
  function dbQuery($sql, $params = array()) {
    global $G;
    if (strpos($sql, 'UPDATE `Groups` SET `ParentId`=NULL') !== false) {
      foreach ($G as $id => $p) if ($p == $params[0]) $G[$id] = null;
    } else if (strpos($sql, 'UPDATE `Groups` SET `ParentId`=?') !== false) {
      foreach ($G as $id => $p) if ($p == $params[1]) $G[$id] = $params[0];
    } else if (strpos($sql, 'DELETE FROM `Groups` ') !== false) {
      unset($G[$params[0]]);
    }
    return true;
  }
  $tests = 0; $failures = 0;
  function check($label, $cond) {
    global $tests, $failures;
    $tests++;
    if ($cond) echo "  ok  - $label\n"; else { $failures++; echo "  NOT OK - $label\n"; }
  }
  $src = file_get_contents(dirname(__DIR__, 2).'/web/includes/Group.php');
  eval('?>'.$src);

  // Delete the middle group: child 3 moves under group 1.
  (new ZM\Group(array('Id' => 2, 'ParentId' => 1)))->delete();
  check('child of a deleted group moves to the grandparent', $G === array(1 => null, 3 => 1));

  // Delete 2 then 3 from stale objects, as one request with two ids does.
  $G = array(1 => null, 2 => 1, 3 => 2, 4 => 3);
  $stale2 = new ZM\Group(array('Id' => 2, 'ParentId' => 1));
  $stale3 = new ZM\Group(array('Id' => 3, 'ParentId' => 2));
  $stale2->delete();
  $stale3->delete();
  check('deleting a chain leaves the last child under the surviving ancestor', $G === array(1 => null, 4 => 1));

  // A top level group deleted: children become top level.
  $G = array(1 => null, 2 => 1);
  (new ZM\Group(array('Id' => 1, 'ParentId' => null)))->delete();
  check('children of a top level group become top level', $G === array(2 => null));

  echo "\n$tests tests, $failures failures\n";
  exit($failures ? 1 : 0);
}
