<?php
// A user who cannot edit a monitor must not be able to probe its camera
// credentials: MonitorsController::index() leaves the credential columns out of
// the filterable fields and refuses to sort by them, and the Groups and Events
// controllers blank them in the Monitor rows they return.
// Run as: php tests/php/test_api_monitors_credential_queries.php [root]

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$ctl = $root.'/web/api/app/Controller';
foreach (array('Monitors', 'Groups', 'Events') as $c) {
  if (!is_file("$ctl/{$c}Controller.php")) { echo "FAIL - $c controller not found\n"; exit(1); }
}
$mon = file_get_contents("$ctl/MonitorsController.php");
$app = file_get_contents("$ctl/AppController.php");
$groups = file_get_contents("$ctl/GroupsController.php");
$events = file_get_contents("$ctl/EventsController.php");

function extractMethod($source, $name) {
  if (!preg_match('/(public|private|protected) function '.$name.'\(/', $source, $m, PREG_OFFSET_CAPTURE)) return '';
  $start = $m[0][1];
  $open = strpos($source, '{', $start);
  $depth = 0;
  for ($i = $open; $i < strlen($source); $i++) {
    if ($source[$i] == '{') $depth++;
    else if ($source[$i] == '}' && --$depth == 0) break;
  }
  return substr($source, $start, $i - $start + 1);
}

$empty = tempnam(sys_get_temp_dir(), 'zmcred');
$fix = function($m) use ($empty) {
  return str_replace('__DIR__ .\'/../../../includes/Monitor.php\'', var_export($empty, true), $m);
};

if (!class_exists('BadRequestException')) { class BadRequestException extends Exception {} }
if (!class_exists('UnauthorizedException')) { class UnauthorizedException extends Exception {} }
if (!function_exists('__')) { function __($s) { return $s; } }

$user = new stdClass();   // a logged in user
$GLOBALS['editable'] = array();
function visibleMonitor($id) { return true; }
function editableMonitor($id) { return in_array($id, $GLOBALS['editable']); }

