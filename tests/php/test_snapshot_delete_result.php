<?php
// Deleting snapshots from the Snapshots list always answered "Snapshot deleted.": the
// two DELETE queries in Snapshot::delete() weren't checked, deleteRequest() didn't look
// at the result, and snapshots.js ignored the reply. A failed delete looked like it worked.
// Snapshot::delete() now returns whether it worked and the request reports the failures.
//
// Run as: php tests/php/test_snapshot_delete_result.php [root]   (root defaults to the repo)

namespace ZM {
  class ZM_Object {
    public function __construct($id = null) {
      if ($id !== null and isset($GLOBALS['rows'][$id])) $this->{'Id'} = $id;
    }
    public function Id() { return isset($this->{'Id'}) ? $this->{'Id'} : null; }
  }
}

namespace {

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';

$failures = 0;
function check($n, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $n\n";
  else { $failures++; echo "FAIL - $n (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

function dbQuery($sql, $values = array()) {
  $GLOBALS['queries'][] = $sql;
  return $GLOBALS['dbResult'];
}
function dbFetchAll() { return array(); }
function validCardinal($input) { if (!is_scalar($input)) return ''; return preg_replace('/\D/', '', $input); }

// Snapshot.php: keep only the class body, the ZM_Object stub above stands in for the parent.
$src = file_get_contents($root.'/web/includes/Snapshot.php');
$src = preg_replace('/^<\?php/', '', $src);
$src = preg_replace('/^\s*require_once\([^)]*\);\s*$/m', '', $src);
$src = preg_replace('/\?>\s*$/', '', $src);
eval($src);

// deleteRequest() from ajax/snapshots.php
$ajax = file_get_contents($root.'/web/ajax/snapshots.php');
$start = strpos($ajax, 'function deleteRequest(');
$depth = 0;
for ($i = strpos($ajax, '{', $start); $i < strlen($ajax); $i++) {
  if ($ajax[$i] == '{') $depth++;
  else if ($ajax[$i] == '}' && --$depth == 0) break;
}
eval(substr($ajax, $start, $i - $start + 1));

$GLOBALS['rows'] = array(5 => true);

$GLOBALS['dbResult'] = true;
$snap = new ZM\Snapshot(5);
check('delete() reports success when both queries work', (bool)$snap->delete(), true);
$GLOBALS['dbResult'] = null;
check('delete() reports failure when the queries fail', (bool)$snap->delete(), false);

$GLOBALS['dbResult'] = null;
$msg = json_encode(deleteRequest(5));
check('a failed delete is reported to the client', strpos($msg, 'could not be deleted') !== false, true);
check('a failed delete is not reported as deleted', strpos($msg, 'Snapshot deleted') === false, true);
$msg = json_encode(deleteRequest(9));
check('a missing snapshot is reported as not found', strpos($msg, 'not found') !== false, true);

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
}
