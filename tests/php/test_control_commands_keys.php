<?php
// The PTZ panel (skins/classic/includes/control_functions.php) reads $cmds[...] from
// ZM\Control::commands(). With continuous white balance the centre button reads
// $cmds['WhiteStop'], which commands() never set: a PHP undefined key warning and a button
// whose value is empty, so it sent nothing.
//
// Run as: php tests/php/test_control_commands_keys.php

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

$stubdir = sys_get_temp_dir().'/zm_control_keys_stubs_'.getmypid();
@mkdir($stubdir, 0700, true);
file_put_contents($stubdir.'/logger.php', "<?php\nnamespace ZM;\nfunction Warning(\$s) {}\nfunction Error(\$s) {}\nfunction Debug(\$s) {}\n");
file_put_contents($stubdir.'/database.php', "<?php\nfunction dbFetchOne(\$sql, \$types=NULL, \$params=array()) { return array(); }\nfunction dbFetchAll(\$sql, \$types=NULL, \$params=array()) { return array(); }\n");
set_include_path($stubdir.PATH_SEPARATOR.get_include_path());

require_once __DIR__.'/../../web/includes/Object.php';
require_once __DIR__.'/../../web/includes/Control.php';

$skin = file_get_contents(__DIR__.'/../../web/skins/classic/includes/control_functions.php');
preg_match_all('/\$cmds\[\'([A-Za-z]+)\'\]/', $skin, $m);
$used = array_unique($m[1]);

foreach (array('Con', 'Rel', 'Abs') as $kind) {
  $row = array('Id' => 1, 'CanMove' => 1, 'CanZoom' => 1, 'CanFocus' => 1, 'CanIris' => 1, 'CanWhite' => 1, 'CanGain' => 1);
  foreach (array('Move', 'Zoom', 'Focus', 'Iris', 'White', 'Gain') as $axis) $row['Can'.$axis.$kind] = 1;
  $cmds = (new \ZM\Control($row))->commands();
  $missing = array();
  foreach ($used as $key) {
    if (!isset($cmds[$key]) and !($kind != 'Con' and preg_match('/Stop$/', $key))) $missing[] = $key;
  }
  sort($missing);
  check("every key the ptz panel reads is set for $kind controls", $missing, array());
}

$cmds = (new \ZM\Control(array('Id' => 1, 'CanWhite' => 1, 'CanWhiteCon' => 1)))->commands();
check('continuous white balance has a stop command', isset($cmds['WhiteStop']) ? $cmds['WhiteStop'] : null, 'whiteStop');

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
