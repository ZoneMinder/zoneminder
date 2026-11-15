<?php
if ($argc < 2) {
  $failures = 0;
  foreach (array('default', 'component', 'code', 'id', 'bad') as $case) {
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

function dbFetchOne($sql, $col = null, $params = null) {
  return 0;
}
function dbFetchAll($sql, $col = null, $params = null) {
  $GLOBALS['sql'][] = $sql;
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
$user = null;
eval('namespace ZM; class Logger { public static $codes = array(-3=>"PNC", -2=>"FAT", -1=>"ERR", 0=>"INF", 1=>"DBG"); }
  function Error($m) {} function Warning($m) {} function Debug($m) {}');

$request = array('task' => 'query', 'order' => 'asc');
switch ($case) {
  case 'component':
    $request['sort'] = 'Component';
    break;
  case 'code':
    $request['sort'] = 'Code';
    break;
  case 'id':
    $request['sort'] = 'Id';
    break;
  case 'bad':
    $request['sort'] = 'Nope';
    break;
}
$_REQUEST = $request;

require(__DIR__.'/../../web/ajax/log.php');

$sql = isset($GLOBALS['sql'][0]) ? preg_replace('/\s+/', ' ', $GLOBALS['sql'][0]) : '';
$failures = 0;
function check($label, $got, $want) {
  global $failures;
  $ok = ($got === $want);
  if (!$ok) $failures++;
  printf("[%s] %s\n", $ok ? 'PASS' : 'FAIL', $label);
  if (!$ok) printf("        got:  %s\n        want: %s\n", var_export($got, true), var_export($want, true));
}

switch ($case) {
  case 'default':
    check('time sort breaks ties by id in the page query',
      strpos($sql, 'ORDER BY TimeKey ASC, Id ASC LIMIT') !== false, true);
    check('and in the outer query',
      strpos($sql, 'ORDER BY t1.TimeKey ASC, t1.Id ASC') !== false, true);
    break;
  case 'component':
    check('a column with repeated values breaks ties by id so pages do not overlap',
      strpos($sql, 'ORDER BY Component ASC, Id ASC LIMIT') !== false, true);
    check('outer query matches',
      strpos($sql, 'ORDER BY t1.Component ASC, t1.Id ASC') !== false, true);
    break;
  case 'code':
    check('the Code column sorts by level, not by its text',
      strpos($sql, 'ORDER BY Level ASC, Id ASC LIMIT') !== false, true);
    break;
  case 'id':
    check('id sort still runs', $sql !== '', true);
    break;
  case 'bad':
    check('an unknown sort field runs no query', $sql, '');
    break;
}
exit($failures ? 1 : 0);
