<?php
// ConfigsController::filterReferencesValue() decides whether a caller's named filter
// tests the Config.Value column. index() uses it to drop secret rows from the result
// so the returned row count cannot be used as an oracle to read a Private value such
// as ZM_AUTH_HASH_SECRET one character at a time (GHSA-8g63-hqg8-82cv). The detection
// must catch Value with any operator and a Model.Value prefix, and must not fire on
// other columns such as Name or DefaultValue.
//
// ConfigsController can't be loaded without CakePHP, so the method is taken from the
// source and run in a stand-in.
//
// Run as: php tests/php/test_config_value_filter.php

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

$source = file_get_contents(__DIR__.'/../../web/api/app/Controller/ConfigsController.php');
if (!preg_match('/^  private function filterReferencesValue\(.*?^  }\n/ms', $source, $m)) {
  echo "FAIL - could not find filterReferencesValue in ConfigsController.php\n";
  exit(1);
}
eval('class FilterHarness { '.str_replace('private function', 'public function', $m[0]).' }');

function refs($named) {
  $h = new FilterHarness();
  return $h->filterReferencesValue($named);
}

check('Value with LIKE is detected', refs(array('Value LIKE' => 'a%')), true);
check('bare Value is detected', refs(array('Value' => 'x')), true);
check('Value with a comparison operator is detected', refs(array('Value >' => '5')), true);
check('Model-qualified Config.Value is detected', refs(array('Config.Value LIKE' => 'a%')), true);
check('detection is case-insensitive', refs(array('value' => 'x')), true);
check('Value among other filters is detected', refs(array('Name LIKE' => 'ZM_%', 'Value LIKE' => 'a%')), true);

check('Name filter does not match', refs(array('Name' => 'ZM_AUTH_HASH_SECRET')), false);
check('DefaultValue is a different column', refs(array('DefaultValue' => 'x')), false);
check('a column ending in value but not Value does not match', refs(array('DefaultValue LIKE' => 'x%')), false);
check('no filter does not match', refs(array()), false);
check('unrelated columns do not match', refs(array('Name LIKE' => 'ZM_%', 'Id >' => '3')), false);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
