<?php
// With authentication off the web user has no Id. TagOrder::recordUsage() still ran
// INSERT INTO User_Preferences with UserId NULL (a NOT NULL column with a foreign key),
// so every tag assignment logged an SQL error. A user without an Id has no preferences,
// so nothing may be queried or written for them.
// Run as: php tests/php/test_tagorder_no_user.php [path to web dir]
$web = $argv[1] ?? __DIR__.'/../../web';
$queries = array();
function dbQuery($sql, $params = null) { global $queries; $queries[] = $sql; return true; }
function dbFetchOne($sql, $x = null, $params = null) { global $queries; $queries[] = $sql; return null; }
$dir = sys_get_temp_dir().'/zm_tagorder_'.getmypid();
@mkdir($dir);
file_put_contents($dir.'/database.php', "<?php\n");
$src = file_get_contents($web.'/includes/TagOrder.php');
file_put_contents($dir.'/TagOrder.php', $src);
chdir($dir);
require $dir.'/TagOrder.php';

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

\ZM\TagOrder::recordUsage(null, 5);
check('recordUsage without a user writes nothing', $queries, array());
check('load without a user returns no tags', \ZM\TagOrder::load(null), array());
check('load without a user queries nothing', $queries, array());

// With authentication off the user is the built-in default user, Id 0
$queries = array();
\ZM\TagOrder::recordUsage(0, 5);
check('recordUsage for the Id 0 default user writes nothing', $queries, array());
check('load for the Id 0 default user returns no tags', \ZM\TagOrder::load(0), array());

$queries = array();
\ZM\TagOrder::recordUsage(3, 5);
check('recordUsage with a user still writes', count($queries), 2);
unlink($dir.'/database.php'); unlink($dir.'/TagOrder.php'); rmdir($dir);
exit($failures ? 1 : 0);
