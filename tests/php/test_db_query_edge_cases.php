<?php
/*
 * dbQuery()/dbNumRows()/dbFetchAssoc() edge cases, with a stubbed connection.
 *   1. A PHP false bound as PARAM_STR becomes '' and strict-mode MySQL rejects
 *      it for an integer column; booleans must be bound as PARAM_BOOL.
 *   2. dbNumRows() and dbFetchAssoc() called ->rowCount()/->fetch() on the NULL
 *      that dbQuery() returns for a failed statement: a fatal Error instead of
 *      an empty result like dbFetchAll() gives.
 *
 * Run: php tests/php/test_db_query_edge_cases.php
 */
$src = file_get_contents(dirname(__DIR__, 2).'/web/includes/database.php');
$tests = 0; $failures = 0;
function check($label, $cond) {
  global $tests, $failures; $tests++;
  if ($cond) echo "  ok  - $label\n"; else { $failures++; echo "  NOT OK - $label\n"; }
}
function extract_function($src, $name) {
  $sig = strpos($src, 'function '.$name.'(');
  $brace = strpos($src, '{', $sig);
  $depth = 0;
  for ($i = $brace; $i < strlen($src); $i++) {
    if ($src[$i] === '{') $depth++;
    elseif ($src[$i] === '}' && --$depth === 0) return substr($src, $sig, $i - $sig + 1);
  }
}
eval('namespace ZM { function Error($m){} function Debug($m){} function Warning($m){} }');
foreach (['dbQuery','dbParamsForLog','dbNumRows','dbFetchAssoc','dbLog','dbFetchAll'] as $f)
  eval(extract_function($src, $f));

define('DB_LOG_OFF', 0); define('DB_LOG_ONLY', 1); define('DB_LOG_DEBUG', 2);
$GLOBALS['dbLogLevel'] = DB_LOG_OFF;

class StubStmt {
  public $bound = [];
  public $ok;
  function __construct($ok) { $this->ok = $ok; }
  function bindValue($k, $v, $t) { $this->bound[] = [$v, $t]; }
  function execute() { throw new PDOException('boom'); }
  function rowCount() { return 0; }
  function fetch() { return false; }
}
class OkStmt extends StubStmt { function execute() { return true; } }
class StubConn {
  public $stmt;
  function prepare($sql) { return $this->stmt; }
  function query($sql) { throw new PDOException('boom'); }
  function errorInfo() { return ['00000', 0, '']; }
}
function zmDbConn() { return $GLOBALS['conn']; }

$conn = new StubConn();
$conn->stmt = new OkStmt(true);
dbQuery('INSERT INTO t VALUES (?,?,?,?)', [false, true, 5, null]);
$types = array_map(function($b) { return $b[1]; }, $conn->stmt->bound);
check('false is bound as PARAM_BOOL', $types[0] === PDO::PARAM_BOOL);
check('true is bound as PARAM_BOOL', $types[1] === PDO::PARAM_BOOL);
check('int still PARAM_INT', $types[2] === PDO::PARAM_INT);

$conn->stmt = new StubStmt(true);  // execute() throws
try {
  $r = dbNumRows('DELETE FROM Tags WHERE Id=?', [1]);
  check('dbNumRows on failed query does not fatal', true);
  check('dbNumRows on failed query returns 0', $r === 0);
} catch (Error $e) {
  check('dbNumRows on failed query does not fatal ('.$e->getMessage().')', false);
}
try {
  $r = dbFetchAssoc('SELECT Id FROM Servers', 'Id');
  check('dbFetchAssoc on failed query does not fatal', true);
  check('dbFetchAssoc on failed query returns empty array', $r === []);
} catch (Error $e) {
  check('dbFetchAssoc on failed query does not fatal ('.$e->getMessage().')', false);
}
echo "$tests tests, $failures failures\n";
exit($failures ? 1 : 0);
