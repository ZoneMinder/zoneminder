<?php
// The Log view's ServerId filter is a scalar. Bootstrap can also send it as a
// one-element array; that used to be bound directly as a query parameter, which
// PDO rejects. This checks a scalar binds normally and an array never reaches
// the parameters.
$root = isset($argv[1]) ? $argv[1] : realpath(__DIR__.'/../..');

if ($argc < 3) {
  $failures = 0;
  foreach (array('serverid-scalar', 'serverid-array') as $case) {
    $out = array();
    $rc = 0;
    exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' '.escapeshellarg($root).' '.escapeshellarg($case).' 2>&1', $out, $rc);
    echo implode("\n", $out)."\n";
    if ($rc !== 0) $failures++;
  }
  echo $failures ? "\n$failures case(s) FAILED\n" : "\nall cases passed\n";
  exit($failures ? 1 : 0);
}

$case = $argv[2];
$GLOBALS['queries'] = array();

function dbQuery($sql, $params = null) {
  $GLOBALS['queries'][] = array('sql' => $sql, 'params' => $params);
  return true;
}
function canEdit($area) {
  return true;
}
function validHtmlStr($s) {
  return htmlspecialchars($s, ENT_QUOTES);
}
function ajaxError($message) {
}
function ajaxResponse($data = array()) {
}
$user = null;
eval('namespace ZM; class Logger { public static $codes = array(-3=>"PNC", -2=>"FAT", -1=>"ERR", 0=>"INF", 1=>"DBG"); }
  function Error($m) {} function Warning($m) {} function Debug($m) {}');

$_REQUEST = array('task' => 'delete', 'all' => 1);
switch ($case) {
  case 'serverid-scalar':
    $_REQUEST['ServerId'] = '1';
    break;
  case 'serverid-array':
    $_REQUEST['ServerId'] = array('1');
    break;
}

require("$root/web/ajax/log.php");

$queries = $GLOBALS['queries'];
$failures = 0;
function ok($label, $cond) {
  global $failures;
  if (!$cond) $failures++;
  printf("%s - %s\n", $cond ? 'ok' : 'FAIL', $label);
}

switch ($case) {
  case 'serverid-scalar':
    $sql = isset($queries[0]) ? $queries[0]['sql'] : '';
    $params = isset($queries[0]['params']) && is_array($queries[0]['params']) ? $queries[0]['params'] : array();
    ok('scalar ServerId produces a ServerId = ? clause', strpos($sql, 'ServerId = ?') !== false);
    ok('scalar ServerId is bound as a parameter', in_array('1', $params, true));
    break;
  case 'serverid-array':
    $found_array = false;
    $found_sql = false;
    foreach ($queries as $query) {
      if (isset($query['params']) && is_array($query['params'])) {
        foreach ($query['params'] as $param) {
          if (is_array($param)) $found_array = true;
        }
      }
      if (strpos($query['sql'], 'ServerId = ?') !== false) $found_sql = true;
    }
    ok('no parameter is an array', !$found_array);
    ok('array ServerId does not produce a ServerId = ? clause', !$found_sql);
    break;
}
exit($failures ? 1 : 0);
