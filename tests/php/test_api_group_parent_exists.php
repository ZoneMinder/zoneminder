<?php
// Groups.ParentId is a foreign key to Groups.Id. An API add or edit with a ParentId that matches no group
// reached MySQL, which refused the write, and the client got a 500. The Group model now refuses it as a
// validation error, and still accepts no parent and a parent that exists.
// The Cake model is stubbed, so this runs from a source checkout:
//   php tests/php/test_api_group_parent_exists.php

class App { public static function uses($class, $package) {} }
class AppModel {
  public static $groups = array(1, 2, 5);
  public function find($type, $options) {
    $id = $options['conditions']['Group.Id'];
    return in_array((int)$id, self::$groups, true) && ctype_digit((string)$id) ? array('Group' => array('Id' => $id)) : array();
  }
}
require_once __DIR__.'/../../web/api/app/Model/Group.php';

$passes = 0;
$failures = 0;
function check($ok, $what) {
  global $passes, $failures;
  if ($ok) { $passes++; echo "ok - $what\n"; } else { $failures++; echo "not ok - $what\n"; }
}

$g = new Group();
check(isset($g->validate['ParentId']), 'Group validates ParentId');
$rule = null;
foreach (isset($g->validate['ParentId']) ? $g->validate['ParentId'] : array() as $r) $rule = $r['rule'];
$accepts = function($value) use ($g, $rule) {
  return $rule ? $g->{$rule[0]}(array('ParentId' => $value)) : true;
};
check($accepts(null) === true, 'no parent (null) is accepted');
check($accepts('') === true, 'no parent (empty) is accepted');
check($accepts('2') === true, 'an existing parent is accepted');
check($accepts(5) === true, 'an existing parent given as a number is accepted');
check($accepts('999') === false, 'a parent that does not exist is refused');
check($accepts('0') === false, 'parent 0 is refused, no group has that Id');
check($accepts(array('a' => array('b' => 1))) === true, 'a nested array is left to AppModel, which refuses it, and is not looked up');
check($accepts('abc') === false, 'a non-numeric parent is refused');

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
