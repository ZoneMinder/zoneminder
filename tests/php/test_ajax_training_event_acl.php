<?php
// web/ajax/training.php checked only the global Events permission, so a user denied
// the monitor of an event could still load its detection data, read or write its
// annotations, copy its frames into the training set and run detection on them.
//
// Run as: php tests/php/test_ajax_training_event_acl.php [web root]
// Each request runs in a child process because the file declares functions.

namespace ZM {
  class Event {
    public static $viewable = array(1 => true, 2 => false);
    public $id;
    public $path;
    public function __construct($id, $path) { $this->id = $id; $this->path = $path; }
    public static function find_one($f) {
      return isset(self::$viewable[$f['Id']]) ? new Event($f['Id'], $GLOBALS['evdir']) : null;
    }
    public function canView() { return self::$viewable[$this->id]; }
    public function Path() { return $this->path; }
    public function Relative_Path() { return 'ev'; }
    public function Frames() { return 1; }
    public function Width() { return 100; }
    public function Height() { return 100; }
    public function MonitorId() { return $this->id; }
  }
  function Debug($s) {}
  function Warning($s) {}
  function Error($s) {}
}

namespace {
  class AjaxError extends Exception {}
  class AjaxDone extends Exception {}
  function ajaxError($m, $c = 0, $r = '') { throw new AjaxError($m); }
  function ajaxResponse($d = array()) { throw new AjaxDone(); }
  function canView($r) { return true; }
  function canEdit($r) { return true; }
  function validCardinal($v) { return (int)$v; }
  function validHtmlStr($v) { return htmlspecialchars($v); }
  function detaintPath($p) { return $p; }
  function translate($s) { return $s; }
  class StubUser { function Username() { return 'u'; } }

  $root = isset($argv[1]) ? $argv[1] : __DIR__.'/../../web';

  if (isset($argv[2])) {
    // Child: run one request and print the outcome.
    $work = $argv[3];
    $evdir = $work.'/ev';
    $GLOBALS['evdir'] = $evdir;
    define('ZM_OPT_TRAINING', 1);
    define('ZM_TRAINING_DATA_DIR', $work.'/training');
    define('ZM_TRAINING_DETECT_SCRIPT', '/nonexistent/detect');
    define('ZM_EVENT_IMAGE_DIGITS', 5);
    $user = new StubUser();
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_REQUEST = json_decode($argv[2], true);
    set_include_path($work.'/inc');
    try { include $work.'/inc/ajax/training.php'; echo "RESULT fell-through\n"; }
    catch (AjaxError $e) { echo 'RESULT error:'.$e->getMessage()."\n"; }
    catch (AjaxDone $e) { echo "RESULT ok\n"; }
    exit(0);
  }

  $failures = 0;
  function check($name, $got, $want) {
    global $failures;
    if ($got === $want) echo "ok - $name\n";
    else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
  }

  $work = sys_get_temp_dir().'/zm_training_acl_'.getmypid();
  mkdir($work.'/inc/ajax', 0777, true);
  mkdir($work.'/inc/includes', 0777, true);
  mkdir($work.'/ev', 0777, true);
  mkdir($work.'/training/labels/all', 0777, true);
  copy($root.'/ajax/training.php', $work.'/inc/ajax/training.php');
  file_put_contents($work.'/inc/includes/Event.php', "<?php\n");
  file_put_contents($work.'/ev/alarm.jpg', 'x');
  file_put_contents($work.'/training/labels/all/event_2_frame_alarm.txt', "0 0.5 0.5 0.2 0.2\n");
  file_put_contents($work.'/training/labels/all/event_1_frame_alarm.txt', "0 0.5 0.5 0.2 0.2\n");
  file_put_contents($work.'/training/data.yaml', "names:\n  0: car\n");

  function req($root, $work, $r) {
    $out = shell_exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' '.escapeshellarg($root).' '
      .escapeshellarg(json_encode($r)).' '.escapeshellarg($work).' 2>/dev/null');
    return preg_match('/^RESULT (.*)$/m', (string)$out, $m) ? $m[1] : 'no result';
  }

  $save = array('action' => 'save', 'fid' => 'alarm', 'annotations' => '[]', 'width' => 100, 'height' => 100);
  foreach (array(
    'load' => array('action' => 'load'),
    'load_saved' => array('action' => 'load_saved', 'fid' => 'alarm'),
    'save' => $save,
    'detect' => array('action' => 'detect', 'fid' => 'alarm'),
  ) as $name => $r) {
    $got = req($root, $work, $r + array('eid' => 2));
    check("$name on an event of a denied monitor is refused", strpos($got, 'error:Insufficient permissions') === 0, true);
  }
  check('save on a denied event copies no image', file_exists($work.'/training/images/all/event_2_frame_alarm.jpg'), false);
  check('load on a viewable event works', req($root, $work, array('action' => 'load', 'eid' => 1)), 'ok');
  check('load_saved on a viewable event works', req($root, $work, array('action' => 'load_saved', 'eid' => 1, 'fid' => 'alarm')), 'ok');
  check('save on a viewable event works', req($root, $work, $save + array('eid' => 1)), 'ok');

  shell_exec('rm -rf '.escapeshellarg($work));
  echo $failures ? "\n$failures failed\n" : "\nall passed\n";
  exit($failures ? 1 : 0);
}
