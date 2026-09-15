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
    'all-filtered-level', 'all-filtered-dates', 'all-filtered-search',
    'all-filtered-server', 'truncate-falls-back', 'filter-parity',
    'advsearch-all-invalid', 'advsearch-mixed',
    'unparseable-date', 'unknown-level', 'blank-date-is-no-filter', 'delete-fails',
    'advsearch-pseudo-column',
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
  // dbQuery returns null on failure. TRUNCATE is the only statement here whose
  // failure the caller reacts to, so let a case declare that it fails.
  if (strpos($sql, 'TRUNCATE') === 0 && !empty($GLOBALS['truncate_fails'])) return null;
  if (strpos($sql, 'DELETE') === 0 && !empty($GLOBALS['delete_fails'])) return null;
  return true;
}
function dbFetchOne($sql, $col = null, $params = null) {
  return 0;
}
function dbFetchAll($sql, $col = null, $params = null) {
  return array();
}
function zm_session_start() {
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
$GLOBALS['truncate_fails'] = false;
$GLOBALS['delete_fails'] = false;

// A level filter needs Logger::$codes to map 'ERR' to its numeric level.
namespace_stub_logger();
function namespace_stub_logger() {
  if (!class_exists('ZM\\Logger')) {
    eval('namespace ZM; class Logger { public static $codes = array(-3=>"PNC",-2=>"FAT",-1=>"ERR",0=>"INF",1=>"DBG"); }');
  }
  if (!function_exists('ZM\\Error')) {
    eval('namespace ZM; function Error($m) {} function Warning($m) {} function Debug($m) {}');
  }
}

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
  case 'all-filtered-level':
    $_REQUEST = array('task' => 'delete', 'all' => 1, 'level' => array('ERR'));
    break;
  case 'all-filtered-dates':
    $_REQUEST = array('task' => 'delete', 'all' => 1,
      'StartDateTime' => '2026-01-01 00:00:00', 'EndDateTime' => '2026-01-02 00:00:00');
    break;
  case 'all-filtered-search':
    $_REQUEST = array('task' => 'delete', 'all' => 1, 'search' => 'boom');
    break;
  case 'all-filtered-server':
    $_REQUEST = array('task' => 'delete', 'all' => 1, 'ServerId' => '4');
    break;
  case 'truncate-falls-back':
    $GLOBALS['truncate_fails'] = true;
    $_REQUEST = array('task' => 'delete', 'all' => 1);
    break;
  case 'filter-parity':
    $_REQUEST = array('task' => 'delete', 'all' => 1, 'level' => array('ERR'),
      'ServerId' => '4', 'StartDateTime' => '2026-01-01 00:00:00');
    break;
  case 'advsearch-all-invalid':
    $_REQUEST = array('task' => 'delete', 'all' => 1,
      'filter' => json_encode(array('NotAColumn' => 'x', 'AlsoNot' => 'y')));
    break;
  case 'advsearch-mixed':
    $_REQUEST = array('task' => 'delete', 'all' => 1,
      'filter' => json_encode(array('NotAColumn' => 'x', 'Message' => 'boom')));
    break;
  case 'unparseable-date':
    // The date fields accept free text (constrainInput: false), so this is what
    // a half-typed date posts.
    $_REQUEST = array('task' => 'delete', 'all' => 1, 'StartDateTime' => '2026-13-45');
    break;
  case 'unknown-level':
    $_REQUEST = array('task' => 'delete', 'all' => 1, 'level' => array('NOPE'));
    break;
  case 'blank-date-is-no-filter':
    $_REQUEST = array('task' => 'delete', 'all' => 1, 'StartDateTime' => '', 'EndDateTime' => '');
    break;
  case 'delete-fails':
    $GLOBALS['delete_fails'] = true;
    $_REQUEST = array('task' => 'delete', 'all' => 1, 'ServerId' => '4');
    break;
  case 'advsearch-pseudo-column':
    // The advanced search form offers DateTime and Server because the view
    // declares them as fields, but they are built in PHP and are not columns of
    // Logs, so they must not reach the SQL.
    $_REQUEST = array('task' => 'delete', 'all' => 1,
      'filter' => json_encode(array('DateTime' => '2026-08')));
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
    check('all: one statement', count($queries), 1);
    check('all: with nothing filtered, truncate rather than delete row by row',
      $queries[0]['sql'], 'TRUNCATE TABLE Logs');
    check('all: no bind values', $queries[0]['params'], null);
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

  case 'all-filtered-level':
    check('a level filter deletes by filter, not the whole table', count($queries), 1);
    check('level: scoped DELETE, never TRUNCATE',
      $queries[0]['sql'], 'DELETE FROM Logs WHERE  Level IN (?)');
    check('level: bound to the numeric level, not the code', $queries[0]['params'], array(-1));
    break;

  case 'all-filtered-dates':
    check('a date range deletes by filter', count($queries), 1);
    check('dates: bounded both ends',
      $queries[0]['sql'], 'DELETE FROM Logs WHERE TimeKey >= ? AND TimeKey <= ?');
    check('dates: bound as timestamps', $queries[0]['params'],
      array(strtotime('2026-01-01 00:00:00'), strtotime('2026-01-02 00:00:00')));
    break;

  case 'all-filtered-search':
    check('a search deletes by filter', count($queries), 1);
    check('search: still parameterised, never interpolated',
      strpos($queries[0]['sql'], 'boom'), false);
    check('search: wildcarded once per searchable column',
      count($queries[0]['params']), 9);
    break;

  case 'all-filtered-server':
    check('a server filter deletes by filter', count($queries), 1);
    check('server: scoped DELETE',
      $queries[0]['sql'], 'DELETE FROM Logs WHERE ServerId = ?');
    check('server: bound value', $queries[0]['params'], array('4'));
    break;

  case 'truncate-falls-back':
    check('a failed TRUNCATE falls back to DELETE', count($queries), 2);
    check('truncate first', $queries[0]['sql'], 'TRUNCATE TABLE Logs');
    check('then a plain DELETE', $queries[1]['sql'], 'DELETE FROM Logs');
    break;

  case 'advsearch-all-invalid':
    // The dangerous one. Every requested column is rejected, so the clause is
    // empty. If that were read as "no filter" the table would be truncated.
    check('all-invalid advanced search does not truncate', count($queries), 1);
    check('all-invalid advanced search matches nothing instead of everything',
      $queries[0]['sql'], 'DELETE FROM Logs WHERE 1=0');
    check('and never issues a TRUNCATE',
      strpos($queries[0]['sql'], 'TRUNCATE'), false);
    break;

  case 'advsearch-mixed':
    check('a partially valid advanced search keeps the valid column',
      $queries[0]['sql'], 'DELETE FROM Logs WHERE (Message LIKE ?)');
    check('and binds only that column\'s value', $queries[0]['params'], array('boom'));
    break;

  case 'unparseable-date':
    // The dangerous disagreement: the browser counts StartDateTime as a filter
    // and says "Clear Filtered Logs", but strtotime rejects it, so the WHERE
    // comes out empty. Truncating on that would destroy everything.
    check('an unparseable date never truncates', count($queries), 0);
    check('and says why instead of silently clearing', count($GLOBALS['errors']), 1);
    break;

  case 'unknown-level':
    check('an unrecognised level never truncates', count($queries), 0);
    check('and reports it', count($GLOBALS['errors']), 1);
    break;

  case 'blank-date-is-no-filter':
    // Empty strings are not a filter, so this must still be the fast path.
    check('blank filter fields still truncate', count($queries), 1);
    check('blank filter fields are not mistaken for a filter',
      $queries[0]['sql'], 'TRUNCATE TABLE Logs');
    break;

  case 'delete-fails':
    check('a failed filtered delete is reported', count($GLOBALS['errors']), 1);
    break;

  case 'advsearch-pseudo-column':
    check('a view-only column never reaches the SQL',
      strpos($queries[0]['sql'], 'DateTime'), false);
    // It matches nothing rather than everything, and the query the user was
    // looking at resolves the same way, so the two agree on an empty result.
    check('it matches nothing instead of truncating',
      $queries[0]['sql'], 'DELETE FROM Logs WHERE 1=0');
    check('exactly one statement', count($queries), 1);
    break;

  case 'filter-parity':
    // The property that matters: the rows deleted are the rows the query would
    // have shown. Same builder, so the clause and bind values must match.
    $expected = logFilter();
    check('parity: one delete', count($queries), 1);
    check('parity: delete clause is exactly the query clause',
      $queries[0]['sql'], 'DELETE FROM Logs WHERE '.$expected['where']);
    check('parity: identical bind values', $queries[0]['params'], $expected['values']);
    check('parity: every filter made it in, so this is not a bare DELETE',
      substr_count($queries[0]['sql'], '?'), 3);
    break;
}
exit($failures ? 1 : 0);
