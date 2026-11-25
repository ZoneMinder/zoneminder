<?php
// The Log view can filter by Monitor instead of making the user know that a
// camera's daemons log under zmc_mN, zma_mN, zms_mN and zmtrack_mN.
// web/ajax/log.php turns a MonitorId into a bound Component IN (...) clause; a
// monitor the user may not view must match no rows, and no MonitorId must leave
// the query exactly as it was.
//
// Run as: php tests/php/test_ajax_log_monitor_filter.php   (from the repo root)

// Each case includes web/ajax/log.php, which defines functions at file scope, so
// the driver re-runs itself per case.
if ($argc < 2) {
  $failures = 0;
  foreach (array('viewable', 'not-viewable', 'no-monitor', 'empty-id', 'with-component', 'array-id') as $case) {
    $out = array();
    $rc = 0;
    exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' '.escapeshellarg($case).' 2>&1', $out, $rc);
    echo implode("\n", $out)."\n";
    if ($rc !== 0) $failures++;
  }
  echo $failures ? "\n$failures case(s) FAILED\n" : "\nall cases passed\n";
  exit($failures ? 1 : 0);
}

$case = $argv[1];
$GLOBALS['sql'] = array();
$GLOBALS['params'] = array();

// Stand-ins for what log.php leans on. dbFetchAll records the statement and its
// bound values; no rows come back, which is all these assertions need.
function dbFetchOne($sql, $col = null, $params = null) {
  return 0;
}
function dbFetchAll($sql, $col = null, $params = null) {
  $GLOBALS['sql'][] = $sql;
  $GLOBALS['params'][] = $params;
  return array();
}
function zm_session_start() {
}
function canView($area) {
  return true;
}
function validHtmlStr($s) {
  return htmlspecialchars($s, ENT_QUOTES);
}
function logState() {
  return 'ok';
}
function ajaxError($message) {
}
function ajaxResponse($data = array()) {
}
class FakeFormatter {
  public function format($t) {
    return (string)$t;
  }
}
$dateTimeFormatter = new FakeFormatter();

class FakeUser {
  private $viewable;
  public function __construct($viewable) {
    $this->viewable = $viewable;
  }
  public function viewableMonitorIds() {
    return $this->viewable;
  }
  public function Username() {
    return 'tester';
  }
}

eval('namespace ZM; class Logger { public static $codes = array(-3=>"PNC", -2=>"FAT", -1=>"ERR", 0=>"INF", 1=>"DBG"); }
  function Error($m) {} function Warning($m) {} function Debug($m) {}');

switch ($case) {
  case 'viewable':
    $user = new FakeUser(array(1, 3));
    $_REQUEST = array('task' => 'query', 'MonitorId' => '3');
    break;
  case 'not-viewable':
    $user = new FakeUser(array(1, 3));
    $_REQUEST = array('task' => 'query', 'MonitorId' => '2');
    break;
  case 'no-monitor':
    $user = new FakeUser(array(1, 3));
    $_REQUEST = array('task' => 'query');
    break;
  case 'empty-id':
    $user = new FakeUser(array(1, 3));
    $_REQUEST = array('task' => 'query', 'MonitorId' => '');
    break;
  case 'with-component':
    $user = new FakeUser(array(3));
    $_REQUEST = array('task' => 'query', 'MonitorId' => '3', 'Component' => array('web_js'));
    break;
  case 'array-id':
    $user = new FakeUser(array(3));
    $_REQUEST = array('task' => 'query', 'MonitorId' => array('3'));
    break;
}

require(__DIR__.'/../../web/ajax/log.php');

$sql = isset($GLOBALS['sql'][0]) ? preg_replace('/\s+/', ' ', $GLOBALS['sql'][0]) : '';
$params = isset($GLOBALS['params'][0]) ? $GLOBALS['params'][0] : array();

$failures = 0;
function check($label, $got, $want) {
  global $failures;
  $ok = ($got === $want);
  if (!$ok) $failures++;
  printf("[%s] %s\n", $ok ? 'PASS' : 'FAIL', $label);
  if (!$ok) printf("        got:  %s\n        want: %s\n", var_export($got, true), var_export($want, true));
}

$monitorComponents = array('zmc_m3', 'zma_m3', 'zms_m3', 'zmtrack_m3');

switch ($case) {
  case 'viewable':
    check('a viewable monitor adds a bound Component IN with its four daemons',
      strpos($sql, 'Component IN (?, ?, ?, ?)') !== false, true);
    check('the daemon names are bound, not interpolated',
      strpos($sql, 'zmc_m3') === false, true);
    check('the bound values are the monitor components then paging',
      array_slice($params, 0, 4), $monitorComponents);
    break;
  case 'not-viewable':
    check('a monitor the user may not view matches no rows',
      strpos($sql, ' WHERE 1=0 ') !== false, true);
    check('and binds no component names',
      $params, array(0, 100));
    break;
  case 'no-monitor':
    check('no MonitorId leaves the query unfiltered',
      strpos($sql, 'zmc_m') === false && strpos($sql, '1=0') === false, true);
    check('and the only bound values are paging',
      $params, array(0, 100));
    break;
  case 'empty-id':
    check('an empty MonitorId (the All option) leaves the query unfiltered',
      strpos($sql, 'zmc_m') === false && strpos($sql, '1=0') === false, true);
    break;
  case 'with-component':
    check('the monitor clause is added to the chosen Components',
      strpos($sql, 'Component IN (?) AND Component IN (?, ?, ?, ?)') !== false, true);
    check('existing filter values come first, monitor values after',
      $params, array_merge(array('web_js'), $monitorComponents, array(0, 100)));
    break;
  case 'array-id':
    check('a non-scalar MonitorId matches no rows',
      strpos($sql, ' WHERE 1=0 ') !== false, true);
    break;
}

exit($failures ? 1 : 0);
