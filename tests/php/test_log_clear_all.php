<?php
// web/ajax/log.php gained an "all" path so the Log view's Clear Logs button can
// empty the table in one statement instead of posting ids 100 at a time.  The
// property that matters is the guard: a delete request that arrives with no ids
// and no explicit all must delete nothing, so a malformed or truncated request
// can never wipe the table.
// Run: php tests/php/test_log_clear_all.php   (from the repo root)
// refs #4727

// Each case includes web/ajax/log.php, which defines functions at file scope, so
// it can only be included once per process; the driver re-runs itself per case.
if ($argc < 2) {
  $cases = array(
    'ids', 'all', 'empty-ids', 'no-ids', 'empty-all', 'ids-and-all', 'not-permitted',
  );
  $failures = 0;
  foreach ($cases as $case) {
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
$GLOBALS['errors'] = array();

// Stand-ins for the ZM globals log.php leans on.
function dbQuery($sql, $params = null) {
  $GLOBALS['queries'][] = array('sql' => $sql, 'params' => $params);
  return null;
}
function canEdit($area) {
  return $GLOBALS['can_edit'];
}
function canView($area) {
  return true;
}
function validHtmlStr($s) {
  return htmlspecialchars($s, ENT_QUOTES);
}
function ajaxError($message) {
  $GLOBALS['errors'][] = $message;
}
function ajaxResponse($data = array()) {
}
class FakeUser {
  public function Username() {
    return 'tester';
  }
}
$user = new FakeUser();
$GLOBALS['can_edit'] = true;

switch ($case) {
  case 'ids':
    $_REQUEST = array('task' => 'delete', 'ids' => array('3', '4', 'not-a-number'));
    break;
  case 'all':
    $_REQUEST = array('task' => 'delete', 'all' => 1);
    break;
  case 'empty-ids':
    $_REQUEST = array('task' => 'delete', 'ids' => array());
    break;
  case 'no-ids':
    $_REQUEST = array('task' => 'delete');
    break;
  case 'empty-all':
    $_REQUEST = array('task' => 'delete', 'all' => 0);
    break;
  case 'ids-and-all':
    $_REQUEST = array('task' => 'delete', 'ids' => array('9'), 'all' => 1);
    break;
  case 'not-permitted':
    $GLOBALS['can_edit'] = false;
    $_REQUEST = array('task' => 'delete', 'all' => 1);
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
  case 'ids':
    check('ids: one parameterised delete', count($queries), 1);
    check('ids: placeholders, never interpolation',
      $queries[0]['sql'], 'DELETE FROM Logs WHERE Id IN (?,?,?)');
    check('ids: values are cast to int', $queries[0]['params'], array(3, 4, 0));
    break;
  case 'all':
    check('all: one delete', count($queries), 1);
    check('all: clears the table in a single statement',
      $queries[0]['sql'], 'DELETE FROM Logs');
    break;
  case 'empty-ids':
    check('empty ids array deletes nothing', count($queries), 0);
    break;
  case 'no-ids':
    check('a delete with no ids and no all deletes nothing', count($queries), 0);
    break;
  case 'empty-all':
    check('a falsy all deletes nothing', count($queries), 0);
    break;
  case 'ids-and-all':
    check('ids win over all: one delete', count($queries), 1);
    check('ids win over all: scoped to the ids',
      $queries[0]['sql'], 'DELETE FROM Logs WHERE Id IN (?)');
    break;
  case 'not-permitted':
    check('clear all requires edit on System', count($queries), 0);
    check('and reports why', count($GLOBALS['errors']), 1);
    break;
}
exit($failures ? 1 : 0);
