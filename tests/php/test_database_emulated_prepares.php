<?php
// Tests the client-side (emulated) prepares that dbConnect() in
// web/includes/database.php uses from PHP 8.1: every value read from every
// table keeps the PHP type and value it has with server-side prepares, LIMIT ?
// still works because dbQuery() binds integers as integers, and a string with
// two statements is refused.
//
// Run: sudo -u www-data php tests/php/test_database_emulated_prepares.php
//      (from the repo root, on a tree with a readable zm.conf)

if (PHP_VERSION_ID < 80100) {
  echo "1..0 # SKIP server-side prepares are kept before PHP 8.1\n";
  exit(0);
}

set_include_path(__DIR__.'/../../web/includes'.PATH_SEPARATOR.get_include_path());
require_once('config.php');   // connects to DB + loads config

$failures = 0;
$passes = 0;

function check($name, $got, $expected) {
  global $failures, $passes;
  if ($got === $expected) {
    $passes++;
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "not ok - $name (got ".var_export($got, true)." expected ".var_export($expected, true).")\n";
  }
}

$emulated = zmDbConn();
check('prepares are emulated', $emulated->getAttribute(PDO::ATTR_EMULATE_PREPARES), true);

// A second connection with the same settings, switched back to server-side prepares.
dbDisconnect();
$native = zmDbConn();
$native->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
$GLOBALS['dbConn'] = $emulated;

// Every row the web UI and the API could read: the same values and types both ways.
$tables = $native->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
$rows = 0;
$differences = array();
foreach ($tables as $table) {
  $sql = "SELECT * FROM `$table` ORDER BY 1 LIMIT ?";
  $a = $native->prepare($sql);
  $a->bindValue(1, 50, PDO::PARAM_INT);
  $a->execute();
  $b = $emulated->prepare($sql);
  $b->bindValue(1, 50, PDO::PARAM_INT);
  $b->execute();
  $nativeRows = $a->fetchAll(PDO::FETCH_ASSOC);
  $emulatedRows = $b->fetchAll(PDO::FETCH_ASSOC);
  $rows += count($nativeRows);
  if ($nativeRows !== $emulatedRows) {
    foreach ($nativeRows as $i => $row) {
      foreach ($row as $column => $value) {
        $other = isset($emulatedRows[$i]) ? $emulatedRows[$i][$column] : null;
        if ($value !== $other)
          $differences[] = "$table.$column: ".var_export($value, true).' vs '.var_export($other, true);
      }
    }
    if (!$differences) $differences[] = "$table: row count or order";
  }
}
echo "# compared $rows rows of ".count($tables)." tables\n";
check('every column keeps its type and value', array_slice($differences, 0, 10), array());

// Expressions the web code selects, not only stored columns.
$expressions = 'SELECT COUNT(*) AS c, SUM(Id) AS s, MAX(Id) AS m, UNIX_TIMESTAMP(NOW()) AS u, NULL AS n, 1.5 AS d, Id/2 AS q FROM Config';
check('aggregates and expressions keep their types',
  $emulated->query($expressions)->fetch(PDO::FETCH_ASSOC), $native->query($expressions)->fetch(PDO::FETCH_ASSOC));

// dbQuery() binds integers as integers, so LIMIT ? is not quoted.
check('LIMIT ? with a positional integer',
  count(dbFetchAll('SELECT Name FROM Config ORDER BY Id LIMIT ?', NULL, array(3))), 3);
check('LIMIT ?, ? with integers',
  dbFetchAll('SELECT Id FROM Config ORDER BY Id LIMIT ?, ?', 'Id', array(1, 1)),
  dbFetchAll('SELECT Id FROM Config ORDER BY Id LIMIT 1, 1', 'Id'));
check('LIMIT :n with a named integer',
  count(dbFetchAll('SELECT Name FROM Config WHERE Name LIKE :p ORDER BY Id LIMIT :n', NULL, array(':p' => 'ZM_%', ':n' => 2))), 2);
check('a string parameter is still a string',
  dbFetchOne('SELECT ? AS v', 'v', array('007')), '007');
check('a null parameter is NULL',
  dbFetchOne('SELECT ? IS NULL AS v', 'v', array(null)), 1);
check('a string that looks like an integer still matches a string column',
  dbFetchOne('SELECT COUNT(*) AS c FROM Config WHERE Name=?', 'c', array('ZM_WEB_TITLE')), 1);

// Two statements in one string are refused, as server-side prepares refuse them.
check('two statements in one string are refused', dbQuery('SELECT 1; SELECT 2'), null);
check('two statements with parameters are refused', dbQuery('SELECT ?; SELECT 2', array(1)), null);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
?>
