<?php
// User::beforeFind() lists the User columns without Password. A field list
// leaves out joined models unless they are named, so /api/users.json and
// /api/users/<id>.json never returned the user's Role. Its columns must be in
// the list whenever the find joins it (recursive 0 or more).
//
// The Cake model is stubbed, so this runs from a source checkout:
//   php tests/php/test_api_user_role_fields.php

class App { public static function uses($class, $package) {} }
class AppModel {
  public $alias = 'User';
  public $recursive = 1;
  public function beforeFind($query) { return true; }
}
require_once __DIR__.'/../../web/api/app/Model/User.php';

class StubSchema {
  public $columns;
  public function __construct($columns) { $this->columns = $columns; }
  public function schema() { return array_fill_keys($this->columns, array()); }
}
class TestUser extends User {
  public $Role;
  public function schema() { return array_fill_keys(array('Id', 'Username', 'Password', 'RoleId'), array()); }
}

$passes = 0;
$failures = 0;
function check($name, $ok) {
  global $passes, $failures;
  if ($ok) { $passes++; echo "ok - $name\n"; } else { $failures++; echo "not ok - $name\n"; }
}

function fields($recursive, $query = array()) {
  $u = new TestUser();
  $u->Role = new StubSchema(array('Id', 'Name', 'System'));
  $u->recursive = $recursive;
  $q = $u->beforeFind(array_merge(array('fields' => null), $query));
  return $q['fields'];
}

$f = fields(1);
check('recursive 1 keeps User columns', in_array('User.Id', $f) and in_array('User.RoleId', $f));
check('recursive 1 leaves out Password', !in_array('User.Password', $f));
check('recursive 1 names the Role columns', in_array('Role.Id', $f) and in_array('Role.Name', $f) and in_array('Role.System', $f));

$f = fields(0);
check('recursive 0 names the Role columns', in_array('Role.Name', $f));

$f = fields(-1);
check('recursive -1 does not join Role', !in_array('Role.Name', $f));

$f = fields(1, array('recursive' => -1));
check('a query with recursive -1 does not join Role', !in_array('Role.Name', $f));

$u = new TestUser();
$u->Role = new StubSchema(array('Id'));
$q = $u->beforeFind(array('fields' => array('User.Id')));
check('an explicit field list is left alone', $q === true);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
