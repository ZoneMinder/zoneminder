<?php
if ($argc < 2) {
  $failures = 0;
  foreach (array('two-columns', 'one-column', 'invalid-and-valid') as $case) {
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
  case 'two-columns':
    $filter = array('Component' => 'zmc', 'Pid' => '12');
    break;
  case 'one-column':
    $filter = array('Message' => 'boom');
    break;
  case 'invalid-and-valid':
    $filter = array('DateTime' => 'x', 'Message' => 'boom', 'Pid' => '12');
    break;
}
$_REQUEST = array('task' => 'delete', 'all' => 1, 'filter' => json_encode($filter));

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
  case 'two-columns':
    check('every filled-in column must match',
      $queries[0]['sql'], 'DELETE FROM Logs WHERE (Component LIKE ? AND Pid LIKE ?)');
    check('values bound in column order', $queries[0]['params'], array('zmc', '12'));
    break;
  case 'one-column':
    check('a single column is unchanged',
      $queries[0]['sql'], 'DELETE FROM Logs WHERE (Message LIKE ?)');
    break;
  case 'invalid-and-valid':
    check('columns the table lacks are dropped, the rest are ANDed',
      $queries[0]['sql'], 'DELETE FROM Logs WHERE (Message LIKE ? AND Pid LIKE ?)');
    break;
}
exit($failures ? 1 : 0);
