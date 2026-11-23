<?php
// Unit and regression tests for PTZ control bugs in:
//   - web/includes/control_functions.php
//   - web/skins/classic/includes/control_functions.php
//   - web/includes/Control.php
//
// Run as: php tests/php/test_control_bugs.php

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

// Set up stubs for database and logging
$stubdir = sys_get_temp_dir().'/zm_control_bugs_stubs_'.getmypid();
@mkdir($stubdir, 0700, true);
file_put_contents($stubdir.'/logger.php', "<?php\nnamespace ZM;\nfunction Warning(\$s) {}\nfunction Error(\$s) {}\nfunction Debug(\$s) {}\n");
file_put_contents($stubdir.'/database.php', "<?php\nfunction dbFetchOne(\$sql, \$types=NULL, \$params=array()) { return array('Label'=>'OldLabel'); }\nfunction dbQuery(\$sql, \$params=array()) { return true; }\nfunction dbFetchAll(\$sql, \$types=NULL, \$params=array()) { return array(); }\n");
set_include_path($stubdir.PATH_SEPARATOR.get_include_path());

if (!defined('SCALE_BASE')) define('SCALE_BASE', 100);

function deScale($dimension, $dummy) {
  $new_dimension = $dimension;
  for ( $i = 1; $i < func_num_args(); $i++ ) {
    $scale = func_get_arg($i);
    if ( !empty($scale) && $scale != SCALE_BASE )
      $new_dimension = (int)(($new_dimension*SCALE_BASE)/$scale);
  }
  return $new_dimension;
}

function canEdit($area) { return true; }
function validInt($v) { return (int)$v; }
function validJsStr($v) { return addslashes($v); }
function validHtmlStr($v) { return htmlspecialchars($v); }

// The path to control_functions.php can be overridden (e.g. to test the
// pre-fix version) via the ZM_TEST_CONTROL_FUNCTIONS environment variable.
$zm_control_functions = getenv('ZM_TEST_CONTROL_FUNCTIONS');
if (!$zm_control_functions) $zm_control_functions = __DIR__.'/../../web/includes/control_functions.php';
require_once $zm_control_functions;
require_once __DIR__.'/../../web/includes/Object.php';
require_once __DIR__.'/../../web/includes/Control.php';

class MockControl extends \ZM\Control {
  private $caps = array();
  public function __construct(array $caps = array()) {
    parent::__construct();
    $this->caps = $caps;
  }
  public function __call($fn, array $args = array()) {
    if (array_key_exists($fn, $this->caps)) {
      return $this->caps[$fn];
    }
    return parent::__call($fn, $args);
  }
}

class MockMonitor {
  private $orientation;
  private $control;
  private $width;
  private $height;
  public function __construct($orientation = 'ROTATE_0', $control = null, $width = 640, $height = 480) {
    $this->orientation = $orientation;
    $this->control = $control ?: new MockControl();
    $this->width = $width;
    $this->height = $height;
  }
  public function Id() { return 1; }
  public function Control() { return $this->control; }
  public function Orientation() { return $this->orientation; }
  public function Width() { return $this->width; }
  public function Height() { return $this->height; }
  // The size of the image the user clicks on: width and height swap when rotated 90 or 270.
  private function rotated() { return in_array($this->orientation, array('ROTATE_90', 'ROTATE_270')); }
  public function ViewWidth() { return $this->rotated() ? $this->height : $this->width; }
  public function ViewHeight() { return $this->rotated() ? $this->width : $this->height; }
  public function AutoStopTimeout() { return 0; }
}

// ----------------------------------------------------------------------------
// Test 1: movePseudoMap coordinate factor rotation (ROTATE_90 and ROTATE_270)
// ----------------------------------------------------------------------------
// Monitor 640x480 rotated 90 or 270 is displayed 480x640, so the click is read against
// halfWidth=240, halfHeight=320 (ViewWidth/ViewHeight).
// Click at x=120, y=160:
// unrotated xFactor = (120 - 240)/240 = -0.5, yFactor = (160 - 320)/320 = -0.5
// ROTATE_90:
// Correct: yFactor = -(-0.5) = 0.5, xFactor = -0.5 -> dirn = 'DownLeft'
// Pan step (using |xFactor|=0.5): MinPanStep(1) + (MaxPanStep(10)-1)*0.5 = 1 + 4.5 = 6 (<= 10)
// With bug: $tempYFactor = $y = 160, $xFactor = 160! Pan step becomes 1 + 9*160 = 1441!
$ctrl = new MockControl(array(
  'HasPanSpeed' => 1, 'MinPanSpeed' => 1, 'MaxPanSpeed' => 10, 'HasTurboPan' => 0,
  'HasTiltSpeed' => 1, 'MinTiltSpeed' => 1, 'MaxTiltSpeed' => 10, 'HasTurboTilt' => 0,
  'MinPanStep' => 1, 'MaxPanStep' => 10,
  'MinTiltStep' => 1, 'MaxTiltStep' => 10,
));
$mon90 = new MockMonitor('ROTATE_90', $ctrl, 640, 480);
$_REQUEST = array('control' => 'movePseudoMap', 'x' => 120, 'y' => 160, 'scale' => 100);
$cmd = buildControlCommand($mon90);

