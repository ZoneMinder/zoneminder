<?php
// Security regression test: Groups, Snapshots and Tags API writes must check the links the
// save will write, and an edit must keep the links the user may not change.
//
// AppController::requestAssociatedIds() took the ids from $alias[MonitorIds|EventIds] when
// present, while Model::save() links what the request gives as the HABTM entry (Monitor,
// Event) and ignores those fields on edit. So {"Group":{"MonitorIds":"2,3"},"Monitor":[]}
// passed the membership check of group 2,3 as "nothing moved" and emptied the group, and
// {"Snapshot":{"EventIds":"1"},"Event":[2]} added an event the user may not view. An edit
// also replaces all links, but Snapshots and Tags checked only the new list, so {"Event":[]}
// unlinked events of monitors the user is denied. Those links are now kept.
//
// AppController can't be loaded without CakePHP, so the methods are taken from the source and
// run in a stand-in with the same $this->request->data.
//
// Run as: php tests/php/test_api_associated_ids.php

$failures = 0;
$passes = 0;

function check($name, $got, $want) {
  global $failures, $passes;
  if ($got === $want) {
    $passes++;
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n";
  }
}

$controllers = __DIR__.'/../../web/api/app/Controller';
$source = file_get_contents($controllers.'/AppController.php');
$methods = '';
foreach (array('requestAssociatedIds', 'eventLinksToSave') as $fn) {
  if (!preg_match('/^  protected function '.$fn.'\(.*?^  }\n/ms', $source, $m)) {
    echo "FAIL - could not find $fn in AppController.php\n";
    exit(1);
  }
  $methods .= str_replace('protected function', 'public function', $m[0]);
}

// Stand-ins for what the methods call.
class UnauthorizedException extends Exception {}
function __($s) { return $s; }
$links = array();
function dbFetchAll($sql, $col, $args) {
  global $links;
  return isset($links[$args[0]]) ? $links[$args[0]] : array();
}
$viewable = array();
eval('namespace ZM; class Event {
  private $id;
  public function __construct($id) { $this->id = in_array($id, array(1, 2, 3)) ? $id : null; }
  public function Id() { return $this->id; }
  public function canView() { global $viewable; return in_array($this->id, $viewable); }
}');

eval('class AssocHarness { public $request; public $Group; public $Snapshot; public $checked = array();
  public function requireEventsView($ids) {
    global $viewable;
    foreach ($ids as $id) if (!in_array($id, $viewable)) throw new UnauthorizedException("view");
  }
  '.$methods.' }');

function harness(array $data) {
  $h = new AssocHarness();
  $h->request = (object)array('data' => $data);
  $h->Group = (object)array('hasAndBelongsToMany' => array('Monitor' => array('associationForeignKey' => 'MonitorId')));
  $h->Snapshot = (object)array('hasAndBelongsToMany' => array('Event' => array('associationForeignKey' => 'EventId')));
  return $h;
}

function ids(array $data, $alias = 'Group', $assoc = 'Monitor') {
  $h = harness($data);
  $ids = $h->requestAssociatedIds($alias, $assoc);
  return array($ids, $h->request->data);
}

// What the check sees is what save() will link.
check('a plain list', ids(array('Monitor' => array('2', 3)))[0], array(2, 3));
check('a plain list is saved as given', ids(array('Monitor' => array('2', 3)))[1], array('Monitor' => array('Monitor' => array(2, 3))));
check('Monitor[Monitor] form', ids(array('Monitor' => array('Monitor' => array(4))))[0], array(4));
check('Monitor[Monitor] as a single value', ids(array('Monitor' => array('Monitor' => '5')))[0], array(5));
check('an empty list removes every link', ids(array('Monitor' => array()))[0], array());
check('MonitorIds next to Monitor: Monitor is what save() links',
  ids(array('Group' => array('MonitorIds' => '2,3'), 'Monitor' => array()))[0], array());
check('MonitorIds alone links nothing on edit (save() ignores it)',
  ids(array('Group' => array('MonitorIds' => '2,3')))[0], null);
check('null leaves the links alone', ids(array('Monitor' => null))[0], null);
check('a string leaves the links alone and is dropped',
  ids(array('Group' => array('Name' => 'g'), 'Monitor' => 'x'))[1], array('Group' => array('Name' => 'g')));
check('a join record links only its MonitorId',
  ids(array('Monitor' => array(array('Id' => 100000, 'MonitorId' => 4, 'GroupId' => 9))))[1],
  array('Monitor' => array('Monitor' => array(4))));
check('a record without MonitorId links nothing', ids(array('Monitor' => array(array('Id' => 7))))[0], array());
check('events for a snapshot', ids(array('Snapshot' => array('EventIds' => '1'), 'Event' => array(2)), 'Snapshot', 'Event')[0], array(2));

// An edit may link only events the user may view, and keeps the links to events they may not.
function linksSaved($old, $new) {
  global $links;
  $links = array(9 => $old);
  try {
    return harness(array())->eventLinksToSave('Snapshots_Events', 'SnapshotId', 9, $new);
  } catch (UnauthorizedException $e) {
    return 'refused';
  }
}
$viewable = array(1, 3);
check('emptying keeps the event the user may not view', linksSaved(array(1, 2), array()), array(2));
check('keeping only the visible ones keeps the hidden one too', linksSaved(array(1, 2), array(1)), array(1, 2));
check('changing the visible ones keeps the hidden one', linksSaved(array(1, 2), array(3)), array(3, 2));
check('linking an event the user may not view is refused', linksSaved(array(1), array(1, 2)), 'refused');
check('naming a hidden linked event is refused', linksSaved(array(1, 2), array(1, 2)), 'refused');
check('unlinking a visible event', linksSaved(array(1, 3), array(1)), array(1));
check('a link to a deleted event is dropped', linksSaved(array(1, 99), array(1)), array(1));
$viewable = array(1, 2, 3);
check('an unrestricted user may replace the list', linksSaved(array(1, 2), array()), array());

// The controllers use them that way.
foreach (array('Groups' => array('Group', 'MonitorIds', 'Monitor'), 'Snapshots' => array('Snapshot', 'EventIds', 'Event'), 'Tags' => array('Tag', 'EventIds', 'Event')) as $name => $a) {
  list($alias, $field, $assoc) = $a;
  $c = file_get_contents("$controllers/{$name}Controller.php");
  $copy = strpos($c, "\$this->request->data['$assoc'] = explode(',', \$this->request->data['$alias']['$field']);");
  $first = strpos($c, "requestAssociatedIds('$alias', '$assoc')");
  check("$name add copies $field to $assoc before the check", $copy !== false and $first !== false and $copy < $first, true);
  if ($name != 'Groups') {
    check("$name edit keeps the links the user may not change", strpos($c, "\$this->request->data['Event'] = array('Event' => \$this->eventLinksToSave(") !== false, true);
  }
}

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
