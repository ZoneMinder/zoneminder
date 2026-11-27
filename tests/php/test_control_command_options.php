<?php
// buildControlCommand() option handling for the PTZ control panel.
//
// * moveStop sent on button release carries the button's xge/yge, so it went through the
//   direction parser: the last letter of "Stop" was taken as the direction, and on a rotated
//   monitor it was rewritten to "moveSto", a method no control module has, so the camera
//   never stopped.
// * A continuous zoom or focus on a control without a speed range never got --autostop,
//   although a continuous move without speeds does.
// * A relative step was scaled by the turbo-adjusted factor, so a pan or tilt step jumped
//   once the control had a turbo speed.
//
// Run as: php tests/php/test_control_command_options.php

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

$stubdir = sys_get_temp_dir().'/zm_control_options_stubs_'.getmypid();
@mkdir($stubdir, 0700, true);
file_put_contents($stubdir.'/logger.php', "<?php\nnamespace ZM;\nfunction Warning(\$s) {}\nfunction Error(\$s) {}\nfunction Debug(\$s) {}\n");
set_include_path($stubdir.PATH_SEPARATOR.get_include_path());

$can_edit = true;
function canEdit($area) { global $can_edit; return $can_edit; }
function validInt($v) { return (int)$v; }
function validJsStr($v) { return addslashes($v); }
function dbFetchOne($sql, $col = NULL, $params = array()) { return false; }
function dbQuery($sql, $params = array()) { return true; }
function deScale($d, $s) { return $d; }

require_once __DIR__.'/../../web/includes/control_functions.php';

class OptControl {
  private $caps;
  public function __construct(array $caps) { $this->caps = $caps; }
  public function __call($fn, $args) { return isset($this->caps[$fn]) ? $this->caps[$fn] : 0; }
}

class OptMonitor {
  private $control;
  private $orientation;
  private $autostop;
  public function __construct($control, $orientation = 'ROTATE_0', $autostop = 0) {
    $this->control = $control;
    $this->orientation = $orientation;
    $this->autostop = $autostop;
  }
  public function Id() { return 1; }
  public function Control() { return $this->control; }
  public function Orientation() { return $this->orientation; }
  public function AutoStopTimeout() { return $this->autostop; }
}

function command_for($monitor, array $request) {
  $_REQUEST = $request;
  return buildControlCommand($monitor);
}

// moveStop on release of a pan/tilt button
$ctrl = new OptControl(array('CanMoveCon' => 1));
foreach (array('ROTATE_0', 'ROTATE_90', 'ROTATE_180', 'ROTATE_270', 'FLIP_HORI', 'FLIP_VERT') as $orientation) {
  $cmd = command_for(new OptMonitor($ctrl, $orientation), array('control' => 'moveStop', 'xge' => 50, 'yge' => 50));
  check("moveStop with xge/yge is unchanged on $orientation", $cmd, ' --command=moveStop');
}


// zoom and focus without a speed range still auto stop
$noSpeed = new OptControl(array('CanZoomCon' => 1, 'CanFocusCon' => 1, 'HasZoomSpeed' => 0, 'HasFocusSpeed' => 0));
$cmd = command_for(new OptMonitor($noSpeed, 'ROTATE_0', 2), array('control' => 'zoomConTele', 'yge' => 50));
check('zoomConTele without a zoom speed gets --autostop', strpos($cmd, '--autostop') !== false, true);
$cmd = command_for(new OptMonitor($noSpeed, 'ROTATE_0', 2), array('control' => 'focusConNear', 'yge' => 50));
check('focusConNear without a focus speed gets --autostop', strpos($cmd, '--autostop') !== false, true);
$cmd = command_for(new OptMonitor($noSpeed, 'ROTATE_0', 0), array('control' => 'zoomConTele', 'yge' => 50));
check('no auto stop timeout, no --autostop', strpos($cmd, '--autostop'), false);

$speed = new OptControl(array('HasZoomSpeed' => 1, 'MinZoomSpeed' => 1, 'MaxZoomSpeed' => 10));
$cmd = command_for(new OptMonitor($speed, 'ROTATE_0', 2), array('control' => 'zoomConTele', 'yge' => 100));
check('fast zoom with a speed range is not auto stopped', strpos($cmd, '--autostop'), false);
$cmd = command_for(new OptMonitor($speed, 'ROTATE_0', 2), array('control' => 'zoomConTele', 'yge' => 10));
check('slow zoom with a speed range is auto stopped', strpos($cmd, '--autostop') !== false, true);

// relative step is not scaled by the turbo adjustment
$turbo = new OptControl(array(
  'HasPanSpeed' => 1, 'MinPanSpeed' => 1, 'MaxPanSpeed' => 10, 'HasTurboPan' => 1, 'TurboPanSpeed' => 20,
  'MinPanStep' => 0, 'MaxPanStep' => 100,
  'HasTiltSpeed' => 1, 'MinTiltSpeed' => 1, 'MaxTiltSpeed' => 10, 'HasTurboTilt' => 1, 'TurboTiltSpeed' => 20,
  'MinTiltStep' => 0, 'MaxTiltStep' => 100,
));
$cmd = command_for(new OptMonitor($turbo), array('control' => 'moveRelRight', 'xge' => 50, 'yge' => 0));
check('pan step at half way is half the step range with turbo pan', preg_match('/--panstep=(\d+)/', $cmd, $m) ? $m[1] : null, '50');
check('pan speed still uses the turbo scale', preg_match('/--panspeed=(\d+)/', $cmd, $m) ? $m[1] : null, '6');
$cmd = command_for(new OptMonitor($turbo), array('control' => 'moveRelDown', 'xge' => 0, 'yge' => 50));
check('tilt step at half way is half the step range with turbo tilt', preg_match('/--tiltstep=(\d+)/', $cmd, $m) ? $m[1] : null, '50');

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
