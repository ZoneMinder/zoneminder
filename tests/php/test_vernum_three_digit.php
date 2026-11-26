<?php
// verNum() padded each version field to two digits, so a field of 100 or more
// produced a longer number that sorted above every later release (1.39.100 sorted
// as newer than 1.40.0), breaking the update notice in getZMVersionHTML().
//
// Run: php tests/php/test_vernum_three_digit.php [repo root]   (defaults to the repo)

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$src = file_get_contents($root.'/web/includes/functions.php');
if (!preg_match('/^function verNum\(.*?^}/ms', $src, $m)) {
  echo "FAIL could not find verNum in $root\n";
  exit(1);
}
eval($m[0]);

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) {
    echo "  ok $name\n";
  } else {
    $failures++;
    echo "  FAIL $name (got ".var_export($got, true).", want ".var_export($want, true).")\n";
  }
}

// The same comparison getZMVersionHTML() makes: verNum(a) <= verNum(b).
function le($a, $b) { return verNum($a) <= verNum($b); }

check('1.39.100 older than 1.40.0', le('1.39.100', '1.40.0'), true);
check('1.40.0 newer than 1.39.100', le('1.40.0', '1.39.100'), false);
check('1.37.100 newer than 1.37.99', le('1.37.100', '1.37.99'), false);
check('1.37.99 older than 1.37.100', le('1.37.99', '1.37.100'), true);
check('1.37.100 older than 1.38.0', le('1.37.100', '1.38.0'), true);
check('1.36.10 newer than 1.36.9', le('1.36.10', '1.36.9'), false);
check('1.36.9 older than 1.36.10', le('1.36.9', '1.36.10'), true);
check('equal versions', le('1.39.54', '1.39.54') && le('1.39.54', '1.39.54'), true);
check('equal verNum', verNum('1.39.54'), verNum('1.39.54'));
check('two fields equal three with .0', verNum('1.40'), verNum('1.40.0'));
check('two fields ordering', le('1.40', '1.40.1'), true);
check('1.100.0 newer than 1.99.9', le('1.100.0', '1.99.9'), false);

echo $failures ? "$failures FAILED\n" : "all passed\n";
exit($failures ? 1 : 0);
