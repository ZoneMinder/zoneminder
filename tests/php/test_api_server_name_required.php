<?php
// Servers.Name defaults to '', and the Server model had no rule for it, so an API
// add without a Name, or with a blank one, was saved; the web console answers
// 'Server Name is required'. Name must be required on create, and never blank.
// Cake is stubbed and the model's rules are applied the way CakeValidationSet
// does for the rule types used here (required, notBlank, custom); Cake's own
// Validation class does the checking.
//
//   php tests/php/test_api_server_name_required.php [root]

class App { public static function uses($class, $package) {} }
class AppModel {}

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
require_once $root.'/web/api/lib/Cake/Utility/Validation.php';
require_once $root.'/web/api/app/Model/Server.php';

$failures = 0;
function check($ok, $what) {
  global $failures;
  if ($ok) echo "ok - $what\n"; else { $failures++; echo "not ok - $what\n"; }
}

// True when $data passes every notBlank/custom rule of $field, with a field
// that is missing failing only a rule that requires it (true, or 'create' on a create).
function accepts($model, $field, $data, $create) {
  foreach ($model->validate[$field] as $rule) {
    $required = isset($rule['required']) ? $rule['required'] : false;
    if (!array_key_exists($field, $data)) {
      if ($required === true or ($required === 'create' and $create)) return false;
      continue;
    }
    $r = $rule['rule'];
    $name = is_array($r) ? $r[0] : $r;
    $args = is_array($r) ? array_slice($r, 1) : array();
    if ($name !== 'notBlank' and $name !== 'custom') continue;
    if (!call_user_func_array(array('Validation', $name), array_merge(array($data[$field]), $args))) return false;
  }
  return true;
}

$model = new Server();

check(!accepts($model, 'Name', array(), true), "add without Name is refused");
check(!accepts($model, 'Name', array('Name' => ''), true), "add with a blank Name is refused");
check(!accepts($model, 'Name', array('Name' => '   '), true), "add with only spaces in Name is refused");
check(accepts($model, 'Name', array('Name' => 'zm-node-2'), true), "add with a non-blank Name is accepted");
check(accepts($model, 'Name', array(), false), "an edit may leave Name out");
check(accepts($model, 'Name', array('Name' => 'zm-node-2'), false), "an edit may keep the stored Name");
check(!accepts($model, 'Name', array('Name' => ''), false), "an edit may not blank Name");

exit($failures ? 1 : 0);