preg_match('/--panstep=(\d+)/', $cmd, $mPan);
$panStep = isset($mPan[1]) ? (int)$mPan[1] : 0;
check('movePseudoMap ROTATE_90 panstep is within max bounds', ($panStep >= 1 && $panStep <= 10), true);
check('movePseudoMap ROTATE_90 command is moveRelDownLeft', $_REQUEST['control'], 'moveRelDownLeft');

// ROTATE_270:
// Correct: xFactor = -(-0.5) = 0.5, yFactor = -0.5 -> dirn = 'UpRight'
// Tilt step (using |yFactor|=0.5): MinTiltStep(1) + 9*0.5 = 5 (<= 10)
// With bug: $tempXFactor = $x = 120, $yFactor = 120! Tilt step becomes 1 + 9*120 = 1081!
$mon270 = new MockMonitor('ROTATE_270', $ctrl, 640, 480);
$_REQUEST = array('control' => 'movePseudoMap', 'x' => 120, 'y' => 160, 'scale' => 100);
$cmd = buildControlCommand($mon270);
preg_match('/--tiltstep=(\d+)/', $cmd, $mTilt);
$tiltStep = isset($mTilt[1]) ? (int)$mTilt[1] : 0;
check('movePseudoMap ROTATE_270 tiltstep is within max bounds', ($tiltStep >= 1 && $tiltStep <= 10), true);
check('movePseudoMap ROTATE_270 command is moveRelUpRight', $_REQUEST['control'], 'moveRelUpRight');

// ----------------------------------------------------------------------------
// Test 2: moveConMap coordinate factor rotation (ROTATE_90 and ROTATE_270)
// ----------------------------------------------------------------------------
$mon90 = new MockMonitor('ROTATE_90', $ctrl, 640, 480);
$_REQUEST = array('control' => 'moveConMap', 'x' => 120, 'y' => 160, 'scale' => 100);
$cmd = buildControlCommand($mon90);
preg_match('/--panspeed=(\d+)/', $cmd, $mPanSpeed);
$panSpeed = isset($mPanSpeed[1]) ? (int)$mPanSpeed[1] : 0;
check('moveConMap ROTATE_90 panspeed is within max bounds', ($panSpeed >= 1 && $panSpeed <= 10), true);
check('moveConMap ROTATE_90 command is moveConDownLeft', $_REQUEST['control'], 'moveConDownLeft');

$mon270 = new MockMonitor('ROTATE_270', $ctrl, 640, 480);
$_REQUEST = array('control' => 'moveConMap', 'x' => 120, 'y' => 160, 'scale' => 100);
$cmd = buildControlCommand($mon270);
preg_match('/--tiltspeed=(\d+)/', $cmd, $mTiltSpeed);
$tiltSpeed = isset($mTiltSpeed[1]) ? (int)$mTiltSpeed[1] : 0;
check('moveConMap ROTATE_270 tiltspeed is within max bounds', ($tiltSpeed >= 1 && $tiltSpeed <= 10), true);
check('moveConMap ROTATE_270 command is moveConUpRight', $_REQUEST['control'], 'moveConUpRight');

// ----------------------------------------------------------------------------
// Test 3: Undefined $panSpeed / $tiltSpeed in movePseudoMap & moveConMap when HasPanSpeed=0
// ----------------------------------------------------------------------------
$ctrlNoSpeed = new MockControl(array(
  'HasPanSpeed' => 0,
  'HasTiltSpeed' => 0,
  'MinPanStep' => 1, 'MaxPanStep' => 10,
  'MinTiltStep' => 1, 'MaxTiltStep' => 10,
));
$monNoSpeed = new MockMonitor('ROTATE_0', $ctrlNoSpeed, 640, 480);

$_REQUEST = array('control' => 'movePseudoMap', 'x' => 480, 'y' => 360, 'scale' => 100);
$warning_triggered = false;
set_error_handler(function($errno, $errstr) use (&$warning_triggered) {
  if (strpos($errstr, 'Undefined variable') !== false) {
    $warning_triggered = true;
  }
});
$cmd = buildControlCommand($monNoSpeed);
restore_error_handler();
check('movePseudoMap with HasPanSpeed=0 triggers no undefined variable warning', $warning_triggered, false);
check('movePseudoMap with HasPanSpeed=0 does not append invalid --panspeed=', strpos($cmd, '--panspeed='), false);

$_REQUEST = array('control' => 'moveConMap', 'x' => 480, 'y' => 360, 'scale' => 100);
$warning_triggered = false;
set_error_handler(function($errno, $errstr) use (&$warning_triggered) {
  if (strpos($errstr, 'Undefined variable') !== false) {
    $warning_triggered = true;
  }
});
$cmd = buildControlCommand($monNoSpeed);
restore_error_handler();
check('moveConMap with HasPanSpeed=0 triggers no undefined variable warning', $warning_triggered, false);
check('moveConMap with HasPanSpeed=0 does not append invalid --panspeed=', strpos($cmd, '--panspeed='), false);

