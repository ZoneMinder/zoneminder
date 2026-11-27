<?php
// ajax/training.php save clamped the YOLO centre and size separately, so a box that overhangs the
// image edge (detectors return these) was saved shifted, and a box with x1 > x2 was saved with
// zero width. The corners are now ordered and clipped to the image first.
//
// Run as: php tests/php/test_training_save_clip_box.php [root]   (root defaults to the repo)

namespace ZM {
  class Event {
    static function find_one($f) { return new Event(); }
    function Path() { return $GLOBALS['evdir']; }
    function Width() { return 100; }
    function Height() { return 100; }
    function MonitorId() { return 1; }
  }
  function Debug($m) {}
  function Warning($m) {}
  function Error($m) {}
}

namespace {
class Done extends \Exception {}
function canView($a) { return true; }
function canEdit($a) { return true; }
function validCardinal($v) { return preg_replace('/\D/', '', $v); }
function validHtmlStr($v) { return $v; }
function translate($k) { return $k; }
function ajaxError($m) { $GLOBALS['out'] = 'error: '.$m; throw new Done(); }
function ajaxResponse($r = array()) { $GLOBALS['out'] = 'ok'; throw new Done(); }

$tmp = getenv('ZM_TRAIN_TMP') ?: sys_get_temp_dir().'/zm_train_clip_'.getmypid();
putenv('ZM_TRAIN_TMP='.$tmp);
$evdir = $tmp.'/ev';
@mkdir($evdir, 0700, true);
file_put_contents($evdir.'/alarm.jpg', 'x');
define('ZM_OPT_TRAINING', true);
define('ZM_TRAINING_DATA_DIR', $tmp.'/train');
define('ZM_EVENT_IMAGE_DIGITS', 5);
$stubdir = $tmp.'/stub';
@mkdir($stubdir.'/includes', 0700, true);
file_put_contents($stubdir.'/includes/Event.php', "<?php\n");
set_include_path($stubdir.PATH_SEPARATOR.get_include_path());

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$file = $root.'/web/ajax/training.php';
$user = null;
$_SERVER['REQUEST_METHOD'] = 'POST';

// training.php declares functions, so each save runs in a child process (argv[2] = box JSON).
if (isset($argv[2])) {
  $_REQUEST = array('action' => 'save', 'eid' => '7', 'fid' => 'alarm', 'width' => '100', 'height' => '100',
    'annotations' => json_encode(array(json_decode($argv[2], true) + array('label' => 'car'))));
  try { include $file; } catch (Done $e) {}
  echo trim(@file_get_contents(ZM_TRAINING_DATA_DIR.'/labels/all/event_7_frame_alarm.txt'));
  exit(0);
}

function save($box) {
  global $root, $argv;
  return trim(shell_exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($argv[0]).' '.escapeshellarg($root).' '.
    escapeshellarg(json_encode($box)).' 2>/dev/null'));
}

$failures = 0;
function check($n, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $n\n";
  else { $failures++; echo "FAIL - $n (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

check('box inside the image', save(array('x1' => 10, 'y1' => 20, 'x2' => 30, 'y2' => 60)),
  '0 0.200000 0.400000 0.200000 0.400000');
check('box overhanging the right and bottom edge is clipped',
  save(array('x1' => 80, 'y1' => 90, 'x2' => 130, 'y2' => 120)), '0 0.900000 0.950000 0.200000 0.100000');
check('box overhanging the left edge is clipped',
  save(array('x1' => -20, 'y1' => 0, 'x2' => 40, 'y2' => 50)), '0 0.200000 0.250000 0.400000 0.500000');
check('box with swapped corners keeps its size',
  save(array('x1' => 30, 'y1' => 60, 'x2' => 10, 'y2' => 20)), '0 0.200000 0.400000 0.200000 0.400000');

exec('rm -rf '.escapeshellarg($tmp));
exit($failures ? 1 : 0);
}
