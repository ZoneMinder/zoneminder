<?php
$repoRoot = $argv[1] ?? realpath(__DIR__.'/../..');
$GLOBALS['sql'] = array();
$GLOBALS['response'] = array();

function dbFetchOne($sql, $col = null, $params = null) {
  return 0;
}
function dbFetchAll($sql, $col = null, $params = null) {
  $GLOBALS['sql'][] = $sql;
  if (strpos($sql, 'FROM `Logs`') !== false || strpos($sql, 'Logs') !== false) {
    return array(array(
      'Id' => 1,
      'TimeKey' => 1700000000,
      'Component' => '<img src=x onerror=alert(1)>',
      'ServerId' => 0,
      'Pid' => 1,
      'Code' => 0,
      'Message' => '<b>m</b>',
      'File' => 'f.cpp',
      'Line' => 3,
    ));
  }
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
  $GLOBALS['response'] = $data;
}
class FakeFormatter {
  public function format($t) {
    return (string)$t;
  }
}
$dateTimeFormatter = new FakeFormatter();
$user = null;
eval('namespace ZM; class Logger { public static $codes = array(-3=>"PNC", -2=>"FAT", -1=>"ERR", 0=>"INF", 1=>"DBG"); }
  function Error($m) {} function Warning($m) {} function Debug($m) {}');

$_REQUEST = array('task' => 'query', 'order' => 'asc');

require("$repoRoot/web/ajax/log.php");

$failures = 0;
$tests = 0;
function check($label, $cond) {
  global $failures, $tests;
  $tests++;
  if (!$cond) $failures++;
  printf("  %s - %s\n", $cond ? 'ok' : 'NOT OK', $label);
}

$response = $GLOBALS['response'];
check(
  'Component is HTML-escaped',
  isset($response['rows'][0]['Component'])
    && $response['rows'][0]['Component'] === '&lt;img src=x onerror=alert(1)&gt;'
);
check(
  'Message is HTML-escaped',
  isset($response['rows'][0]['Message'])
    && $response['rows'][0]['Message'] === '&lt;b&gt;m&lt;/b&gt;'
);
check(
  'response has exactly one row',
  isset($response['rows']) && is_array($response['rows']) && count($response['rows']) === 1
);

printf("%d tests, %d failures\n", $tests, $failures);
exit($failures ? 1 : 0);
