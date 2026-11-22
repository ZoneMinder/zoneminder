<?php
// The API Monitor model's Name rules must accept ordinary monitor names
// (letters from any language, digits, spaces and - . ( ) : /) and reject
// markup, quotes and other characters that break HTML/URL output.
//
//   php tests/php/test_api_monitor_name_rules.php [repo-root]

class App { public static function uses($class, $package) {} }

class AppModel {}

$repoRoot = $argv[1] ?? realpath(__DIR__.'/../..');

require_once "$repoRoot/web/api/lib/Cake/Utility/Validation.php";
require_once "$repoRoot/web/api/app/Model/Monitor.php";

$m = new Monitor();

function nameIsValid($value) {
  global $m;
  foreach ($m->validate['Name'] as $rule) {
    if (!isset($rule['rule'])) continue;
    $spec = $rule['rule'];
    if (is_array($spec)) {
      $method = array_shift($spec);
      $args = array_merge(array($value), $spec);
      if (!call_user_func_array(array('Validation', $method), $args)) return false;
    } else {
      if (!call_user_func(array('Validation', $spec), $value)) return false;
    }
  }
  return true;
}

$tests = 0;
$failures = 0;

function check($label, $cond) {
  global $tests, $failures;
  $tests++;
  if ($cond) {
    echo "  ok  - $label\n";
  } else {
    $failures++;
    echo "  NOT OK - $label\n";
  }
}

$valid = array(
  'Front Door',
  'Camera-1 (north): lot/2.1',
  'Caméra été',
  '車庫',
  'cam_01',
);

$invalid = array(
  '<img src=x onerror=alert(1)>',
  '"><script>1</script>',
  "a'b",
  'a=b',
  '<b>x</b>',
  '',
);

foreach ($valid as $value) {
  check('valid: '.$value, nameIsValid($value) === true);
}

foreach ($invalid as $value) {
  $label = $value === '' ? '(blank)' : $value;
  check('invalid: '.$label, nameIsValid($value) === false);
}

echo "\n$tests tests, $failures failures\n";
exit($failures ? 1 : 0);
?>
