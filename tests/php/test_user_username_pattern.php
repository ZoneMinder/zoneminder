<?php
// The Username input pattern in the classic user view must accept what the
// server-side filter in web/includes/User.php allows (\w . @ and space).
// Run: php tests/php/test_user_username_pattern.php [root]

$root = isset($argv[1]) ? $argv[1] : dirname(__DIR__, 2);
$view = $root.'/web/skins/classic/views/user.php';

$failed = 0;
$passed = 0;

if (!is_file($view)) {
  echo "  FAIL cannot read $view\n";
  echo "0 passed, 1 failed\n";
  exit(1);
}
$contents = file_get_contents($view);

$pattern = null;
if (preg_match('/<input\b[^>]*name="user\[Username\]"[^>]*>/', $contents, $tag)) {
  if (preg_match('/pattern="([^"]*)"/', $tag[0], $m)) {
    $pattern = $m[1];
  }
}
if ($pattern === null) {
  echo "  FAIL no pattern on user[Username] input\n";
  echo "0 passed, 1 failed\n";
  exit(1);
}

$regex = '/^(?:'.$pattern.')$/';

$accepted = array('john_doe', 'bob', 'a.b@c d', 'Bob_2');
$rejected = array('bad!name');

foreach ($accepted as $name) {
  $ok = preg_match($regex, $name) === 1;
  echo ($ok ? '  ok' : '  FAIL')." accepts ".var_export($name, true)."\n";
  $ok ? $passed++ : $failed++;
}

foreach ($rejected as $name) {
  $ok = preg_match($regex, $name) === 0;
  echo ($ok ? '  ok' : '  FAIL')." rejects ".var_export($name, true)."\n";
  $ok ? $passed++ : $failed++;
}

echo "$passed passed, $failed failed\n";
exit($failed ? 1 : 0);
