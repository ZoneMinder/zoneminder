<?php
// SnapshotsController::edit() only saved on POST/PUT, but any other method fell through and
// answered 200 with an empty message and the snapshot, so GET /api/snapshots/edit/5.json looked
// like a successful edit that saved nothing. It now refuses other methods with a 405, as the
// Groups/Tags/Users edit() actions do.
//
// php tests/php/test_api_snapshot_edit_method.php [root]   (root defaults to the repo)

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$src = file_get_contents($root.'/web/api/app/Controller/SnapshotsController.php');
$start = strpos($src, 'public function edit(');
$depth = 0;
for ($i = strpos($src, '{', $start); $i < strlen($src); $i++) {
  if ($src[$i] == '{') $depth++;
  else if ($src[$i] == '}' && --$depth == 0) break;
}
$method = substr($src, $start, $i - $start + 1);

class NotFoundException extends Exception {}
class UnauthorizedException extends Exception {}
class MethodNotAllowedException extends Exception {}
function __($s) { return $s; }

class FakeRequest {
  public $data = array('Snapshot' => array('Name' => 'x'));
  private $method;
  function __construct($m) { $this->method = $m; }
  function is($t) { return in_array(strtolower($this->method), array_map('strtolower', (array)$t)); }
  function allowMethod() {
    foreach (func_get_args() as $m) if ($this->is($m)) return true;
    throw new MethodNotAllowedException();
  }
}
class FakeSnapshot {
  public $saved = 0, $validationErrors = array();
  function exists($id) { return true; }
  function save($d) { $this->saved++; return true; }
  function findById($id) { return array('Snapshot' => array('Id' => $id)); }
}
eval('class SnapshotsHarness { public $request, $Snapshot, $vars = array();
  function set($v) { $this->vars = $v; }
  function pinRequestId($m, $id) {}
  function requestAssociatedIds($a, $b) { return null; }
  function eventLinksToSave() { return array(); }
  '.$method.' }');

$user = null;
$failures = 0;
function check($n, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $n\n";
  else { $failures++; echo "FAIL - $n (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}
function run($m) {
  $h = new SnapshotsHarness();
  $h->request = new FakeRequest($m);
  $h->Snapshot = new FakeSnapshot();
  try { $h->edit(5); $r = 'ok'; } catch (Exception $e) { $r = get_class($e); }
  return array($r, $h);
}

list($r, $h) = run('GET');
check('GET edit is refused with a 405', $r, 'MethodNotAllowedException');
check('GET edit answers no body', $h->vars, array());
list($r, $h) = run('PUT');
check('PUT edit saves', $h->Snapshot->saved, 1);
check('PUT edit answers Saved', $h->vars['message'] ?? null, 'Saved');

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
