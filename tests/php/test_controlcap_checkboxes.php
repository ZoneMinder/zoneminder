<?php
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

$view = file_get_contents(__DIR__.'/../../web/skins/classic/views/controlcap.php');
$action = file_get_contents(__DIR__.'/../../web/includes/actions/controlcap.php');

preg_match_all('/type="checkbox" name="Control\[(\w+)\]"/', $view, $m);
$checkboxes = array_unique($m[1]);

preg_match('/\$field_defaults = array\((.*?)\n  \);/s', $action, $block);
preg_match_all("/'(\w+)'\s*=>/", $block[1], $m);
$defaults = $m[1];

check('the form has checkboxes', count($checkboxes) > 0, true);
foreach ($checkboxes as $name) {
  check("unchecked $name is saved as 0", in_array($name, $defaults), true);
}

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
