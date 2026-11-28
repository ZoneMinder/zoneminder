<?php
// validStr() strips tags from a request value. strip_tags() throws a TypeError for an array,
// so newBandwidth[]=x on the bandwidth action, filter[Query][sort_field][]=x on the filter
// action and postLoginQuery[]=x on the login page all ended in a fatal error. An
// array is no string: validStr() gives '' for it as validHtmlStr() and validJsStr() do.
//
// Run as: php tests/php/test_validstr_array.php

$failures = 0;
$passes = 0;

function check($name, $got, $want) {
  global $failures, $passes;
  if ($got === $want) {
    $passes++;
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n";
  }
}

$source = file_get_contents(__DIR__.'/../../web/includes/functions.php');
if (!preg_match('/^function validStr\(.*?^}\n/ms', $source, $m)) {
  echo "FAIL - could not find validStr in functions.php\n";
  exit(1);
}
eval($m[0]);

check('a plain value is kept', validStr('high'), 'high');
check('tags are stripped', validStr('<b>high</b>'), 'high');
check('null is empty', validStr(null), '');
check('a number is kept', validStr(5), '5');
foreach (array('a list' => array('x'), 'a keyed array' => array('k' => 'v'), 'a nested array' => array(array('x')), 'an empty array' => array()) as $label => $value) {
  $got = null;
  try {
    $got = validStr($value);
  } catch (Throwable $e) {
    $got = get_class($e);
  }
  check("$label is empty", $got, '');
}

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
