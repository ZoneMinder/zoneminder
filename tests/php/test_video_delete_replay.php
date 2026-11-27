<?php
// views/video.php: a delete request names a file only by its position in the video list
// (deleteIndex), so replaying the same request after the list has changed deletes
// whatever now sits at that position. The request also carries deleteName; a replay
// must not remove a different file and must warn instead.
//
// Run as: php tests/php/test_video_delete_replay.php [repo-root]

namespace ZM {
  function Warning($s) { $GLOBALS['warnings'][] = $s; }
  function Debug($s) {}
  class Event {
    function __construct($id) {}
    function MonitorId() { return 1; }
    function Monitor() { return new Monitor(); }
    function Path() { return $GLOBALS['dir']; }
    function Id() { return 7; }
    function Width() { return 640; }
    function Height() { return 480; }
    function DefaultVideo() { return ''; }
    function canView($u=null) { return true; }
  }
  class Monitor {
    function DefaultRate() { return 100; }
    function DefaultScale() { return 100; }
  }
}

namespace {
  $root = isset($argv[1]) ? $argv[1] : dirname(__DIR__, 2);
  $dir = sys_get_temp_dir().'/zm_video_delete_'.getmypid();
  mkdir($dir, 0700, true);
  foreach (array('a-video.mp4', 'b-video.mp4', 'c-video.mp4') as $f) {
    file_put_contents($dir.'/'.$f, 'x');
  }
  $stubdir = $dir.'_stubs';
  mkdir($stubdir.'/includes', 0700, true);
  file_put_contents($stubdir.'/includes/Event.php', "<?php\n");
  set_include_path($stubdir.PATH_SEPARATOR.get_include_path());

  define('ZM_FFMPEG_FORMATS', 'mp4*');
  define('ZM_WEB_DEFAULT_RATE', 100);
  define('ZM_WEB_DEFAULT_SCALE', 100);
  define('RATE_BASE', 100);
  define('SCALE_BASE', 100);
  $rates = array(); $scales = array(); $skin = 'classic';
  function canView($a, $m = false) { return true; }
  function canEdit($a) { return true; }
  function validInt($v) { return is_scalar($v) ? (int)$v : ''; }
  function validCardinal($v) { return is_scalar($v) ? (int)$v : ''; }
  function reScale($a, $b, $c) { return $a; }
  function translate($s) { return $s; }
  function xhtmlHeaders() {}
  function xhtmlFooter() {}
  function getBodyTopHTML() {}
  function getNavBarHTML() {}
  function buildSelect() {}
  function human_filesize($n) { return $n; }
  function outputVideoStream($id, $src, $w, $h) { $GLOBALS['size'] = "{$w}x{$h}"; }

  set_error_handler(function ($no, $str) { throw new ErrorException($str); });
  register_shutdown_function(function () {
    global $dir, $stubdir;
    foreach (array('a-video.mp4', 'b-video.mp4', 'c-video.mp4') as $f) {
      if (file_exists($dir.'/'.$f)) unlink($dir.'/'.$f);
    }
    rmdir($dir);
    unlink($stubdir.'/includes/Event.php'); rmdir($stubdir.'/includes'); rmdir($stubdir);
  });

  $failures = 0;
  function check($n, $got, $want) {
    global $failures;
    if ($got === $want) { echo "ok - $n\n"; } else { $failures++; echo "FAIL - $n (got ".var_export($got, true).")\n"; }
  }

  // The view lists the event dir with opendir/readdir, keeps regular files matching the
  // mp4 format, and numbers them in readdir order. Recompute a file's key the same way,
  // at the same directory state the view will see.
  function videoIndex($dir, $name) {
    $index = 0;
    if (!($h = opendir($dir))) return -1;
    while (($file = readdir($h)) !== false) {
      $path = $dir.'/'.$file;
      if (is_file($path) && preg_match('/\.(?:mp4)$/', $path)) {
        if ($file === $name) { closedir($h); return $index; }
        $index++;
      }
    }
    closedir($h);
    return -1;
  }

  function view($req) {
    global $root;
    $GLOBALS['warnings'] = array(); $GLOBALS['size'] = null;
    $_REQUEST = array_merge(array('eid' => '7'), $req);
    $view = 'video'; $rates = array(); $scales = array(); $skin = 'classic';
    ob_start();
    try {
      include $root.'/web/skins/classic/views/video.php';
    } catch (Throwable $e) {
      ob_end_clean();
      return 'threw: '.$e->getMessage();
    }
    ob_end_clean();
    return 'ok';
  }

  // Request 1: delete b by index and name.
  $bIndex = videoIndex($dir, 'b-video.mp4');
  check('b-video.mp4 is in the list', $bIndex >= 0, true);
  check('request 1 does not throw',
    view(array('deleteIndex' => (string)$bIndex, 'deleteName' => 'b-video.mp4')), 'ok');
  check('request 1 deletes b-video.mp4', file_exists($dir.'/b-video.mp4'), false);

  // Request 2: replay the exact same request. Its index now points at a different file.
  $replay = view(array('deleteIndex' => (string)$bIndex, 'deleteName' => 'b-video.mp4'));
  check('request 2 replay does not throw', $replay, 'ok');
  check('request 2 replay keeps a-video.mp4', file_exists($dir.'/a-video.mp4'), true);
  check('request 2 replay keeps c-video.mp4', file_exists($dir.'/c-video.mp4'), true);
  check('request 2 replay logs a warning', count($GLOBALS['warnings']) > 0, true);

  // Request 3: deleteIndex alone still deletes, for links without a name.
  $cIndex = videoIndex($dir, 'c-video.mp4');
  check('c-video.mp4 is in the list', $cIndex >= 0, true);
  check('request 3 without deleteName does not throw',
    view(array('deleteIndex' => (string)$cIndex)), 'ok');
  check('request 3 without deleteName deletes c-video.mp4', file_exists($dir.'/c-video.mp4'), false);

  exit($failures ? 1 : 0);
}
