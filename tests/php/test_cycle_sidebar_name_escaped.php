<?php
// The Cycle view sidebar printed each monitor name unescaped (stored XSS through a monitor
// name); it now escapes it.
//
// Run as: php tests/php/test_cycle_sidebar_name_escaped.php

namespace ZM {
  class Monitor {
    private $row;
    function __construct($row) { $this->row = $row; }
    function Id() { return $this->row['Id']; }
    function Name() { return $this->row['Name']; }
    function DefaultScale() { return $this->row['DefaultScale']; }
    function getStreamHTML($options) { return 'stream'.$this->row['Id']; }
    function JanusEnabled() { return false; }
    function RTSP2WebEnabled() { return false; }
  }
  function Error($s) {}
}

namespace {
  $root = isset($argv[1]) ? rtrim($argv[1], '/') : __DIR__.'/../..';
  $stubdir = sys_get_temp_dir().'/zm_cycle_scale_'.getmypid();
  @mkdir($stubdir, 0700, true);
  file_put_contents($stubdir.'/_monitor_filters.php', "<?php\nfunction buildMonitorsFilters() { return array('filterBar'=>'', 'displayMonitors'=>\$GLOBALS['monitorRows']); }\n");
  set_include_path($stubdir.PATH_SEPARATOR.get_include_path());
  register_shutdown_function(function () use ($stubdir) {
    @unlink($stubdir.'/_monitor_filters.php');
    @rmdir($stubdir);
  });

  define('ZM_WEB_DEFAULT_SCALE', 100);
  define('SCALE_BASE', 100);
  $scales = array('0'=>'Auto', '100'=>'Actual', 'fit_to_width'=>'Fit to width', '640px'=>'Max 640px');
  function translate($s) { return $s; }
  function canView($a) { return true; }
  function canStream() { return true; }
  function validInt($v) { return preg_replace('/\D/', '', (string)$v); }
  function validHtmlStr($s) { return htmlspecialchars((string)$s); }
  function zm_session_start() {}
  function noCacheHeaders() {}
  function xhtmlHeaders() {}
  function xhtmlFooter() {}
  function getBodyTopHTML() {}
  function getNavBarHTML() { return ''; }
  function htmlSelect() { return ''; }
  function cache_bust($s) { return $s; }

  // The real helper
  $fn = file_get_contents($root.'/web/includes/functions.php');
  preg_match('/^function reScale\(.*?^}/ms', $fn, $m);
  eval($m[0]);

  set_error_handler(function ($no, $str) { throw new ErrorException($str); });

  $failures = 0;
  function check($n, $got, $want) {
    global $failures;
    if ($got === $want) { echo "ok - $n\n"; } else { $failures++; echo "FAIL - $n (got ".var_export($got, true).")\n"; }
  }
  function view() {
    global $root, $scales;
    $_REQUEST = array('mid' => '1');
    $view = 'cycle';
    ob_start();
    try {
      include $root.'/web/skins/classic/views/cycle.php';
    } catch (Throwable $e) {
      ob_end_clean();
      return 'threw: '.$e->getMessage();
    }
    return ob_get_clean();
  }

  // The view declares a function, so it can only be included once per process.
  if (isset($argv[2])) {
    $monitorRows = array(
      array('Id'=>1, 'Name'=>'<img src=x onerror=alert(1)>', 'Capturing'=>'Always', 'Width'=>640, 'Height'=>480, 'DefaultScale'=>'0'),
    );
    echo view();
    exit(0);
  }
  $name = '<img src=x onerror=alert(1)>';
  $escaped = '&lt;img src=x onerror=alert(1)&gt;';
  $out = shell_exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' '.escapeshellarg($root).' '.escapeshellarg('xss'));
  check('cycle sidebar escapes the monitor name', strpos($out, $escaped) !== false, true);
  check('cycle sidebar does not print the monitor name raw', strpos($out, $name) === false, true);
  exit($failures ? 1 : 0);
}
