<?php
// POST /api/events/archiveBulk archives or unarchives many events in one call.
// The single-event archive() toggles each event's Archived flag, and the bulk
// form does the same per event: ids the user may not act on (or that no longer
// exist) are skipped and reported instead of aborting the whole request.
//
// EventsController can't be loaded without CakePHP, so archive() and
// archiveBulk() are taken from the source and run against stubs.
// Run as: php tests/php/test_api_events_bulk_archive.php [root]

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

function controllerMethod($source, $name) {
  $start = strpos($source, 'public function '.$name.'(');
  if ($start === false) return false;
  $open = strpos($source, '{', $start);
  $depth = 0;
  for ($i = $open; $i < strlen($source); $i++) {
    if ($source[$i] == '{') $depth++;
    else if ($source[$i] == '}' && --$depth == 0) return substr($source, $start, $i - $start + 1);
  }
  return false;
}

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$source = file_get_contents($root.'/web/api/app/Controller/EventsController.php');
if ($source === false) {
  echo "FAIL - cannot read controller source\n";
  exit(1);
}
$single = controllerMethod($source, 'archive');
$bulk = controllerMethod($source, 'archiveBulk');
if ($single === false) {
  echo "FAIL - archive() not found in controller source\n";
  exit(1);
}
if ($bulk === false) {
  echo "FAIL - archiveBulk() not found in controller source\n";
  echo "\n1 failed\n";
  exit(1);
}

class NotFoundException extends Exception {}
class UnauthorizedException extends Exception {}
class BadRequestException extends Exception {}
class InternalErrorException extends Exception {}
function __($s) { return $s; }

eval('namespace ZM; class Event {
  public $row;
  function __construct($row) { $this->row = is_array($row) ? $row : array(); }
  function canView() {
    if (!isset($this->row["MonitorId"])) return false;
    return in_array($this->row["MonitorId"], $GLOBALS["viewable"], true);
  }
  function canEdit() {
    if (!isset($this->row["MonitorId"])) return false;
    return in_array($this->row["MonitorId"], $GLOBALS["editable"], true);
  }
}');

class FakeEventModel {
  public $id; public $recursive; public $primaryKey = 'Id';
  public $rows = array();
  public $saveFieldResult = true;
  public $saved = array();
  function exists($id) { return isset($this->rows[$id]); }
  function find($type, $options) {
    $id = $options['conditions']['Event.Id'];
    if (!isset($this->rows[$id])) return array();
    return array('Event' => $this->rows[$id]);
  }
  function saveField($field, $value) {
    $this->saved[] = array('id' => $this->id, 'field' => $field, 'value' => $value);
    return $this->saveFieldResult;
  }
}
class FakeRequest {
  public $data = array();
  function allowMethod() {}
}
eval('class FakeController { public $Event; public $request; public $out; public $setCalls = 0;
  function set($a) { $this->setCalls++; $this->out = $a; }
  '.$single.' '.$bulk.' }');

function newController($rows, $viewable, $editable, $saveFieldResult = true) {
  $GLOBALS['viewable'] = $viewable;
  $GLOBALS['editable'] = $editable;
  $c = new FakeController();
  $c->Event = new FakeEventModel();
  $c->Event->rows = $rows;
  $c->Event->saveFieldResult = $saveFieldResult;
  $c->request = new FakeRequest();
  return $c;
}

// Bulk archive (archived=1): every listed event is set to 1, whatever it was,
// so 4 (already archived) stays archived rather than being flipped. 3 is on a
// monitor the user may not view, so it is refused; 99 does not exist.
$rows = array(
  1 => array('Id' => 1, 'Archived' => 0, 'MonitorId' => 1),
  2 => array('Id' => 2, 'Archived' => 0, 'MonitorId' => 1),
  3 => array('Id' => 3, 'Archived' => 0, 'MonitorId' => 2),
  4 => array('Id' => 4, 'Archived' => 1, 'MonitorId' => 1),
);
$c = newController($rows, array(1), array(1));
$c->request->data = array('ids' => array(1, 2, 3, 4, 99), 'archived' => '1');
try {
  $c->archiveBulk();
  check('bulk reports the ids it changed', isset($c->out['changed']) ? $c->out['changed'] : null, array(1, 2, 4));
  check('bulk reports the ids it refused', isset($c->out['refused']) ? $c->out['refused'] : null, array(3, 99));
  check('bulk serializes the changed and refused ids', isset($c->out['_serialize']) ? $c->out['_serialize'] : null, array('changed', 'refused'));
  check('bulk archive sets every event to archived, not a toggle', $c->Event->saved, array(
    array('id' => 1, 'field' => 'Archived', 'value' => 1),
    array('id' => 2, 'field' => 'Archived', 'value' => 1),
    array('id' => 4, 'field' => 'Archived', 'value' => 1),
  ));
} catch (Exception $e) {
  check('bulk does not throw', get_class($e), 'no exception');
}

