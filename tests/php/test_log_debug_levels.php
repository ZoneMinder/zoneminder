<?php
if ($argc < 2) {
  $failures = 0;
  foreach (array('debug-only', 'debug-and-error', 'error-only') as $case) {
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

switch ($case) {
  case 'debug-only':
    $levels = array('DBG');
    break;
  case 'debug-and-error':
    $levels = array('ERR', 'DBG');
    break;
  case 'error-only':
    $levels = array('ERR');
    break;
}
$_REQUEST = array('task' => 'delete', 'all' => 1, 'level' => $levels);

require(__DIR__.'/../../web/ajax/log.php');

$queries = $GLOBALS['queries'];
$failures = 0;
function check($label, $got, $want) {
  global $failures;
  $ok = ($got === $want);
  if (!$ok) $failures++;
  printf("[%s] %s\n", $ok ? 'PASS' : 'FAIL', $label);
  if (!$ok) printf("        got:  %s\n        want: %s\n", var_export($got, true), var_export($want, true));
}

switch ($case) {
  case 'debug-only':
    check('DBG covers DB1 to DB9, not only level 1',
      $queries[0]['sql'], 'DELETE FROM Logs WHERE Level >= ?');
    check('bound to the first debug level', $queries[0]['params'], array(1));
    break;
  case 'debug-and-error':
    check('DBG alongside another level',
      $queries[0]['sql'], 'DELETE FROM Logs WHERE ( Level IN (?) OR Level >= ?)');
    check('values follow the placeholders', $queries[0]['params'], array(-1, 1));
    break;
  case 'error-only':
    check('other levels still match exactly',
      $queries[0]['sql'], 'DELETE FROM Logs WHERE  Level IN (?)');
    break;
}
exit($failures ? 1 : 0);
