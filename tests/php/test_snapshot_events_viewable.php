<?php
// Snapshot::Events() should list only the events the current user can view and should
// renumber the surviving keys. The DAO used to hand back every event found for the
// snapshot, keys included, whether or not the user was allowed to see it.
//
// Run as: php tests/php/test_snapshot_events_viewable.php [root]   (root defaults to the repo)

namespace ZM {
  class ZM_Object {
    public function __construct($id = null) {
      $this->{'Id'} = $id;
    }
    public function Id() { return $this->{'Id'}; }
  }

  class Event {
    public $id;
    public $viewable;

    public function __construct($id, $viewable) {
      $this->id = $id;
      $this->viewable = $viewable;
    }
    public function Id() { return $this->id; }
    public function canView() { return $this->viewable; }
    public static function find($params) {
      $out = array();
      foreach ($GLOBALS['all_events'] as $event) {
        if (in_array($event->Id(), $params['Id'])) $out[] = $event;
      }
      return $out;
    }
  }
}

namespace {

$root = $argv[1] ?? __DIR__.'/../..';

$failures = 0;
function check($n, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $n\n";
  else { $failures++; echo "NOT OK - $n (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

function dbFetchAll() {
  return $GLOBALS['event_ids'];
}

// Snapshot.php: keep only the class body, the ZM_Object stub above stands in for the parent.
$src = file_get_contents($root.'/web/includes/Snapshot.php');
$src = preg_replace('/^<\?php/', '', $src);
$src = preg_replace('/^\s*require_once\([^)]*\);\s*$/m', '', $src);
$src = preg_replace('/\?>\s*$/', '', $src);
eval($src);

$tests = 0;

$GLOBALS['all_events'] = array(new ZM\Event(1, true), new ZM\Event(2, false), new ZM\Event(3, true));
$GLOBALS['event_ids'] = array(1, 2, 3);

// (1) The event on a monitor the user cannot view is left out and keys are renumbered.
$snapshot = new ZM\Snapshot(7);
$events = $snapshot->Events();
$tests++;
check('unviewable event is left out', array_map(function($e) { return $e->Id(); }, $events), array(1, 3));
$tests++;
check('surviving keys are renumbered', array_keys($events), array(0, 1));

// (2) Every event viewable: all of them come back.
foreach ($GLOBALS['all_events'] as $event) $event->viewable = true;
$snapshot = new ZM\Snapshot(7);
$tests++;
check('all viewable events are returned', array_map(function($e) { return $e->Id(); }, $snapshot->Events()), array(1, 2, 3));

// (3) No event viewable: nothing comes back.
foreach ($GLOBALS['all_events'] as $event) $event->viewable = false;
$snapshot = new ZM\Snapshot(7);
$tests++;
check('no viewable events gives an empty list', $snapshot->Events(), array());

echo "$tests tests, $failures failures\n";
exit($failures === 0 ? 0 : 1);
}