eval('namespace ZM;
class Monitor {
  public $row;
  function __construct($row = null) { $this->row = $row; }
  function canEdit() { return editableMonitor($this->row["Id"]); }
  function canView() { return true; }
  function JanusEnabled() { return false; }
}');

class FakeFilter {
  public $fields;
  function qualifyFields($named, $fields, $bare = array()) { $this->fields = $fields; return array(); }
  function buildFilter($named) { return array(); }
}
class FakeComponents { public $f; function load($n) { return $this->f; } }
class FakeRequest {
  public $params = array('named' => array()); public $q = array(); public $query = array();
  function query($k) { return isset($this->q[$k]) ? $this->q[$k] : null; }
}
class FakeMonitorModel {
  public $recursive; public $hasOne = array(); public $belongsTo = array(); public $find_array;
  function schema() { return array('Id' => 1, 'Name' => 1, 'User' => 1, 'Pass' => 1, 'ONVIF_Username' => 1,
    'ONVIF_Password' => 1, 'Host' => 1, 'Path' => 1, 'SecondPath' => 1, 'ONVIF_URL' => 1, 'Deleted' => 1); }
  function find($type, $o = array()) {
    if ($type == 'list') return array(1 => 1, 2 => 2);
    $this->find_array = $o;
    return array();
  }
}
class FakeController {
  public $Monitor; public $Components; public $request; public $out;
  function set($a) { $this->out = $a; }
}
eval('class MonCtl extends FakeController { '.$fix(extractMethod($mon, 'index')).' '.extractMethod($mon, 'canQueryCredentials').' '
  .extractMethod($app, 'monitorCredentialColumns').' }');

function runIndex($named, $query, $editable) {
  $GLOBALS['editable'] = $editable;
  $c = new MonCtl();
  $c->Monitor = new FakeMonitorModel();
  $c->Components = new FakeComponents();
  $c->Components->f = new FakeFilter();
  $c->request = new FakeRequest();
  $c->request->params['named'] = $named;
  $c->request->q = $query;
  $c->request->query = $query;
  try {
    $c->index();
  } catch (BadRequestException $e) {
    return array('400', null);
  }
  return array('ok', $c);
}

$credential = array('User', 'Pass', 'ONVIF_Username', 'ONVIF_Password', 'Host', 'Path', 'SecondPath', 'ONVIF_URL');

// A viewer: can edit neither monitor.
list(, $c) = runIndex(array('Name' => 'x'), array(), array());
$f = $c->Components->f->fields['Monitor'];
check('viewer: Name stays filterable', in_array('Name', $f), true);
foreach ($credential as $col) check("viewer: $col is not filterable", in_array($col, $f), false);
list(, $c) = runIndex(array(), array('Pass' => 'x'), array());
check('viewer: Pass is not filterable from the query string', in_array('Pass', $c->Components->f->fields['Monitor']), false);

foreach ($credential as $col) {
  list($state) = runIndex(array(), array('sort' => $col), array());
  check("viewer: sort by $col is refused", $state, '400');
}
list($state, $c) = runIndex(array(), array('sort' => 'Name'), array());
check('viewer: sort by Name is allowed', $state, 'ok');

// Can edit one of the two monitors they can view: still a viewer for the other.
list(, $c) = runIndex(array('Name' => 'x'), array(), array(1));
check('partial editor: Pass is not filterable', in_array('Pass', $c->Components->f->fields['Monitor']), false);
list($state) = runIndex(array(), array('sort' => 'Pass'), array(1));
check('partial editor: sort by Pass is refused', $state, '400');

// An editor of every monitor they can view keeps both.
list(, $c) = runIndex(array('Name' => 'x'), array(), array(1, 2));
check('editor: Pass is filterable', in_array('Pass', $c->Components->f->fields['Monitor']), true);
list($state) = runIndex(array(), array('sort' => 'Pass', 'direction' => 'desc'), array(1, 2));
check('editor: sort by Pass is allowed', $state, 'ok');

// Groups: the Monitor rows of each group.
class FakeGroupModel { public $Monitor; }
class FakeGroupMonitor { function find($t, $o = array()) { return array(); } }
class FakeGroupsBase extends FakeController {
  public $Group;
  function viewableMonitorCondition($f) { return array(); }
}
eval('class GroupsCtl extends FakeGroupsBase { '.str_replace('private function', 'public function', extractMethod($groups, 'filterGroupMonitors')).' '
  .$fix(extractMethod($app, 'hideRowCredentials')).' '.extractMethod($app, 'hideCredentials').' '
  .extractMethod($app, 'monitorCredentialColumns').' }');
$row = array('Id' => 1, 'Name' => 'Porch', 'User' => 'admin', 'Pass' => 'hunter2', 'ONVIF_Username' => 'o',
  'ONVIF_Password' => 'p', 'Host' => 'u:p@cam', 'Path' => 'rtsp://a:b@cam/x', 'SecondPath' => '', 'ONVIF_URL' => '');
$g = new GroupsCtl();
$g->Group = new FakeGroupModel();
$g->Group->Monitor = new FakeGroupMonitor();
$GLOBALS['editable'] = array();
$out = $g->filterGroupMonitors(array(array('Group' => array('Id' => 3), 'Monitor' => array($row))));
$m = $out[0]['Monitor'][0];
check('groups: viewer gets no Pass', $m['Pass'], '');
check('groups: viewer gets no User', $m['User'], '');
check('groups: viewer gets no ONVIF password', $m['ONVIF_Password'], '');
check('groups: viewer gets no credentials in Host', $m['Host'], 'cam');
check('groups: viewer still gets the name', $m['Name'], 'Porch');
$GLOBALS['editable'] = array(1);
$out = $g->filterGroupMonitors(array(array('Group' => array('Id' => 3), 'Monitor' => array($row))));
check('groups: editor gets the row as stored', $out[0]['Monitor'][0], $row);

// Events: the Monitor row view() and index() return goes through the same helper.
check('events: view() blanks the Monitor row', preg_match('/\$event\[.Monitor.\]\s*=\s*\$this->hideRowCredentials\(/', extractMethod($events, 'view')), 1);
check('events: index() blanks each Monitor row', preg_match('/\$this->hideRowCredentials\(/', extractMethod($events, 'index')), 1);

unlink($empty);
exit($failures ? 1 : 0);
