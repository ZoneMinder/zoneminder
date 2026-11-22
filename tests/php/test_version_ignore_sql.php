<?php
// "Ignore this version" (includes/actions/version.php) saves
// ZM_DYN_LAST_VERSION, which zmupdate.pl stores from the update server's
// reply, as ZM_DYN_CURR_VERSION. The value must reach the database as a bound
// parameter, not as part of the SQL text.
//
// Run as: php tests/php/test_version_ignore_sql.php   (from the repo root)
// Pass another version.php as the first argument to test it.

$failures = 0;
$passes = 0;

function check($name, $got, $want) {
  global $failures, $passes;
  if ($got === $want) {
    $passes++;
    echo "  ok $name\n";
  } else {
    $failures++;
    echo "  FAIL $name (got ".var_export($got, true).", want ".var_export($want, true).")\n";
  }
}

$queries = array();
function dbQuery($sql, $params = null) {
  global $queries;
  $queries[] = array($sql, $params);
  return array();
}
function canEdit($area) { return true; }

$latest = "1.38.0' WHERE 1=1 -- ";
define('ZM_DYN_LAST_VERSION', $latest);

$action = 'version';
$_REQUEST['option'] = 'ignore';
require($argv[1] ?? __DIR__.'/../../web/includes/actions/version.php');

check('one query ran', count($queries), 1);
$sql = $queries[0][0] ?? '';
$params = $queries[0][1] ?? array();
check('the version text is not part of the SQL', strpos($sql, 'WHERE 1=1'), false);
check('the version is passed as a parameter', $params, array($latest));

echo "$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
