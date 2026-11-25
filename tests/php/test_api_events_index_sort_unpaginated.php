<?php
// GET /api/events.json?sort=StartDateTime&direction=desc without limit or page ignored the sort:
// only Paginator::paginate() reads sort and direction, and the plain find() got no order at all.
// index() is taken from the source and run against stubs.
// Run as: php tests/php/test_api_events_index_sort_unpaginated.php [repo root]

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

$source = file_get_contents($root.'/web/api/app/Controller/EventsController.php');
$start = strpos($source, 'public function index(');
$open = strpos($source, '{', $start);
$depth = 0;
for ($i = $open; $i < strlen($source); $i++) {
  if ($source[$i] == '{') $depth++;
  else if ($source[$i] == '}' && --$depth == 0) break;
}
$method = substr($source, $start, $i - $start + 1);
$method = preg_replace('/require_once [^;]*;/', '', $method, 1);
// index() calls these helpers, so take them from the controller too when it has them.
foreach (array('renameLegacyTimeParams', 'datetimeConditions') as $helper) {
  $hstart = strpos($source, 'private function '.$helper.'(');
  if ($hstart === false) continue;
  $hopen = strpos($source, '{', $hstart);
  $depth = 0;
  for ($j = $hopen; $j < strlen($source); $j++) {
    if ($source[$j] == '{') $depth++;
    else if ($source[$j] == '}' && --$depth == 0) break;
  }
  $method .= "\n".substr($source, $hstart, $j - $hstart + 1);
}

class BadRequestException extends Exception {}
eval('namespace ZM; class Event {
  function __construct($r) {} function MaxScoreFrameId() { return 1; } function Path() { return "/x"; } }');

class FakeFilter {
  function qualifyFields($p, $f, $u = array()) { return $p; }
  function buildFilter($p, $v = null) { return array(); }
}
class FakeComponents { function load($n) { return new FakeFilter(); } }
class FakePaginator { public $settings; public $used = false;
  function paginate($m) { $this->used = true; return array(); } }
class FakeEventModel { public $recursive; public $found;
  function schema() { return array('Id'=>1, 'StartDateTime'=>1, 'Name'=>1, 'MonitorId'=>1); }
  function find($t, $o) { $this->found = $o; return array(); } }
class FakeRequest { public $params = array('named' => array()); public $q = array();
  function query($k) { return isset($this->q[$k]) ? $this->q[$k] : null; } }
eval('class FakeController { public $Event; public $request; public $Components; public $Paginator;
  function viewableMonitorCondition($f) { return array(); }
  function filterColumns() { return array(); }
  function set($a) {}
  '.$method.' }');

function run($q) {
  $c = new FakeController();
  $c->Event = new FakeEventModel();
  $c->Paginator = new FakePaginator();
  $c->Components = new FakeComponents();
  $c->request = new FakeRequest();
  $c->request->q = $q;
  $c->index();
  return $c;
}
$user = null;

$c = run(array());
check('no sort: default Id asc', $c->Event->found['order'], array('Event.Id' => 'asc'));
$c = run(array('sort' => 'StartDateTime', 'direction' => 'desc'));
check('sort and direction apply without limit/page', $c->Event->found['order'], array('Event.StartDateTime' => 'desc'));
$c = run(array('sort' => 'StartDateTime'));
check('direction defaults to asc', $c->Event->found['order'], array('Event.StartDateTime' => 'asc'));
$c = run(array('sort' => 'Bogus; DROP', 'direction' => 'desc'));
check('unknown sort field is not put in the order', isset($c->Event->found['order']), false);
$c = run(array('sort' => 'Name', 'direction' => 'sideways'));
check('bad direction falls back to asc', $c->Event->found['order'], array('Event.Name' => 'asc'));

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
