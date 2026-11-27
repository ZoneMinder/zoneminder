<?php
// A boolean option set in zm.conf or conf.d may be written as a word. The box owner's
// file value wins over the Config table, and a word used to reach PHP as written, so
// ZM_X=off was the string 'off': truthy, so the option was on. configFileValue() reads
// it as the Config table would hold it, the same way as ConfigFileValue() in
// src/zm_config.cpp and ZoneMinder::Config do.
//
// Run: sudo -u www-data php tests/php/test_config_file_booleans.php
//      (from the repo root, on a tree with a readable zm.conf)

set_include_path(__DIR__.'/../../web/includes'.PATH_SEPARATOR.get_include_path());
require_once('config.php');

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

foreach (array('1', 'yes', 'Yes', 'true', 'on', ' on ') as $v)
  check("boolean '$v' is on", configFileValue($v, 'boolean'), '1');
foreach (array('0', 'no', 'false', 'off', 'OFF') as $v)
  check("boolean '$v' is off", configFileValue($v, 'boolean'), '0');
check('a boolean that is not a word stays as written', configFileValue('2', 'boolean'), '2');
check('a string option keeps a word', configFileValue('Yes', 'string'), 'Yes');
check('an integer option keeps its value', configFileValue('5', 'integer'), '5');

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
