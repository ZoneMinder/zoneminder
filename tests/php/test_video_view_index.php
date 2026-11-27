<?php
// views/video.php: downloadIndex and showIndex past the end of the video list read an
// undefined array key (warning, then filesize()/pathinfo() on null), and a fractional scale
// in a generated file name (s0_5) was cast to int, so the player was sized 0x0.
//
// Run as: php tests/php/test_video_view_index.php

namespace ZM {
  function Warning($s) { $GLOBALS['warnings'][] = $s; }
  function Debug($s) {}
  class Event {
    function __construct($id) {}
    function MonitorId() { return 1; }
    function Monitor() { return new Monitor(); }
    function canView($u = null) { return true; }
    function Path() { return $GLOBALS['dir']; }
    function Id() { return 7; }
    function Width() { return 640; }
    function Height() { return 480; }
    function DefaultVideo() { return ''; }
  }
  class Monitor {
    function DefaultRate() { return 100; }
    function DefaultScale() { return 100; }
  }
}

namespace {
  $dir = sys_get_temp_dir().'/zm_video_view_'.getmypid();
  mkdir($dir, 0700, true);
  file_put_contents($dir.'/7-video-s0_5.mp4', 'x');
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
    global $dir, $stubdir, $failures;
    @unlink($dir.'/7-video-s0_5.mp4'); @rmdir($dir);
    @unlink($stubdir.'/includes/Event.php'); @rmdir($stubdir.'/includes'); @rmdir($stubdir);
  });

  $failures = 0;
  function check($n, $got, $want) {
    global $failures;
    if ($got === $want) { echo "ok - $n\n"; } else { $failures++; echo "FAIL - $n (got ".var_export($got, true).")\n"; }
  }
  function view($req) {
    $GLOBALS['warnings'] = array(); $GLOBALS['size'] = null;
    $_REQUEST = array_merge(array('eid' => '7'), $req);
    $view = 'video'; $rates = array(); $scales = array(); $skin = 'classic';
    ob_start();
    try {
      include __DIR__.'/../../web/skins/classic/views/video.php';
    } catch (Throwable $e) {
      ob_end_clean();
      $GLOBALS['dlResult'] = 'threw: '.$e->getMessage();
      return 'threw: '.$e->getMessage();
    }
    ob_end_clean();
    return 'ok';
  }

  check('showIndex past the end does not throw', view(array('showIndex' => '5')), 'ok');
  check('fractional scale sizes the player', view(array('showIndex' => '0')).' '.$size, 'ok 320x240');
  // downloadIndex ends in exit, so its result is checked at shutdown
  register_shutdown_function(function () {
    global $failures, $dlResult;
    check('downloadIndex past the end warns and does not throw', $dlResult.' '.count($GLOBALS['warnings']), 'pending 1');
    exit($failures ? 1 : 0);
  });
  $dlResult = 'pending';
  view(array('downloadIndex' => '5'));
}
