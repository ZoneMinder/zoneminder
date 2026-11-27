<?php
// The API Storage model refuses a Name or Path with characters the web console
// strips from them (shell metacharacters, quotes, newlines, backslashes).
// Cake's AppModel is stubbed and the model's validation rules are applied as
// Validation::custom() does.
// Run as: php tests/php/test_api_storage_validate.php [root]

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$file = $root.'/web/api/app/Model/Storage.php';
if (!is_file($file)) { echo "FAIL - model not found at $file\n"; exit(1); }

class App { public static function uses($class, $package) {} }
class AppModel {}
require $file;

$model = new Storage();

// True when every rule of the field accepts the value.
function accepts($model, $field, $value) {
  if (empty($model->validate[$field])) return true;
  foreach ($model->validate[$field] as $rule) {
    $r = $rule['rule'];
    if (is_array($r) && $r[0] === 'custom' && !preg_match($r[1], (string)$value)) return false;
  }
  return true;
}

foreach (array('Path', 'Name') as $field) {
  check("$field accepts a plain path", accepts($model, $field, '/var/cache/zoneminder/events (2)'), true);
  check("$field refuses a semicolon", accepts($model, $field, '/data; touch /tmp/x'), false);
  check("$field refuses a command substitution", accepts($model, $field, '/data/$(id)'), false);
  check("$field refuses a backtick", accepts($model, $field, '/data/`id`'), false);
  check("$field refuses a quote", accepts($model, $field, "/data/'x"), false);
  check("$field refuses a newline", accepts($model, $field, "/data\nx"), false);
  check("$field refuses a backslash", accepts($model, $field, '/data\\x'), false);
  check("$field refuses a trailing newline", accepts($model, $field, "/data\n"), false);
}
exit($failures ? 1 : 0);
