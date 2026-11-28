<?php
// The console refuses to save an Ffmpeg monitor whose source Path has no protocol (includes/actions/monitor.php),
// because ffmpeg cannot open it and zmdc restarts zmc in a loop. The API saved it. The Monitor model now refuses it
// for an Ffmpeg monitor, allows absolute paths and the v4l2: form, and never refuses a Path an edit leaves as stored
// or an edit that does not send a Path.
//
//   php tests/php/test_api_monitor_ffmpeg_path.php [repo-root]

class App { public static function uses($class, $package) {} }
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

$m = new Monitor();
$rule = isset($m->validate['Path']['ffmpegProtocol']['rule']) ? $m->validate['Path']['ffmpegProtocol']['rule'][0] : null;
check($rule !== null, 'Monitor validates Path');

function valid($rule, $id, $data, $stored, $path) {
  global $m;
  if ($rule === null) return true;
  $m->id = $id;
  $m->data = array('Monitor' => $data);
  AppModel::$stored = $stored;
  return $m->$rule(array('Path' => $path));
}

check(valid($rule, false, array('Type' => 'Ffmpeg'), array(), 'camera.local/stream') === false, 'create of an Ffmpeg monitor refuses a Path with no protocol');
check(valid($rule, false, array('Type' => 'Ffmpeg'), array(), 'rtsp://camera.local/stream') === true, 'create of an Ffmpeg monitor accepts rtsp://');
check(valid($rule, false, array('Type' => 'Ffmpeg'), array(), '/dev/video0') === true, 'an absolute path is accepted');
check(valid($rule, false, array('Type' => 'Ffmpeg'), array(), 'v4l2:/dev/video0') === true, 'the v4l2: form is accepted');
check(valid($rule, false, array('Type' => 'Ffmpeg'), array(), '') === true, 'an empty Path is accepted');
check(valid($rule, false, array('Type' => 'Ffmpeg'), array(), array('a' => 'b')) === true, 'an array Path is left to AppModel, which refuses it, and does not raise a TypeError');
check(valid($rule, false, array('Type' => 'Remote'), array(), 'camera.local/stream') === true, 'a Remote monitor keeps a bare path');
check(valid($rule, 3, array(), array('Type' => 'Ffmpeg', 'Path' => 'old/stream'), 'new/stream') === false, 'edit of a stored Ffmpeg monitor refuses a new Path with no protocol');
check(valid($rule, 3, array(), array('Type' => 'Ffmpeg', 'Path' => 'rtsp://a/b'), 'rtsp://c/d') === true, 'edit of a stored Ffmpeg monitor accepts a new rtsp:// Path');
check(valid($rule, 3, array(), array('Type' => 'Ffmpeg', 'Path' => 'old/stream'), 'old/stream') === true, 'edit that sends the stored Path back is not refused');
check(valid($rule, 3, array(), array('Type' => 'Remote', 'Path' => 'x'), 'bare') === true, 'edit of a stored Remote monitor keeps a bare path');
check(valid($rule, 3, array('Type' => 'Remote'), array('Type' => 'Ffmpeg', 'Path' => 'x'), 'bare') === true, 'edit that sets Type Remote accepts a bare path');

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
