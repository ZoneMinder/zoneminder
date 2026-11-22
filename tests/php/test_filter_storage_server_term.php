<?php
// A filter term on StorageServerId used to produce the SQL column S.ServerId,
// but no event query joins Storage AS S, so the events list failed with
// "Unknown column S.ServerId". Both FilterTerm::sql_attr() and Filter::tree()
// now emit a subquery against Storage instead, matching the Perl filter.
//
// Run as: php tests/php/test_filter_storage_server_term.php [repo root]
// The optional root lets the same test run against an untouched checkout.

namespace ZM;

$root = isset($argv[1]) ? rtrim($argv[1], '/') : __DIR__.'/../..';
$includes = $root.'/web/includes';

// Stubs for the includes the classes pull in. database.php and Monitor.php are
// emptied so loading Filter.php does not need a database or a live ZM install.
$stubdir = '/tmp/numes-tests/filter_storage_stubs_'.getmypid();
@mkdir($stubdir, 0700, true);
foreach (array('database.php', 'Monitor.php') as $stub) {
  file_put_contents($stubdir.'/'.$stub, "<?php\n");
}
set_include_path($stubdir.PATH_SEPARATOR.$includes.PATH_SEPARATOR.get_include_path());

function translate($s) { return $s; }
function dbEscape($s) { return "'".addslashes($s)."'"; }
function Warning($s) { /* noop */ }
function Error($s)   { /* noop */ }
function Debug($s)   { /* noop */ }
function Info($s)    { /* noop */ }
function Fatal($s)   { /* noop */ }
function jsonEncode($v) { return json_encode($v); }
function jsonDecode($v) { return json_decode($v, true); }
if (!defined('STRF_FMT_DATETIME_DB')) define('STRF_FMT_DATETIME_DB', 'Y-m-d H:i:s');
if (!defined('ZM_SERVER_ID')) define('ZM_SERVER_ID', 1);

require_once $includes.'/FilterTerm.php';
require_once $includes.'/Filter.php';

$failures = 0;
$passes = 0;

function check($name, $got, $want) {
  global $failures, $passes;
  if ($got === $want) {
    $passes++;
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n";
  }
}

function norm($s) { return trim(preg_replace('/\s+/', ' ', $s)); }

function tree_sql($node) {
  if (!is_array($node)) return '';
  $sql = '';
  if (isset($node['data']['sqlValue'])) $sql .= $node['data']['sqlValue'].' ';
  if (isset($node['left'])) $sql .= tree_sql($node['left']);
  if (isset($node['right'])) $sql .= tree_sql($node['right']);
  return $sql;
}

$term_sql = (new FilterTerm(null, array('attr'=>'StorageServerId', 'op'=>'=', 'val'=>'2'), 0))->sql();
check('FilterTerm does not produce S.ServerId', strpos($term_sql, 'S.ServerId'), false);
check('FilterTerm reads Storage by E.StorageId', strpos($term_sql, 'Storage.Id=E.StorageId') !== false, true);
check('FilterTerm StorageServerId SQL',
  norm($term_sql),
  "(SELECT Storage.ServerId FROM Storage WHERE Storage.Id=E.StorageId) = '2'");

// Filter::tree() builds the same expression for the filter debug SQL and zmfilter.
// The ServerId cases concatenate onto an uninitialised $sqlValue, a pre-existing
// warning that is not the subject of this test.
$error_level = error_reporting();
error_reporting($error_level & ~E_WARNING);
$filter = new Filter();
$filter->terms(array(array('attr'=>'StorageServerId', 'op'=>'=', 'val'=>'2')));
$tree_sql = norm(tree_sql($filter->tree()));
error_reporting($error_level);
check('Filter::tree does not produce S.ServerId', strpos($tree_sql, 'S.ServerId'), false);
check('Filter::tree reads Storage by E.StorageId', strpos($tree_sql, 'Storage.Id=E.StorageId') !== false, true);

register_shutdown_function(function() use ($stubdir) {
  foreach (array('database.php', 'Monitor.php') as $stub) @unlink($stubdir.'/'.$stub);
  @rmdir($stubdir);
});

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
