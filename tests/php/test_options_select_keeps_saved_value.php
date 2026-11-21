<?php
// A saved option value that is not one of the offered choices (e.g. a locale
// such as en_GB.UTF-8 when the choices only list en_GB) must still appear in
// the select so that saving the options page does not silently drop it.
// php tests/php/test_options_select_keeps_saved_value.php [root]
$root = $argv[1] ?? dirname(__DIR__, 2);
$failed = false;

function check($ok, $msg) {
  echo ($ok ? 'ok' : 'FAIL')." - $msg\n";
  return $ok;
}

require $root.'/web/includes/config_values.php';

$choices = array('' => 'System Default', 'en_GB' => 'en_GB');

if (!function_exists('configSelectChoices')) {
  echo "FAIL - configSelectChoices() is not defined in web/includes/config_values.php\n";
  $failed = true;
} else {
  $r = configSelectChoices($choices, 'en_GB.UTF-8');
  $ok = is_array($r) && array_key_exists('en_GB.UTF-8', $r)
    && array_key_exists('', $r) && array_key_exists('en_GB', $r)
    && count($r) === 3;
  $failed = !check($ok, 'saved value en_GB.UTF-8 added as a choice and original 2 keys kept') || $failed;

  $r = configSelectChoices($choices, 'en_GB');
  $ok = is_array($r) && count($r) === 2
    && array_key_exists('', $r) && array_key_exists('en_GB', $r);
  $failed = !check($ok, 'saved value already in choices returns the 2 choices unchanged') || $failed;

  $r = configSelectChoices($choices, '');
  $ok = is_array($r) && count($r) === 2;
  $failed = !check($ok, 'empty saved value returns the 2 choices unchanged') || $failed;
}

$path = $root.'/web/skins/classic/views/options.php';
$src = @file_get_contents($path);
if ($src === false) {
  echo "FAIL - could not read web/skins/classic/views/options.php\n";
  $failed = true;
} else {
  $calls = substr_count($src, 'htmlSelect("newConfig[$name]"');
  $uses = substr_count($src, 'configSelectChoices(');
  $failed = !check($calls === 2 && $uses >= 2, 'both htmlSelect("newConfig[$name]", ...) calls use configSelectChoices()')
    || $failed;
}

exit($failed ? 1 : 0);