// ----------------------------------------------------------------------------
// Test 4: Direction rotation regex in buildControlCommand
// ----------------------------------------------------------------------------
// Under ROTATE_90, moveRelUp should become moveRelLeft
$mon90 = new MockMonitor('ROTATE_90', $ctrl, 640, 480);
$_REQUEST = array('control' => 'moveRelUp', 'xge' => 50, 'yge' => 50);
$cmd = buildControlCommand($mon90);
check('buildControlCommand ROTATE_90 rotates moveRelUp to moveRelLeft', strpos($cmd, '--command=moveRelLeft') !== false, true);

// Under ROTATE_90 with x/y coordinates (lines 508-706)
$_REQUEST = array('control' => 'moveRelUp', 'x' => 10, 'y' => 10);
$cmd = buildControlCommand($mon90);
check('buildControlCommand ROTATE_90 rotates moveRelUp to moveRelLeft for x/y', strpos($cmd, '--command=moveRelLeft') !== false, true);

// ----------------------------------------------------------------------------
// Test 6: presetGoto accepts preset 0
// ----------------------------------------------------------------------------
$_REQUEST = array('control' => 'presetGoto', 'preset' => '0');
$cmd = buildControlCommand($monNoSpeed);
check('presetGoto with preset 0 includes --preset=0', strpos($cmd, '--preset=0') !== false, true);

// ----------------------------------------------------------------------------
// Test 7: CanAutoZoom default in Control::$defaults
// ----------------------------------------------------------------------------
$controlObj = new \ZM\Control();
$warning_triggered = false;
set_error_handler(function($errno, $errstr) use (&$warning_triggered) {
  $warning_triggered = true;
});
$autoZoom = $controlObj->CanAutoZoom();
restore_error_handler();
check('CanAutoZoom() does not trigger unknown function warning', $warning_triggered, false);
check('CanAutoZoom() returns default 0', $autoZoom, 0);

// ----------------------------------------------------------------------------
// Test 8: Typo data-ytell="1)" in controlWhite()
// ----------------------------------------------------------------------------
$skinFuncs = file_get_contents(__DIR__.'/../../web/skins/classic/includes/control_functions.php');
check('controlWhite does not have typo data-ytell="1)"', strpos($skinFuncs, 'data-ytell="1)"'), false);

// ----------------------------------------------------------------------------
// Test 9: moveMap sends the point in the camera's own frame
// ----------------------------------------------------------------------------
// A 640x480 camera rotated 90 or 270 is shown 480x640. Image::Rotate(90) puts camera
// pixel 160,360 at 119,160 of the rotated image, and Rotate(270) puts 480,120 at 120,159.
$map = array(
  'ROTATE_0' => '--xcoord=120 --ycoord=160',
  'ROTATE_90' => '--xcoord=160 --ycoord=360',
  'ROTATE_180' => '--xcoord=520 --ycoord=320',
  'ROTATE_270' => '--xcoord=480 --ycoord=120',
  'FLIP_HORI' => '--xcoord=520 --ycoord=160',
  'FLIP_VERT' => '--xcoord=120 --ycoord=320',
);
foreach ($map as $orientation => $want) {
  $_REQUEST = array('control' => 'moveMap', 'x' => 120, 'y' => 160, 'scale' => 100);
  $cmd = buildControlCommand(new MockMonitor($orientation, $ctrl, 640, 480));
  preg_match('/--xcoord=\S+ --ycoord=\S+/', $cmd, $m);
  check("moveMap $orientation sends $want", isset($m[0]) ? $m[0] : $cmd, $want);
}

// ----------------------------------------------------------------------------
// Test 10: movePseudoMap & moveConMap with a monitor of zero size must not
// divide by zero (DivisionByZeroError on PHP 8), but log and return ''
// ----------------------------------------------------------------------------
$monZero = new MockMonitor('ROTATE_0', $ctrl, 0, 0);
foreach (array('movePseudoMap', 'moveConMap') as $mapControl) {
  $_REQUEST = array('control' => $mapControl, 'x' => 120, 'y' => 160, 'scale' => 100);
  $threw = '';
  $cmd = null;
  try {
    $cmd = buildControlCommand($monZero);
  } catch ( Throwable $e ) {
    $threw = get_class($e).': '.$e->getMessage();
  }
  check("$mapControl with zero ViewWidth/ViewHeight does not throw", $threw, '');
  check("$mapControl with zero ViewWidth/ViewHeight returns empty command", $cmd, '');
}

// Control case: a monitor with non-zero size still builds a real command.
$_REQUEST = array('control' => 'movePseudoMap', 'x' => 120, 'y' => 160, 'scale' => 100);
$cmd = buildControlCommand(new MockMonitor('ROTATE_0', $ctrl, 640, 480));
check('movePseudoMap with non-zero ViewWidth/ViewHeight still returns a command', strpos($cmd, '--command=') !== false, true);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