// Bulk unarchive (archived=0) needs edit: 4 is editable, 3 is viewable only.
$rows = array(
  3 => array('Id' => 3, 'Archived' => 1, 'MonitorId' => 2),
  4 => array('Id' => 4, 'Archived' => 1, 'MonitorId' => 1),
);
$c = newController($rows, array(1, 2), array(1));
$c->request->data = array('ids' => array(3, 4), 'archived' => '0');
try {
  $c->archiveBulk();
  check('bulk unarchive changes the editable event', isset($c->out['changed']) ? $c->out['changed'] : null, array(4));
  check('bulk unarchive refuses the view-only event', isset($c->out['refused']) ? $c->out['refused'] : null, array(3));
  check('bulk unarchive sets archived to 0', $c->Event->saved, array(array('id' => 4, 'field' => 'Archived', 'value' => 0)));
} catch (Exception $e) {
  check('bulk unarchive does not throw', get_class($e), 'no exception');
}

// archived is required and must be 0 or 1.
foreach (array(null, '2', array(1)) as $bad) {
  $c = newController(array(1 => array('Id' => 1, 'Archived' => 0, 'MonitorId' => 1)), array(1), array(1));
  $c->request->data = array('ids' => array(1));
  if ($bad !== null) $c->request->data['archived'] = $bad;
  $thrown = '';
  try { $c->archiveBulk(); } catch (Exception $e) { $thrown = get_class($e); }
  check('bulk refuses archived='.var_export($bad, true), $thrown, 'BadRequestException');
  check('bulk writes nothing for archived='.var_export($bad, true), $c->Event->saved, array());
}

// Bulk: a failed save is refused and does not stop the remaining events.
$rows = array(
  1 => array('Id' => 1, 'Archived' => 0, 'MonitorId' => 1),
  2 => array('Id' => 2, 'Archived' => 0, 'MonitorId' => 1),
);
$c = newController($rows, array(1), array(1), false);
$c->request->data = array('ids' => array(1, 2), 'archived' => '1');
try {
  $c->archiveBulk();
  check('bulk refuses an event whose save failed', isset($c->out['refused']) ? $c->out['refused'] : null, array(1, 2));
  check('bulk reports no changes when every save failed', isset($c->out['changed']) ? $c->out['changed'] : null, array());
} catch (Exception $e) {
  check('bulk save failure does not throw', get_class($e), 'no exception');
}

// Single-id archive() is unchanged: it still toggles and answers with archived.
$c = newController(array(1 => array('Id' => 1, 'Archived' => 0, 'MonitorId' => 1)), array(1), array(1));
try {
  $c->archive(1);
  check('single archive still answers archived=1', isset($c->out['archived']) ? $c->out['archived'] : null, 1);
  check('single archive still serializes archived', isset($c->out['_serialize']) ? $c->out['_serialize'] : null, array('archived'));
  check('single archive still saves the toggle', $c->Event->saved, array(
    array('id' => 1, 'field' => 'Archived', 'value' => 1),
  ));
} catch (Exception $e) {
  check('single archive does not throw', get_class($e), 'no exception');
}

$c = newController(array(1 => array('Id' => 1, 'Archived' => 1, 'MonitorId' => 1)), array(1), array(1));
try {
  $c->archive(1);
  check('single archive still unarchives an archived event', isset($c->out['archived']) ? $c->out['archived'] : null, 0);
  check('single unarchive still saves the toggle', $c->Event->saved, array(
    array('id' => 1, 'field' => 'Archived', 'value' => 0),
  ));
} catch (Exception $e) {
  check('single unarchive does not throw', get_class($e), 'no exception');
}

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
?>
