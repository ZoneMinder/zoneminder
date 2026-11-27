<?php
// POST /api/events/<id>/archive ignored the result of Event->saveField('Archived', ...)
// and always answered {"archived": <val>}, even when the save failed. The API now answers
// 500 InternalErrorException when saveField() returns false.
//
// EventsController can't be loaded without CakePHP, so archive() is taken from the source
// and run against stubs.
// Run as: php tests/php/test_api_events_archive_save.php [root]

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$source = file_get_contents($root.'/web/api/app/Controller/EventsController.php');
if ($source === false) {
  echo "FAIL - cannot read controller source\n";
  exit(1);
}
$start = strpos($source, 'public function archive(');
if ($start === false) {
  echo "FAIL - archive() not found in controller source\n";
  exit(1);
}
$open = strpos($source, '{', $start);
$depth = 0;
for ($i = $open; $i < strlen($source); $i++) {
  if ($source[$i] == '{') $depth++;
  else if ($source[$i] == '}' && --$depth == 0) break;
}
$method = substr($source, $start, $i - $start + 1);

class NotFoundException extends Exception {}
class UnauthorizedException extends Exception {}
class BadRequestException extends Exception {}
class InternalErrorException extends Exception {}
function __($s) { return $s; }

eval('namespace ZM; class Event {
  function __construct($row) {}
  function canView() { return true; }
  function canEdit() { return true; }
}');

class FakeEventModel {
  public $id; public $recursive; public $primaryKey = 'Id';
  public $rows = array();
  public $saveFieldResult = true;
  function exists($id) { return isset($this->rows[$id]); }
  function find($type, $options) {
    $id = $this->optionsId($options);
    return array('Event' => $this->rows[$id]);
  }
  private function optionsId($options) {
    return $options['conditions']['Event.Id'];
  }
  function saveField($field, $value) { return $this->saveFieldResult; }
}
class FakeRequest { function allowMethod() {} }
eval('class FakeController { public $Event; public $request; public $out; public $setCalls = 0;
  function set($a) { $this->setCalls++; $this->out = $a; }
  '.$method.' }');

function newController($saveFieldResult) {
  $c = new FakeController();
  $c->Event = new FakeEventModel();
  $c->Event->rows = array(
    1 => array('Id' => 1, 'Archived' => 0, 'MonitorId' => 1),
  );
  $c->Event->saveFieldResult = $saveFieldResult;
  $c->request = new FakeRequest();
  return $c;
}

// saveField() succeeds: the response reports the new value, no exception.
$c = newController(true);
try {
  $c->archive(1);
  check('successful save sets archived', isset($c->out['archived']) ? $c->out['archived'] : null, 1);
  check('successful save calls set()', $c->setCalls, 1);
} catch (Exception $e) {
  check('successful save sets archived', get_class($e), 1);
}

// saveField() fails: 500 InternalErrorException, no success payload.
$c = newController(false);
try {
  $c->archive(1);
  check('failed save throws', 'answered', 'InternalErrorException');
  check('failed save does not call set()', $c->setCalls, 0);
} catch (InternalErrorException $e) {
  check('failed save throws', 'InternalErrorException', 'InternalErrorException');
  check('failed save does not call set()', $c->setCalls, 0);
} catch (Exception $e) {
  check('failed save throws', get_class($e), 'InternalErrorException');
  check('failed save does not call set()', $c->setCalls, 0);
}

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
?>
