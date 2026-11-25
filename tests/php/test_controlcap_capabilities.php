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

$base = __DIR__.'/../../';
$view_path = isset($argv[1]) ? $argv[1] : $base.'web/skins/classic/views/controlcap.php';
$action_path = isset($argv[2]) ? $argv[2] : $base.'web/includes/actions/controlcap.php';
$control_path = isset($argv[3]) ? $argv[3] : $base.'web/includes/Control.php';

$view = file_get_contents($view_path);
$action = file_get_contents($action_path);
$control = file_get_contents($control_path);

preg_match('/\$defaults = array\((.*?)\n\s*\);/s', $control, $block);
preg_match_all("/'(Can\w+)'\s*=>\s*0\s*,/", $block[1], $m);
$capabilities = array_values(array_unique($m[1]));

preg_match('/\$field_defaults = array\((.*?)\n\s*\);/s', $action, $block);
preg_match_all("/'(\w+)'\s*=>/", $block[1], $m);
$defaults = $m[1];

check('the view file was read', $view !== false, true);
check('the action file was read', $action !== false, true);
check('Control.php defines Can capabilities', count($capabilities) > 0, true);

foreach ($capabilities as $name) {
  check(
    "$name is an editable checkbox",
    preg_match('/type="checkbox" name="Control\['.preg_quote($name, '/').'\]"/', $view),
    1
  );
  check(
    "$name is cleared on save",
    in_array($name, $defaults, true),
    true
  );
}

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
