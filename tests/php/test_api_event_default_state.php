<?php
// A POST to /api/events without StateId failed with "Field 'StateId' doesn't have a default
// value" because Events.StateId is NOT NULL with no default. Event::beforeSave() fills it
// in for a new event with the active run state, as zmc does, and leaves edits and an
// explicit StateId alone.
//
// Event can't be loaded without CakePHP, so beforeSave() is taken from the source and run
// against stubs.
// Run as: php tests/php/test_api_event_default_state.php

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

$source = file_get_contents(__DIR__.'/../../web/api/app/Model/Event.php');
$start = strpos($source, 'public function beforeSave(');
if ($start === false) {
  echo "FAIL - Event::beforeSave() not found\n";
  exit(1);
}
$open = strpos($source, '{', $start);
$depth = 0;
for ($i = $open; $i < strlen($source); $i++) {
  if ($source[$i] == '{') $depth++;
  else if ($source[$i] == '}' && --$depth == 0) break;
}
$method = substr($source, $start, $i - $start + 1);

class FakeState {
  public $active = null;
  function find($type, $options) {
    return $this->active === null ? array() : array('State' => array('Id' => $this->active));
  }
}
class ClassRegistry {
  public static $state;
  static function init($name) { return self::$state; }
}
eval('class FakeEvent { public $alias = "Event"; public $id = false; public $data = array(); '.$method.' }');

ClassRegistry::$state = new FakeState();

function run($id, $data, $active) {
  ClassRegistry::$state->active = $active;
  $e = new FakeEvent();
  $e->id = $id;
  $e->data = $data;
  $r = $e->beforeSave();
  return array($r, $e->data);
}

list($r, $d) = run(false, array('Event' => array('MonitorId' => 1)), 3);
check('new event takes the active state', $d['Event']['StateId'], 3);
check('beforeSave lets the save go on', $r, true);
list($r, $d) = run(false, array('Event' => array('MonitorId' => 1)), null);
check('no active state falls back to 0', $d['Event']['StateId'], 0);
list($r, $d) = run(false, array('Event' => array('MonitorId' => 1, 'StateId' => 7)), 3);
check('explicit StateId is kept', $d['Event']['StateId'], 7);
list($r, $d) = run(false, array('Event' => array('MonitorId' => 1, 'StateId' => 0)), 3);
check('explicit StateId 0 is kept', $d['Event']['StateId'], 0);
list($r, $d) = run(12, array('Event' => array('Name' => 'x')), 3);
check('an edit does not add StateId', isset($d['Event']['StateId']), false);

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
?>
