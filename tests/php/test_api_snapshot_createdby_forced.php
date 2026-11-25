<?php
// The API must record the authenticated user and the time for a new snapshot whatever the request
// body says, and an edit must not change them.
// Run as: php tests/php/test_api_snapshot_createdby_forced.php [root]

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

$source = file_get_contents((isset($argv[1]) ? rtrim($argv[1], '/') : __DIR__.'/../..').'/web/api/app/Model/Snapshot.php');
$start = strpos($source, 'public function beforeSave(');
if ($start === false) {
  echo "FAIL - Snapshot::beforeSave() not found\n";
  exit(1);
}
$open = strpos($source, '{', $start);
$depth = 0;
for ($i = $open; $i < strlen($source); $i++) {
  if ($source[$i] == '{') $depth++;
  else if ($source[$i] == '}' && --$depth == 0) break;
}
$method = substr($source, $start, $i - $start + 1);
eval('class FakeSnapshot { public $alias = "Snapshot"; public $id = false; public $data = array(); '.$method.' }');

class FakeUser { function Id() { return 5; } }

function run($id, $data, $u) {
  global $user;
  $user = $u;
  $s = new FakeSnapshot();
  $s->id = $id;
  $s->data = $data;
  $r = $s->beforeSave();
  return array($r, $s->data);
}

$old = '2000-01-01 00:00:00';
list($r, $d) = run(false, array('Snapshot' => array('CreatedBy' => 99, 'CreatedOn' => $old)), new FakeUser());
check('a client CreatedBy is replaced by the user', $d['Snapshot']['CreatedBy'], 5);
check('a client CreatedOn is replaced', $d['Snapshot']['CreatedOn'] !== $old, true);
check('beforeSave lets a new snapshot save', $r, true);
list($r, $d) = run(false, array('Snapshot' => array('CreatedBy' => 99)), null);
check('without a user a client CreatedBy is dropped', isset($d['Snapshot']['CreatedBy']), false);
list($r, $d) = run(3, array('Snapshot' => array('CreatedBy' => 99, 'CreatedOn' => $old)), new FakeUser());
check('an edit cannot change CreatedBy', isset($d['Snapshot']['CreatedBy']), false);
check('an edit cannot change CreatedOn', isset($d['Snapshot']['CreatedOn']), false);
check('beforeSave lets an edit save', $r, true);

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
