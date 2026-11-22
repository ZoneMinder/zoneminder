<?php
// views/cycle.php ran every monitor's DefaultScale through reScale() into ScaledWidth,
// ScaledHeight and PopupScale, which nothing reads. DefaultScale can be fit_to_width, which
// reScale() multiplies, so on PHP 8 the cycle view died with a TypeError when any monitor
// in it had that default, and a 640px style value raised a non-numeric warning.
//
// Run as: php tests/php/test_cycle_view_default_scale.php

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
  $fn = file_get_contents(__DIR__.'/../../web/includes/functions.php');
  preg_match('/^function reScale\(.*?^}/ms', $fn, $m);
  eval($m[0]);

  set_error_handler(function ($no, $str) { throw new ErrorException($str); });

  $failures = 0;
  function check($n, $got, $want) {
    global $failures;
    if ($got === $want) { echo "ok - $n\n"; } else { $failures++; echo "FAIL - $n (got ".var_export($got, true).")\n"; }
  }
  function view() {
    global $scales;
    $_REQUEST = array('mid' => '1');
    $view = 'cycle';
    ob_start();
    try {
      include __DIR__.'/../../web/skins/classic/views/cycle.php';
    } catch (Throwable $e) {
      ob_end_clean();
      return 'threw: '.$e->getMessage();
    }
    return strpos(ob_get_clean(), 'stream1') !== false ? 'ok' : 'monitor not shown';
  }

  // The view declares a function, so it can only be included once per process.
  if (isset($argv[1])) {
    $monitorRows = array(
      array('Id'=>1, 'Name'=>'Front', 'Capturing'=>'Always', 'Width'=>640, 'Height'=>480, 'DefaultScale'=>$argv[1]),
    );
    echo view();
    exit(0);
  }
  foreach (array('0', '100', 'fit_to_width', '640px') as $defScale) {
    $got = shell_exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' '.escapeshellarg($defScale));
    check("cycle view shows a monitor with DefaultScale $defScale", $got, 'ok');
  }
  exit($failures ? 1 : 0);
}
