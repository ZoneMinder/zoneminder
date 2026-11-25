<?php
// views/video.php: the Generate Video form used the monitor view's $rates and $scales.
// $scales holds Auto (0), fit_to_width and 640px style entries and $rates holds negative
// rates and Stop (0). The ajax handler rejects an empty rate/scale and zmvideo.pl only
// takes 0.25-4 and 0.25-100, so the preselected Auto scale could never generate a video, and
// a monitor whose DefaultScale is fit_to_width made reScale() throw a TypeError, taking
// the page down. The file list also read any "-s..." / "-r..." part of the event name
// (an EventPrefix such as "side-") as a scale or rate.
//
// Run as: php tests/php/test_video_view_options.php

namespace ZM {
  function Warning($s) {}
  function Debug($s) {}
  class Event {
    function __construct($id) {}
    function MonitorId() { return 1; }
    function Monitor() { return new Monitor(); }
    function canView($u = null) { return true; }
    function Path() { return $GLOBALS['tmpdir']; }
    function Id() { return 7; }
    function Width() { return 640; }
    function Height() { return 480; }
    function DefaultVideo() { return ''; }
  }
  class Monitor {
    function DefaultRate() { return $GLOBALS['defRate']; }
    function DefaultScale() { return $GLOBALS['defScale']; }
  }
}

namespace {
  $tmpdir = sys_get_temp_dir().'/zm_video_opts_'.getmypid();
  mkdir($tmpdir, 0700, true);
  $stubdir = $tmpdir.'_stubs';
  mkdir($stubdir.'/includes', 0700, true);
  file_put_contents($stubdir.'/includes/Event.php', "<?php\n");
  set_include_path($stubdir.PATH_SEPARATOR.get_include_path());
  register_shutdown_function(function () {
    global $tmpdir, $stubdir;
    foreach (glob($tmpdir.'/*') as $f) @unlink($f);
    @rmdir($tmpdir);
    @unlink($stubdir.'/includes/Event.php'); @rmdir($stubdir.'/includes'); @rmdir($stubdir);
  });

  define('ZM_FFMPEG_FORMATS', 'mp4*');
  define('ZM_WEB_DEFAULT_RATE', 100);
  define('ZM_WEB_DEFAULT_SCALE', 100);
  define('RATE_BASE', 100);
  define('SCALE_BASE', 100);
  $skin = 'classic';
  function translate($s) { return $s; }
  function canView($a, $m = false) { return true; }
  function canEdit($a) { return true; }
  function validInt($v) { return is_scalar($v) ? (int)$v : ''; }
  function validCardinal($v) { return is_scalar($v) ? (int)$v : ''; }
  function xhtmlHeaders() {}
  function xhtmlFooter() {}
  function getBodyTopHTML() {}
  function getNavBarHTML() {}
  function human_filesize($n) { return $n; }
  function outputVideoStream($id, $src, $w, $h) { $GLOBALS['size'] = "{$w}x{$h}"; }
  function buildSelect($name, $contents) { $GLOBALS['selects'][$name] = $contents; return ''; }

  // The real helper and the real option lists
  $fn = file_get_contents(__DIR__.'/../../web/includes/functions.php');
  preg_match('/^function reScale\(.*?^}/ms', $fn, $m);
  eval($m[0]);
  $cfg = file_get_contents(__DIR__.'/../../web/skins/classic/includes/config.php');
  preg_match('/^\$rates = array\(.*?^\);/ms', $cfg, $m);
  eval($m[0]);
  preg_match('/^\$scales = array\(.*?^\);/ms', $cfg, $m);
  eval($m[0]);

  set_error_handler(function ($no, $str) { throw new ErrorException($str); });

  $failures = 0;
  function check($n, $got, $want) {
    global $failures;
    if ($got === $want) { echo "ok - $n\n"; } else { $failures++; echo "FAIL - $n (got ".var_export($got, true).")\n"; }
  }
  function view($req) {
    global $scale, $rate, $rates, $scales, $selects, $size, $tmpdir, $skin;
    $selects = array(); $size = null;
    $_REQUEST = array_merge(array('eid' => '7'), $req);
    $view = 'video';
    ob_start();
    try {
      include __DIR__.'/../../web/skins/classic/views/video.php';
    } catch (Throwable $e) {
      ob_end_clean();
      return 'threw: '.$e->getMessage();
    }
    return ob_get_clean() === false ? 'fail' : 'ok';
  }

  $defRate = 100; $defScale = 'fit_to_width';
  check('fit_to_width default scale does not throw', view(array()), 'ok');
  check('default scale is a usable one', $scale, 100);
  check('scale choices are all numeric and positive',
    count(array_filter(array_keys($selects['scale']), function ($k) { return !is_numeric($k) || $k <= 0; })), 0);
  check('rate choices are all positive',
    count(array_filter(array_keys($selects['rate']), function ($k) { return $k <= 0; })), 0);
  check('the default rate is one of the choices', isset($selects['rate'][$rate]), true);

  $defScale = '640px';
  check('px default scale does not throw', view(array()), 'ok');
  check('px default scale falls back to actual size', $scale, 100);

  $defScale = '50';
  view(array());
  check('a numeric monitor default scale is kept', $scale, 50);

  // An EventPrefix like "side-" or "room-" puts a word starting with s or r in the file name
  $defScale = 100;
  file_put_contents($tmpdir.'/side-7-r2-s0_5.mp4', 'x');
  check('file names with an s or r word in the event name do not throw', view(array()), 'ok');
  check('showIndex sizes by the scale option only', view(array('showIndex' => '0')).' '.$size, 'ok 320x240');
  check('showIndex does not change the form default scale', $scale, 100);
  file_put_contents($tmpdir.'/room-8.mp4', 'x');
  check('a file with no options lists fine', view(array()), 'ok');

  exit($failures ? 1 : 0);
}
