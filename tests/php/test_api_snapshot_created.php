<?php
// A POST to /api/snapshots left Snapshots.CreatedBy and CreatedOn NULL. The web action
// (includes/actions/snapshot.php) stores the logged in user and the time. Snapshot::beforeSave()
// does the same for a new snapshot, and leaves edits alone.
//
// Snapshot can't be loaded without CakePHP, so beforeSave() is taken from the source and run
// against stubs.
// Run as: php tests/php/test_api_snapshot_created.php

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

$source = file_get_contents(__DIR__.'/../../web/api/app/Model/Snapshot.php');
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

list($r, $d) = run(false, array('Snapshot' => array('Name' => 'a')), new FakeUser());
check('new snapshot records the user', $d['Snapshot']['CreatedBy'], 5);
check('new snapshot records the time', (bool)preg_match('/^\d{4}-\d\d-\d\d \d\d:\d\d:\d\d$/', (string)$d['Snapshot']['CreatedOn']), true);
check('beforeSave lets the save go on', $r, true);
list($r, $d) = run(false, array('Event' => array(1, 2)), new FakeUser());
check('a snapshot with only events still gets the user', $d['Snapshot']['CreatedBy'], 5);
list($r, $d) = run(false, array('Snapshot' => array('Name' => 'a')), null);
check('without authentication CreatedBy stays NULL', $d['Snapshot']['CreatedBy'] ?? null, null);
check('without authentication CreatedOn is still set', isset($d['Snapshot']['CreatedOn']), true);
list($r, $d) = run(3, array('Snapshot' => array('Name' => 'b')), new FakeUser());
check('an edit leaves CreatedBy alone', isset($d['Snapshot']['CreatedBy']), false);
check('an edit leaves CreatedOn alone', isset($d['Snapshot']['CreatedOn']), false);

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
?>
