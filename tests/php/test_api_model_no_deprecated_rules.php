<?php
// Role, Frame and User validated with Validation::notEmpty(), which CakePHP 2.10 deprecates: each
// call raises E_USER_DEPRECATED, and the API error handler (level E_ALL & ~E_DEPRECATED, which
// still includes E_USER_DEPRECATED) logs it on every role, frame or user save. Run every Validation
// rule of these models and fail if any of them raises a deprecation.
//
//   php tests/php/test_api_model_no_deprecated_rules.php [repo-root]

class App { public static function uses($class, $package) {} }
class AppModel {}

$repoRoot = $argv[1] ?? realpath(__DIR__.'/../..');

require_once "$repoRoot/web/api/lib/Cake/Utility/Validation.php";
foreach (array('Role', 'Frame', 'User') as $class) require_once "$repoRoot/web/api/app/Model/$class.php";

$passes = 0;
$failures = 0;
function check($ok, $what) {
  global $passes, $failures;
  if ($ok) { $passes++; echo "ok - $what\n"; } else { $failures++; echo "not ok - $what\n"; }
}

foreach (array('Role', 'Frame', 'User') as $class) {
  $model = new $class();
  foreach ($model->validate as $field => $rules) {
    foreach ($rules as $name => $rule) {
      $spec = is_array($rule) && isset($rule['rule']) ? $rule['rule'] : null;
      if ($spec === null) continue;
      $method = is_array($spec) ? array_shift($spec) : $spec;
      if (!method_exists('Validation', $method)) continue; // Model method such as isUnique
      $deprecations = 0;
      set_error_handler(function($no) use (&$deprecations) { if ($no === E_USER_DEPRECATED) $deprecations++; return true; });
      call_user_func_array(array('Validation', $method), array_merge(array('x'), is_array($spec) ? $spec : array()));
      restore_error_handler();
      check($deprecations === 0, "$class.$field rule '$method' raises no deprecation");
    }
  }
}

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
