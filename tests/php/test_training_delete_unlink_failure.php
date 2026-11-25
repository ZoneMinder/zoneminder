<?php
// ajax/training.php delete, delete_all and browse_delete ignored unlink() failures and reported the
// files as deleted. A file that cannot be removed (read-only directory) must now produce an error.
//
// Run as: php tests/php/test_training_delete_unlink_failure.php [root]   (root defaults to the repo)

namespace ZM {
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
function detaintPath($p) { return $p; }
function translate($k) { return $k; }
function ajaxError($m) { echo 'error'; throw new Done(); }
function ajaxResponse($r = array()) { echo 'ok:'.json_encode($r['deleted']); throw new Done(); }

if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
  echo "skip - running as root, read-only directories are not enforced\n";
  exit(0);
}

$tmp = getenv('ZM_TRAIN_TMP') ?: sys_get_temp_dir().'/zm_train_del_'.getmypid();
putenv('ZM_TRAIN_TMP='.$tmp);
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

// training.php declares functions, so each request runs in a child process (argv[2] = action).
if (isset($argv[2])) {
  $_REQUEST = array('action' => $argv[2], 'eid' => '7', 'fid' => 'alarm',
    'path' => 'images/all/event_7_frame_alarm.jpg');
  try { include $file; } catch (Done $e) {}
  exit(0);
}

function setup($locked) {
  $d = ZM_TRAINING_DATA_DIR;
  exec('chmod -R u+w '.escapeshellarg($d).' 2>/dev/null; rm -rf '.escapeshellarg($d));
  mkdir($d.'/images/all', 0700, true);
  mkdir($d.'/labels/all', 0700, true);
  file_put_contents($d.'/images/all/event_7_frame_alarm.jpg', 'x');
  file_put_contents($d.'/labels/all/event_7_frame_alarm.txt', "0 0.5 0.5 0.2 0.2\n");
  if ($locked) { chmod($d.'/images/all', 0500); chmod($d.'/labels/all', 0500); }
}

function run($action) {
  global $root, $argv;
  return trim(shell_exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($argv[0]).' '.escapeshellarg($root).' '.
    escapeshellarg($action).' 2>/dev/null'));
}

$failures = 0;
function check($n, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $n\n";
  else { $failures++; echo "FAIL - $n (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

foreach (array('delete', 'delete_all', 'browse_delete') as $action) {
  setup(true);
  check("$action reports an error when files cannot be removed", run($action), 'error');
}
setup(false);
check('delete succeeds on removable files', substr(run('delete'), 0, 7), 'ok:true');
setup(false);
check('delete_all succeeds on removable files', run('delete_all'), 'ok:2');
setup(false);
check('browse_delete succeeds on removable files', run('browse_delete'),
  'ok:["images\/all\/event_7_frame_alarm.jpg","labels\/all\/event_7_frame_alarm.txt"]');

exec('chmod -R u+w '.escapeshellarg($tmp).'; rm -rf '.escapeshellarg($tmp));
exit($failures ? 1 : 0);
}
