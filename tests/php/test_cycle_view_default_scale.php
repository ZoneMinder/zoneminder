<?php
// views/cycle.php ran every monitor's DefaultScale through reScale() into ScaledWidth,
// ScaledHeight and PopupScale, which nothing reads. DefaultScale can be fit_to_width, which
// reScale() multiplies, so on PHP 8 the cycle view died with a TypeError when any monitor
// in it had that default, and a 640px style value raised a non-numeric warning.
//
// The view then ran the scale through validInt(), which strips non-digits: a DefaultScale
// of '640px' streamed the monitor at 640% and 'fit_to_width' became ''. The fix keeps a
// value only if it is purely digits, otherwise '0' (Auto).
//
// Run as: php tests/php/test_cycle_view_default_scale.php
// Set ZM_TEST_CYCLE_PHP to test a different copy of the view (e.g. the original).

namespace ZM {
  class Monitor {
    private $row;
    function __construct($row) { $this->row = $row; }
    function Id() { return $this->row['Id']; }
    function Name() { return $this->row['Name']; }
    function DefaultScale() { return $this->row['DefaultScale']; }
    function getStreamHTML($options) {
      $GLOBALS['capturedStreamScale'] = isset($options['scale']) ? $options['scale'] : null;
      return 'stream'.$this->row['Id'];
    }
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
  function validHtmlStr($s) { return htmlspecialchars((string)$s); }
  function zm_session_start() {}
  function noCacheHeaders() {}
  function xhtmlHeaders() {}
  function xhtmlFooter() {}
  function getBodyTopHTML() {}
  function getNavBarHTML() { return ''; }
  function htmlSelect() { return ''; }
  function cache_bust($s) { return $s; }

  // The real helpers
  $fn = file_get_contents(__DIR__.'/../../web/includes/functions.php');
  preg_match('/^function reScale\(.*?^}/ms', $fn, $m);
  eval($m[0]);
  preg_match('/^function validInt\(.*?^}/ms', $fn, $m);
  eval($m[0]);

  set_error_handler(function ($no, $str) { throw new ErrorException($str); });

  $failures = 0;
  function check($n, $got, $want) {
    global $failures;
    if ( $got === $want ) { echo "ok - $n\n"; } else { $failures++; echo "FAIL - $n (got ".var_export($got, true).")\n"; }
  }
  function cyclePhpPath() {
    return getenv('ZM_TEST_CYCLE_PHP') ?: __DIR__.'/../../web/skins/classic/views/cycle.php';
  }
  function view() {
    global $scales;
    $_REQUEST = array('mid' => '1');
    $view = 'cycle';
    ob_start();
    try {
      include cyclePhpPath();
    } catch (Throwable $e) {
      ob_end_clean();
      return 'threw: '.$e->getMessage();
    }
    return strpos(ob_get_clean(), 'stream1') !== false ? 'ok' : 'monitor not shown';
  }

  // The view declares a function, so it can only be included once per process.
  // argv[1] is the monitor's DefaultScale, optional argv[2] the zmCycleScale cookie.
  if ( isset($argv[1]) ) {
    $monitorRows = array(
      array('Id'=>1, 'Name'=>'Front', 'Capturing'=>'Always', 'Width'=>640, 'Height'=>480, 'DefaultScale'=>$argv[1]),
    );
    if ( isset($argv[2]) )
      $_COOKIE['zmCycleScale'] = $argv[2];
    $result = view();
    echo $result.' scale='.var_export($GLOBALS['capturedStreamScale'], true);
    exit(0);
  }
  function runChild($defScale, $cookie = null) {
    $cmd = escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' '.escapeshellarg($defScale);
    if ( $cookie !== null )
      $cmd .= ' '.escapeshellarg($cookie);
    return trim((string)shell_exec($cmd));
  }
  function checkCase($defScale, $wantScale) {
    $got = runChild($defScale);
    check("cycle view shows a monitor with DefaultScale $defScale", strpos($got, 'ok scale=') === 0, true);
    check("DefaultScale $defScale passes scale $wantScale to getStreamHTML", substr($got, strlen('ok scale=')), "'".$wantScale."'");
  }
  checkCase('640px', '0');
  checkCase('fit_to_width', '0');
  checkCase('100', '100');
  checkCase('0', '0');

  $got = runChild('100', '640px');
  check("cycle view shows a monitor with zmCycleScale 640px", strpos($got, 'ok scale=') === 0, true);
  check("zmCycleScale 640px passes scale 0 to getStreamHTML", substr($got, strlen('ok scale=')), "'0'");
  exit($failures ? 1 : 0);
}
