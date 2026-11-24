<?php
// The console starts (or stops, when no longer eligible) zmtrack.pl after saving a
// monitor. add() and edit() in the API only restarted zmc and zmcontrol, so a
// TrackMotion change made through the API never reached zmtrack.pl.
// Run as: php tests/php/test_api_monitors_save_zmtrack.php [root]

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$file = $root.'/web/api/app/Controller/MonitorsController.php';
if (!is_file($file)) { echo "FAIL - controller not found at $file\n"; exit(1); }
$source = file_get_contents($file);

function extractMethod($source, $name) {
  $start = strpos($source, 'public function '.$name.'(');
  if ($start === false) { echo "FAIL - $name() not found\n"; exit(1); }
  $open = strpos($source, '{', $start);
  $depth = 0;
  for ($i = $open; $i < strlen($source); $i++) {
    if ($source[$i] == '{') $depth++;
    else if ($source[$i] == '}' && --$depth == 0) break;
  }
  return substr($source, $start, $i - $start + 1);
}

$empty = tempnam(sys_get_temp_dir(), 'zmtrack');
$fix = function($m) use ($empty) {
  return str_replace(
    array('__DIR__ .\'/../../../includes/Monitor.php\'', '__DIR__ .\'/../../../includes/Zone.php\''),
    array(var_export($empty, true), var_export($empty, true)), $m);
};

if (!class_exists('NotFoundException')) { class NotFoundException extends Exception {} }
if (!class_exists('UnauthorizedException')) { class UnauthorizedException extends Exception {} }
if (!class_exists('BadRequestException')) { class BadRequestException extends Exception {} }
if (!function_exists('__')) { function __($s) { return $s; } }

eval('namespace ZM;
class Zone { function save($a) {} }
class Monitor {
  static $calls = array();
  private $row;
  function __construct($row = null) { $this->row = is_array($row) ? $row : array(); }
  function canEdit() { return true; }
  function commandFieldChangesNotAllowed($f) { return array(); }
  function Type() { return "Ffmpeg"; }
  function Controllable() { return false; }
  function Capturing() { return "Always"; }
  function Deleted() { return false; }
  function zmcControl($c) { self::$calls[] = "zmc:$c"; }
  function sendControlCommand($c) { self::$calls[] = "ctl:$c"; }
  function zmtrackControl($c) { self::$calls[] = "zmtrack:$c"; }
}');

class FakeModel {
  public $id = 7;
  function exists($id = null) { return true; }
  function create() {}
  function find($t = 'first', $o = array()) { return array('Monitor' => array('Id' => 7)); }
  function save($d = null) { return true; }
  function validates() { return true; }
}
class FakeRequest {
  public $data = array();
  public $m;
  function __construct($m) { $this->m = $m; }
  function is($t) { return in_array($this->m, (array)$t, true); }
}
class FakeController {
  public $Monitor; public $request; public $out;
  function set($a) { $this->out = $a; }
  function requestCommandFields() { return array(); }
  function pinRequestId($m, $id) {}
  protected function resizeZones($id, $old, $new) {}
}
eval('class FakeCtl extends FakeController { '.$fix(extractMethod($source, 'edit')).' '.$fix(extractMethod($source, 'add')).' }');

foreach (array('edit', 'add') as $which) {
  ZM\Monitor::$calls = array();
  $c = new FakeCtl();
  $c->Monitor = new FakeModel();
  $c->request = new FakeRequest('post');
  global $user; $user = null;
  if ($which == 'edit') $c->edit(7); else $c->add();
  check("$which starts zmtrack.pl after zmc", in_array('zmtrack:start', ZM\Monitor::$calls, true), true);
}
exit($failures ? 1 : 0);
