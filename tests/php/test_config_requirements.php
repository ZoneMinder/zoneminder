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

$values = array('ZM_OPT_UPLOAD' => '0', 'ZM_OPT_USE_AUTH' => '1', 'ZM_AUTH_RELAY' => 'hashed');

check('a requirement that holds', configRequirementsMet('ZM_OPT_USE_AUTH=1', $values), true);
check('a requirement that does not hold', configRequirementsMet('ZM_OPT_UPLOAD=1', $values), false);
check('every condition of a compound requirement holds',
  configRequirementsMet('ZM_OPT_USE_AUTH=1;ZM_AUTH_RELAY=hashed', $values), true);
check('one condition of a compound requirement fails',
  configRequirementsMet('ZM_OPT_USE_AUTH=1;ZM_AUTH_RELAY=plain', $values), false);
check('an option that does not exist is not met', configRequirementsMet('ZM_NO_SUCH=1', $values), false);
check('a malformed requirement is not met', configRequirementsMet('ZM_OPT_USE_AUTH', $values), false);
check('white space around a condition is ignored',
  configRequirementsMet('ZM_OPT_USE_AUTH=1; ZM_AUTH_RELAY=hashed', $values), true);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
