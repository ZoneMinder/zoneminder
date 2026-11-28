<?php
// ajax/training.php save passed $_REQUEST['annotations'] to json_decode() and each label to preg_match().
// annotations[]=x, or a label that is itself a list, is a TypeError on PHP 8, so the request died with
// a 500 instead of the 'Invalid annotations data' and 'Invalid label' errors. A normal save, including
// a label sent as a number, must still be written.
//
// Run as: php tests/php/test_ajax_training_save_nonscalar.php [web root]
// Each request runs in a child process because the file declares functions.

namespace ZM {
  class Event {
    public $id;
    public function __construct($id) { $this->id = $id; }
    public static function find_one($f) { return new Event($f['Id']); }
    public function canView() { return true; }
    public function Path() { return $GLOBALS['evdir']; }
    public function Width() { return 100; }
    public function Height() { return 100; }
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
  function validCardinal($v) { return is_scalar($v) ? preg_replace('/\D/', '', $v) : ''; }
  function validHtmlStr($v) { return is_array($v) ? '' : htmlspecialchars($v); }
  function detaintPath($p) { return $p; }
  function translate($s) { return $s; }

  $root = isset($argv[1]) ? $argv[1] : __DIR__.'/../../web';

  if (isset($argv[2])) {
    $work = $argv[3];
    $GLOBALS['evdir'] = $work.'/ev';
    define('ZM_OPT_TRAINING', 1);
    define('ZM_TRAINING_DATA_DIR', $work.'/training');
    define('ZM_EVENT_IMAGE_DIGITS', 5);
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_REQUEST = json_decode($argv[2], true);
    set_include_path($work.'/inc');
    try { include $work.'/inc/ajax/training.php'; echo "RESULT fell-through\n"; }
    catch (AjaxError $e) { echo 'RESULT error:'.strtok($e->getMessage(), ':')."\n"; }
    catch (AjaxDone $e) { echo "RESULT ok\n"; }
    catch (Throwable $e) { echo 'RESULT '.get_class($e)."\n"; }
    exit(0);
  }

  $failures = 0;
  function check($name, $got, $want) {
    global $failures;
    if ($got === $want) echo "ok - $name\n";
    else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
  }

  $work = sys_get_temp_dir().'/zm_training_nonscalar_'.getmypid();
  mkdir($work.'/inc/ajax', 0777, true);
  mkdir($work.'/inc/includes', 0777, true);
  mkdir($work.'/ev', 0777, true);
  copy($root.'/ajax/training.php', $work.'/inc/ajax/training.php');
  file_put_contents($work.'/inc/includes/Event.php', "<?php\n");
  file_put_contents($work.'/ev/alarm.jpg', 'x');

  function save($root, $work, $annotations) {
    $r = array('action' => 'save', 'eid' => 1, 'fid' => 'alarm', 'width' => 100, 'height' => 100);
    if ($annotations !== null) $r['annotations'] = $annotations;
    $out = shell_exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' '.escapeshellarg($root).' '
      .escapeshellarg(json_encode($r)).' '.escapeshellarg($work).' 2>/dev/null');
    return preg_match('/^RESULT (.*)$/m', (string)$out, $m) ? $m[1] : 'no result';
  }

  $box = array('x1' => 10, 'y1' => 10, 'x2' => 50, 'y2' => 50);
  check('annotations sent as a list is refused', save($root, $work, array('x')), 'error:Invalid annotations data');
  check('annotations left out is refused', save($root, $work, null), 'error:Invalid annotations data');
  check('a label that is a list is refused', save($root, $work, json_encode(array($box + array('label' => array('car'))))), 'error:Invalid label');
  check('a label that is a nested list is refused', save($root, $work, json_encode(array($box + array('label' => array(array('car')))))), 'error:Invalid label');

  check('a normal annotation is saved', save($root, $work, json_encode(array($box + array('label' => 'car')))), 'ok');
  check('the label file has the box', trim((string)@file_get_contents($work.'/training/labels/all/event_1_frame_alarm.txt')), '0 0.300000 0.300000 0.400000 0.400000');
  check('a numeric label is still saved', save($root, $work, json_encode(array($box + array('label' => 5)))), 'ok');
  check('no annotations is still saved as a background frame', save($root, $work, '[]'), 'ok');

  shell_exec('rm -rf '.escapeshellarg($work));
  echo $failures ? "\n$failures failed\n" : "\nall passed\n";
  exit($failures ? 1 : 0);
}
