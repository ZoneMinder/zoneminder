<?php
// The Log search boxes are plain text. A "_" or "%" typed into them is a
// literal character, not a LIKE wildcard, and the advanced search Server field
// names a server, which the Logs table only stores as ServerId.
if ($argc < 2) {
  $failures = 0;
  foreach (array('search-underscore', 'search-percent', 'adv-underscore', 'adv-server') as $case) {
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

$_REQUEST = array('task' => 'delete', 'all' => 1);
switch ($case) {
  case 'search-underscore':
    $_REQUEST['search'] = '_';
    break;
  case 'search-percent':
    $_REQUEST['search'] = '100%';
    break;
  case 'adv-underscore':
    $_REQUEST['filter'] = json_encode(array('File' => 'zm_monitor.cpp'));
    break;
  case 'adv-server':
    $_REQUEST['filter'] = json_encode(array('Server' => 'zm2'));
    break;
}

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
  case 'search-underscore':
    check('an underscore is escaped so it matches only a literal underscore',
      $queries[0]['params'][0], '%\\_%');
    break;
  case 'search-percent':
    check('a percent sign is escaped', $queries[0]['params'][0], '%100\\%%');
    break;
  case 'adv-underscore':
    check('advanced search escapes wildcards', $queries[0]['params'], array('zm\\_monitor.cpp'));
    break;
  case 'adv-server':
    check('Server is matched through the Servers table by name',
      $queries[0]['sql'], 'DELETE FROM Logs WHERE (ServerId IN (SELECT Id FROM Servers WHERE Name LIKE ?))');
    check('server name bound', $queries[0]['params'], array('zm2'));
    break;
}
exit($failures ? 1 : 0);
