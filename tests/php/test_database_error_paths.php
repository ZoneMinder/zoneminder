<?php
// Tests dbQuery()'s error paths for a prepare() or query() that returns false
// rather than throwing (PDO::ERRMODE_SILENT). They logged $pdo->errorInfo, an
// undefined variable, so the message had no error in it and PHP warned; and
// dbLastError() was read from the connection after ZM\Error() had logged, which
// with a database log target is another statement on the same connection.
//
// Run: sudo -u www-data php tests/php/test_database_error_paths.php
//      (from the repo root, on a tree with a readable zm.conf)

set_include_path(__DIR__.'/../../web/includes'.PATH_SEPARATOR.get_include_path());
require_once('config.php');   // connects to DB + loads config

$failures = 0;
$passes = 0;

function check($name, $ok, $detail = '') {
  global $failures, $passes;
  if ($ok) {
    $passes++;
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "not ok - $name".($detail === '' ? '' : " ($detail)")."\n";
  }
}

$warnings = array();
set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$warnings) {
  // Only warnings raised by database.php: the logger has its own CLI warnings.
  if (basename($errfile) == 'database.php') $warnings[] = "$errstr at line $errline";
  return true;
});

$conn = zmDbConn();
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);

// query() path: no parameters.
$warnings = array();
check('a failing query returns NULL', dbQuery('SELEC 1') === NULL);
check('a failing query raises no PHP warning', !$warnings, implode('; ', $warnings));
check('dbLastError() has the error for a failing query', strpos(dbLastError(), 'syntax') !== false, dbLastError());

// prepare() path: with parameters. Emulated prepares only fail at execute, so
// use server-side ones to make prepare() itself fail.
$conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
$warnings = array();
check('a failing prepare returns NULL', dbQuery('SELEC ?', array(1)) === NULL);
check('a failing prepare raises no PHP warning', !$warnings, implode('; ', $warnings));
check('dbLastError() has the error for a failing prepare', strpos(dbLastError(), 'syntax') !== false, dbLastError());

restore_error_handler();
echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
