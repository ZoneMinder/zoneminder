<?php
// DELETE /api/events/<id> on an archived event answered 500 "The event could not be deleted."
// ZM\Event::delete() refuses archived events, so the API now answers 400 with the reason,
// as StorageController does for a storage area that is in use. Other events still delete.
//
// EventsController can't be loaded without CakePHP, so delete() is taken from the source
// and run against stubs.
// Run as: php tests/php/test_api_event_delete_archived.php

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

$source = file_get_contents(__DIR__.'/../../web/api/app/Controller/EventsController.php');
$start = strpos($source, 'public function delete(');
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
  static $deleteCalls = 0; static $archived = array();
  private $row;
  function __construct($row) { $this->row = $row; }
  function canEdit() { return true; }
  // Like the real one: an archived event is refused.
  function delete() { self::$deleteCalls++; return empty($this->row["Archived"]); }
}');

class FakeEventModel {
  public $id; public $recursive; public $primaryKey = 'Id';
  public $rows = array();
  function exists() { return isset($this->rows[$this->id]); }
  function find($type, $options) {
    $id = $this->id;
    return array('Event' => $this->rows[$id]);
  }
}
class FakeRequest { function allowMethod() {} }
eval('class FakeController { public $Event; public $request; public $out;
  function set($a) { $this->out = $a; }
  '.$method.' }');

$c = new FakeController();
$c->Event = new FakeEventModel();
$c->Event->rows = array(
  1 => array('Id' => 1, 'Archived' => 0),
  2 => array('Id' => 2, 'Archived' => 1),
);
$c->request = new FakeRequest();
$user = null;

$c->delete(1);
check('an unarchived event is deleted', $c->out['message'], 'Deleted');

ZM\Event::$deleteCalls = 0;
try {
  $c->delete(2);
  check('an archived event is refused', 'answered', 'BadRequestException');
} catch (BadRequestException $e) {
  check('an archived event is refused with 400', 'BadRequestException', 'BadRequestException');
  check('the message says why', strpos($e->getMessage(), 'archived') !== false, true);
} catch (InternalErrorException $e) {
  check('an archived event is refused with 400', 'InternalErrorException', 'BadRequestException');
}

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
?>
