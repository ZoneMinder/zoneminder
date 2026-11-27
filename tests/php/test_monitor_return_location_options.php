<?php
// The Return Location list on the Control tab of views/monitor.php was a fixed None, Home and
// Preset 1. A monitor whose location is Preset 2 or higher has no such option, so the form drew
// None as selected and the next save, for any reason, stored -1 and zmtrack stopped returning.
// The list now follows the monitor's own control and always keeps the saved location.
//
// Run as: php tests/php/test_monitor_return_location_options.php [repo root]

$root = $argv[1] ?? dirname(__DIR__, 2);
$src = file_get_contents($root.'/web/skins/classic/views/monitor.php');
if (!preg_match('/^\s*(\$return_options = .*?)^echo htmlSelect\(\'newMonitor\[ReturnLocation\]\'/ms', $src, $m)) {
  echo "FAIL - could not find the return location options in monitor.php\n";
  exit(1);
}
$block = $m[1];

function translate($s) { return $s; }
class StubControl {
  public $id, $home, $presets;
  function __construct($id, $home, $presets) { $this->id = $id; $this->home = $home; $this->presets = $presets; }
  function Id() { return $this->id; }
  function HasHomePreset() { return $this->home; }
  function NumPresets() { return $this->presets; }
}
class StubMonitor {
  public $control, $location;
  function __construct($control, $location) { $this->control = $control; $this->location = $location; }
  function ControlId() { return $this->control; }
  function ReturnLocation() { return $this->location; }
}

$failures = 0;
function options($controlId, $location) {
  global $block;
  $controls = array(new StubControl(3, 1, 2), new StubControl(7, 0, 4));
  $monitor = new StubMonitor($controlId, $location);
  $return_options = null;
  eval($block);
  $out = array();
  foreach ($return_options as $k => $v) $out[] = $k.'='.$v;
  return implode(',', $out);
}
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) {
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "FAIL - $name (got $got, want $want)\n";
  }
}

check('control with a Home preset lists Home and its presets', options(3, '-1'), '-1=None,0=Home,1=Preset 1,2=Preset 2');
check('control without a Home preset lists only its presets', options(7, '-1'), '-1=None,1=Preset 1,2=Preset 2,3=Preset 3,4=Preset 4');
check('a saved preset above 1 is listed', options(7, '3'), '-1=None,1=Preset 1,2=Preset 2,3=Preset 3,4=Preset 4');
check('a saved preset the control no longer has is kept', options(3, '5'), '-1=None,0=Home,1=Preset 1,2=Preset 2,5=Preset 5');
check('a saved Home the control does not list is kept', options(7, '0'), '-1=None,1=Preset 1,2=Preset 2,3=Preset 3,4=Preset 4,0=Home');
check('no control lists only None', options('', '-1'), '-1=None');
check('no control keeps a saved preset', options('', '2'), '-1=None,2=Preset 2');
exit($failures ? 1 : 0);
