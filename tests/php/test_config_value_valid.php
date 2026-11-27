<?php
require_once(__DIR__.'/../../web/includes/config_values.php');

$failures = 0;
$passes = 0;
function check($name, $got, $expected) {
  global $failures, $passes;
  if ($got === $expected) {
    $passes++;
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "not ok - $name (got ".var_export($got, true)." expected ".var_export($expected, true).")\n";
  }
}

foreach (array('0', '45', '-1', '86400') as $v)
  check("integer '$v' is valid", configValueIsValid($v, 'integer', '60'), true);
foreach (array('abc', '1.5', '10 ', '0x10', '1e3', '') as $v)
  check("integer '$v' is not valid", configValueIsValid($v, 'integer', '60'), false);
check('empty integer is valid when the default is empty', configValueIsValid('', 'integer', ''), true);

foreach (array('45', '4.5', '-0.5') as $v)
  check("decimal '$v' is valid", configValueIsValid($v, 'decimal', '45'), true);
foreach (array('4.', '.5', 'x', '') as $v)
  check("decimal '$v' is not valid", configValueIsValid($v, 'decimal', '45'), false);

foreach (array('0x7a6d0000', '7a6d', 'FF') as $v)
  check("hexadecimal '$v' is valid", configValueIsValid($v, 'hexadecimal', '0x7a6d0000'), true);
foreach (array('zzz', '0x', '') as $v)
  check("hexadecimal '$v' is not valid", configValueIsValid($v, 'hexadecimal', '0x7a6d0000'), false);

check('a string option takes anything', configValueIsValid('abc', 'string', 'x'), true);
check('a string option takes an empty value', configValueIsValid('', 'string', 'x'), true);
check('a boolean option takes 1', configValueIsValid('1', 'boolean', '0'), true);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
