<?php
// views/video.php: deleting a video file must only happen on a POST, and the page must
// offer the delete as a POST form rather than a link. A GET carrying deleteIndex (an
// image tag, a prefetch, a copied URL) must leave the event's files alone.
//
// Run as: php tests/php/test_video_delete_post_only.php .

namespace ZM {
  function Warning($s) {}
  function Debug($s) {}
  class Event {
    function __construct($id) {}
    function MonitorId() { return 1; }
    function Monitor() { return new Monitor(); }
    function Path() { return $GLOBALS['tmpdir']; }
    function Id() { return 7; }
    function canView($u=null) { return true; }
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
  $root = realpath($argv[1] ?? __DIR__.'/../..');
  $tmpdir = sys_get_temp_dir().'/zm_video_delete_'.getmypid();
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
  $fn = file_get_contents($root.'/web/includes/functions.php');
  preg_match('/^function reScale\(.*?^}/ms', $fn, $m);
  eval($m[0]);
  $cfg = file_get_contents($root.'/web/skins/classic/includes/config.php');
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
  function view($req, $post = array()) {
    global $root, $scale, $rate, $rates, $scales, $selects, $size, $tmpdir, $skin, $lastOutput;
    $selects = array(); $size = null; $lastOutput = '';
    $_POST = $post;
    $_SERVER['REQUEST_METHOD'] = $post ? 'POST' : 'GET';
    $_REQUEST = array_merge(array('eid' => '7'), $req, $post);
    $view = 'video';
    ob_start();
    try {
      include $root.'/web/skins/classic/views/video.php';
    } catch (Throwable $e) {
      ob_end_clean();
      return 'threw: '.$e->getMessage();
    }
    $out = ob_get_clean();
    if ($out === false) return 'fail';
    $lastOutput = $out;
    return 'ok';
  }

  $defRate = 100; $defScale = 100;
  file_put_contents($tmpdir.'/7-a.mp4', 'x');
  file_put_contents($tmpdir.'/7-b.mp4', 'x');

  check('a GET with deleteIndex does not throw', view(array('deleteIndex' => '0')), 'ok');
  check('a GET with deleteIndex deletes nothing', count(glob($tmpdir.'/*.mp4')), 2);

  check('a POST with deleteIndex does not throw',
    view(array('eid' => '7'), array('deleteIndex' => '0')), 'ok');
  check('a POST with deleteIndex deletes exactly one file', count(glob($tmpdir.'/*.mp4')), 1);

  check('the page renders', view(array()), 'ok');
  check('the delete is a POST form', strpos($lastOutput, 'method="post"') !== false, true);
  check('the delete is a hidden deleteIndex input', strpos($lastOutput, 'name="deleteIndex"') !== false, true);
  check('the delete is not a deleteIndex href',
    strpos($lastOutput, 'href="?view=video&eid=7&deleteIndex=') === false, true);

  exit($failures ? 1 : 0);
}
