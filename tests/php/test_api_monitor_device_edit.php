<?php
// Monitor::validDevicePath() looked for the Type in the request data only. An API edit that
// sends just a Device left it empty, so a Local monitor accepted a Device that is not a /dev/
// path (a create is refused). The stored Type is now used when the request does not carry one.
//
//   php tests/php/test_api_monitor_device_edit.php [repo-root]

class App { public static function uses($class, $package) {} }

// Minimal stand-in for CakePHP's Model: field() answers from the stored row.
class AppModel {
  public $id = false;
  public $data = array();
  public static $stored = array();
  public function field($name) { return isset(self::$stored[$name]) ? self::$stored[$name] : false; }
}

$repoRoot = $argv[1] ?? realpath(__DIR__.'/../..');
require_once "$repoRoot/web/api/app/Model/Monitor.php";

$passes = 0;
$failures = 0;
function check($ok, $what) {
  global $passes, $failures;
  if ($ok) { $passes++; echo "ok - $what\n"; } else { $failures++; echo "not ok - $what\n"; }
}

function valid($id, $data, $stored, $device) {
  $m = new Monitor();
  $m->id = $id;
  $m->data = array('Monitor' => $data);
  AppModel::$stored = $stored;
  return $m->validDevicePath(array('Device' => $device));
}

check(valid(false, array('Type' => 'Local'), array(), 'garbage') === false, 'create of a Local monitor refuses a non-/dev/ Device');
check(valid(false, array('Type' => 'Local'), array(), '/dev/video0') === true, 'create of a Local monitor accepts /dev/video0');
check(valid(3, array('Type' => 'Remote'), array('Type' => 'Local'), 'http://cam/x') === true, 'edit that sets Type Remote accepts a URL');
check(valid(3, array(), array('Type' => 'Local'), 'video0; reboot') === false, 'edit of a stored Local monitor with only a Device refuses a non-/dev/ path');
check(valid(3, array(), array('Type' => 'Local'), '/dev/video1') === true, 'edit of a stored Local monitor accepts /dev/video1');
check(valid(3, array(), array('Type' => 'Remote'), 'http://cam/x') === true, 'edit of a stored Remote monitor keeps a URL Device');
check(valid(3, array(), array('Type' => 'Local'), '') === true, 'an empty Device is still allowed');

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
