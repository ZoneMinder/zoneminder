<?php
// Security regression test for the PTZ control path. refs GHSA-qcm7-vq92-f86f
//
// * buildControlCommand() must refuse a presetGoto preset that is not a number: control
//   modules (FOSCAMR2C) looked it up in SQL and sent the result to the camera.
// * It must refuse a control name that is not a plain method name: zmcontrol calls it as a
//   method, and the command string is split back into options on spaces.
// * On 1.36, canView()/canEdit() with a monitor id ALREADY require the area permission
//   ($user[$area] is checked alongside visibleMonitor()), so the master/1.38 auth.php change
//   is not needed here. This test extracts 1.36's canView()/canEdit() from the source and
//   confirms a Control=None viewer of a visible monitor still cannot control it.
//
// Run as: php tests/php/test_control_command.php

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

// ---- buildControlCommand() -------------------------------------------------

$stubdir = sys_get_temp_dir().'/zm_control_command_stubs_'.getmypid();
@mkdir($stubdir, 0700, true);
file_put_contents($stubdir.'/logger.php', "<?php\nnamespace ZM;\nfunction Warning(\$s) {}\nfunction Error(\$s) {}\nfunction Debug(\$s) {}\n");
set_include_path($stubdir.PATH_SEPARATOR.get_include_path());
require_once __DIR__.'/../../web/includes/control_functions.php';

class StubMonitor {
  public function Control() { return null; }
  public function Id() { return 1; }
}

function command_for(array $request) {
  $_REQUEST = $request;
  return buildControlCommand(new StubMonitor());
}

check('numbered presetGoto button is accepted',
  command_for(array('control' => 'presetGoto3')), ' --preset=3 --command=presetGoto');
check('presetGoto with a numeric preset is accepted',
  command_for(array('control' => 'presetGoto', 'preset' => '12')), ' --preset=12 --command=presetGoto');
check('presetGoto with SQL in the preset is refused',
  command_for(array('control' => 'presetGoto', 'preset' => "0/**/UNION/**/SELECT/**/Value/**/FROM/**/Config")), '');
check('presetGoto with extra options in the preset is refused',
  command_for(array('control' => 'presetGoto', 'preset' => '1 --command=quit')), '');
check('plain movement command is accepted',
  command_for(array('control' => 'moveConUp')), ' --command=moveConUp');
check('qualified method name is refused',
  command_for(array('control' => 'ZoneMinder::General::executeShellCommand')), '');
check('control with spaces is refused',
  command_for(array('control' => 'wake --preset=1')), '');
check('missing control is refused', command_for(array()), '');

// ---- canView()/canEdit() with a monitor id (1.36 already gates on the area) ----

$source = file_get_contents(__DIR__.'/../../web/includes/auth.php');
foreach (array('canView', 'canEdit') as $fn) {
  if (!preg_match('/^function '.$fn.'\(.*?^}\n/ms', $source, $m)) {
    echo "FAIL - could not find $fn in auth.php\n";
    exit(1);
  }
  eval($m[0]);
}

// 1.36 stores permissions as an array on the global $user.
$visible = array();
function visibleMonitor($mid) { global $visible; return in_array($mid, $visible); }
function editableMonitor($mid) { global $visible; return in_array($mid, $visible); }

$visible = array(1);
$user = array('Monitors' => 'View', 'Control' => 'None');
check('Control=None viewer of the monitor cannot control it', canView('Control', 1), false);
check('Control=None viewer can still view the monitor', canView('Monitors', 1), true);

$user = array('Monitors' => 'View', 'Control' => 'View');
check('Control=View viewer of the monitor can control it', canView('Control', 1), true);
check('Control=View user cannot control a monitor they cannot see', canView('Control', 2), false);
check('Control=View user cannot edit Control on the monitor', canEdit('Control', 1), false);

$user = array('Monitors' => 'Edit', 'Control' => 'Edit');
check('Control=Edit editor of the monitor can edit Control', canEdit('Control', 1), true);
check('Monitors=Edit user can edit the monitor', canEdit('Monitors', 1), true);

array_map('unlink', glob($stubdir.'/*'));
@rmdir($stubdir);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
