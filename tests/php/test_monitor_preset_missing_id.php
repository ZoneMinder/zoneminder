<?php
/*
 * A preset id that no longer exists in MonitorPresets makes dbFetchOne() return
 * false, and the preset block in web/skins/classic/views/monitor.php used to
 * foreach over that false value. On PHP 8 that raised
 * "foreach() argument must be of type array|object, bool given". The block now
 * iterates over `$preset ?: array()`.
 *
 * Run: php tests/php/test_monitor_preset_missing_id.php [root]
 * Pass a worktree root as the first argument to test another copy of the file
 * (used to show the failure against the pre-fix version).
 */
namespace {
  $root = isset($argv[1]) ? rtrim($argv[1], '/') : dirname(__DIR__, 2);
  $viewFile = $root.'/web/skins/classic/views/monitor.php';

  class MonitorStub {
    public $calls = array();
    public function __call($name, $args) {
      $this->calls[$name] = $args[0];
      return null;
    }
  }

  function dbFetchOne($sql, $col = null, $params = array()) {
    return $GLOBALS['fetchResult'];
  }

  $tests = 0;
  $failures = 0;
  function check($label, $cond) {
    global $tests, $failures;
    $tests++;
    if ($cond) {
      echo "  ok  - $label\n";
    } else {
      $failures++;
      echo "  FAIL - $label\n";
    }
  }

  $phpErrors = array();
  set_error_handler(function($no, $str) use (&$phpErrors) {
    $phpErrors[] = $str;
    return true;
  });
  error_reporting(E_ALL);

  $source = @file_get_contents($viewFile);
  if ($source === false) {
    echo "  FAIL - could not read $viewFile\n";
    exit(1);
  }

  $startMarker = "if (!empty(\$_REQUEST['preset'])) {";
  $endMarker = '} # end if preset';
  $start = strpos($source, $startMarker);
  $end = $start === false ? false : strpos($source, $endMarker, $start);
  if ($start === false || $end === false) {
    echo "  FAIL - could not locate preset block in $viewFile\n";
    exit(1);
  }
  $block = substr($source, $start, $end + strlen($endMarker) - $start);
  check('preset block found in view', $block !== '');

  function runBlock($block, $monitor) {
    $_REQUEST['preset'] = 999999;
    eval($block);
  }

  // (a) The preset id does not exist, dbFetchOne() returns false.
  $phpErrors = array();
  $GLOBALS['fetchResult'] = false;
  $monitor = new MonitorStub();
  $_REQUEST = array('preset' => 999999);
  runBlock($block, $monitor);
  check('missing preset id raises no PHP warning', count($phpErrors) === 0);
  if (count($phpErrors)) {
    foreach ($phpErrors as $err) echo "         php: $err\n";
  }
  check('missing preset id calls no monitor setter', count($monitor->calls) === 0);

  // (b) A preset row with a NULL column: NULLs must be skipped.
  $phpErrors = array();
  $GLOBALS['fetchResult'] = array('Host' => 'h', 'Width' => null);
  $monitor = new MonitorStub();
  $_REQUEST = array('preset' => 999999);
  runBlock($block, $monitor);
  check('present preset raises no PHP warning', count($phpErrors) === 0);
  if (count($phpErrors)) {
    foreach ($phpErrors as $err) echo "         php: $err\n";
  }
  check('present preset sets Host', array_key_exists('Host', $monitor->calls) && $monitor->calls['Host'] === 'h');
  check('present preset skips NULL Width', !array_key_exists('Width', $monitor->calls));
  check('present preset calls exactly one setter', count($monitor->calls) === 1);

  echo "\n$tests tests, $failures failures\n";
  exit($failures ? 1 : 0);
}
