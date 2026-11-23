<?php
// The API monitor view returns the camera credentials (User, Pass, ONVIF_Username,
// ONVIF_Password and the user:pass@ part of Host, Path, SecondPath, ONVIF_URL and
// ControlAddress)
// only to a user who can edit that monitor.
// Run as: php tests/php/test_api_monitors_hide_credentials.php [root]

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
  if (!preg_match('/(public|private|protected) function '.$name.'\(/', $source, $m, PREG_OFFSET_CAPTURE)) {
    return '';
  }
  $start = $m[0][1];
  $open = strpos($source, '{', $start);
  $depth = 0;
  for ($i = $open; $i < strlen($source); $i++) {
    if ($source[$i] == '{') $depth++;
    else if ($source[$i] == '}' && --$depth == 0) break;
  }
  return substr($source, $start, $i - $start + 1);
}

$empty = tempnam(sys_get_temp_dir(), 'zmcred');
$fix = function($m) use ($empty) {
  return str_replace('__DIR__ .\'/../../../includes/Monitor.php\'', var_export($empty, true), $m);
};

if (!class_exists('NotFoundException')) { class NotFoundException extends Exception {} }
if (!class_exists('UnauthorizedException')) { class UnauthorizedException extends Exception {} }
if (!function_exists('__')) { function __($s) { return $s; } }

eval('namespace ZM;
class Monitor {
  static $canEdit = false;
  function __construct($row = null) {}
  function canEdit() { return self::$canEdit; }
  function canView() { return true; }
  function JanusEnabled() { return false; }
}');

$row = array(
  'Id' => 7, 'Name' => 'Porch', 'User' => 'admin', 'Pass' => 'hunter2',
  'ONVIF_Username' => 'onvif', 'ONVIF_Password' => 'onvifpass',
  'Host' => 'root:s3cret@cam.example', 'Path' => 'rtsp://admin:hunter2@10.0.0.9:554/live?x=a@b',
  'SecondPath' => 'rtsp://u:p@10.0.0.9/sub', 'ONVIF_URL' => 'http://o:q@10.0.0.9/onvif',
  'ControlAddress' => 'user:pw@192.168.1.5:80',
);

class FakeModel {
  public $row; public $recursive; public $primaryKey = 'Id';
  function exists($id = null) { return true; }
  function find($t = 'first', $o = array()) { return array('Monitor' => $this->row); }
}
class FakeController {
  public $Monitor; public $out;
  function set($a) { $this->out = $a; }
}
$app = file_get_contents($root.'/web/api/app/Controller/AppController.php');
eval('class FakeCtl extends FakeController { '.$fix(extractMethod($source, 'view')).' '
  .extractMethod($app, 'monitorCredentialColumns').' '.extractMethod($app, 'hideCredentials').' }');
class FakeCtlColumns extends FakeCtl {
  function credentialColumns() { return $this->monitorCredentialColumns(); }
}

foreach (array(false, true) as $canEdit) {
  ZM\Monitor::$canEdit = $canEdit;
  $c = new FakeCtl();
  $c->Monitor = new FakeModel();
  $c->Monitor->row = $row;
  $c->view(7);
  $m = $c->out['monitor']['Monitor'];
  $who = $canEdit ? 'editor' : 'viewer';
  if ($canEdit) {
    check("$who gets the row as stored", $m, $row);
    continue;
  }
  check("$who gets no Pass", $m['Pass'], '');
  check("$who gets no User", $m['User'], '');
  check("$who gets no ONVIF password", $m['ONVIF_Password'], '');
  check("$who gets no ONVIF user name", $m['ONVIF_Username'], '');
  check("$who gets no credentials in Host", $m['Host'], 'cam.example');
  check("$who gets no credentials in Path", $m['Path'], 'rtsp://10.0.0.9:554/live?x=a@b');
  check("$who gets no credentials in SecondPath", $m['SecondPath'], 'rtsp://10.0.0.9/sub');
  check("$who gets no credentials in ONVIF_URL", $m['ONVIF_URL'], 'http://10.0.0.9/onvif');
  check("$who gets no credentials in ControlAddress", $m['ControlAddress'], '192.168.1.5:80');
  check("$who still gets the name", $m['Name'], 'Porch');
}
check('credential columns include ControlAddress', in_array('ControlAddress', (new FakeCtlColumns())->credentialColumns(), true), true);
unlink($empty);
exit($failures ? 1 : 0);
