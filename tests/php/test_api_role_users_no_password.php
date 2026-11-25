<?php
// API /roles/view/<id>.json returned each member's Password hash because
// associated Users skip User::beforeFind().
//
//   php tests/php/test_api_role_users_no_password.php [root]

class App { public static function uses($class, $package) {} }

// Minimal stand-in for CakePHP's Model.
class AppModel {}

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
require_once $root.'/web/api/app/Model/Role.php';

$passes = 0;
$failures = 0;
function check($ok, $what) {
  global $passes, $failures;
  if ($ok) { $passes++; echo "ok - $what\n"; } else { $failures++; echo "not ok - $what\n"; }
}

$r = new Role();
check(isset($r->hasMany['User']), 'Role hasMany User');

$fields = isset($r->hasMany['User']['fields']) ? $r->hasMany['User']['fields'] : null;
check(is_array($fields) and count($fields) > 0, 'Role hasMany User has a non-empty fields list');
check(!is_array($fields) or !in_array('Password', $fields), 'Role hasMany User fields exclude Password');
check(!is_array($fields) or !in_array('TokenMinExpiry', $fields), 'Role hasMany User fields exclude TokenMinExpiry');
check(is_array($fields) and in_array('Id', $fields) and in_array('Username', $fields),
  'Role hasMany User fields include Id and Username');

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
