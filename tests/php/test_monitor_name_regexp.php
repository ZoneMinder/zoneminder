<?php

$failures = 0;
$passes = 0;

function check($name, $got, $want) {
  global $failures, $passes;
  if ($got === $want) {
    $passes++;
    echo "  ok $name\n";
  } else {
    $failures++;
    echo "  FAIL $name\n";
    echo '    got:  '.var_export($got, true)."\n";
    echo '    want: '.var_export($want, true)."\n";
  }
}

$path = __DIR__.'/../../web/skins/classic/views/_monitor_filters.php';
$src = file_get_contents($path);
if (!preg_match('/^function monitorNameRegexp\(.*?\n\}/ms', $src, $m)) {
  echo "Could not find monitorNameRegexp() in $path\n";
  exit(1);
}
eval($m[0]);

$matches = function($filter, $name) {
  return preg_match(monitorNameRegexp($filter), $name) === 1;
};

check('plain text, any case', $matches('door', 'Front DOOR'), true);
check('plain text that is absent', $matches('door', 'Garage'), false);
check('regular expression', $matches('^front', 'Front Door'), true);
check('regular expression anchored out', $matches('^door', 'Front Door'), false);
check('delimited pattern', $matches('/^front/i', 'Front Door'), true);
check('delimited pattern is case sensitive without i', $matches('/^front/', 'Front Door'), false);
check('delimited pattern with alternation', $matches('/garage|door/i', 'Front Door'), true);
check('text containing a slash', $matches('in/out', 'Gate in/out'), true);
check('text containing a slash that is absent', $matches('in/out', 'Gate'), false);
check('invalid pattern matches literally', $matches('cam(', 'cam(1)'), true);
check('invalid pattern that is absent', $matches('cam(', 'cam1'), false);
check('delimited but invalid pattern matches literally', $matches('/cam(/i', '/cam(/i'), true);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
