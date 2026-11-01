<?php
// ZM\Event::canView() and canEdit() must refuse an Event with no Id.
//
// An Event built from a lookup that came back empty (new ZM\Event(null),
// new ZM\Event(array()), an id that does not exist) has no MonitorId, so
// Event::Monitor() returns an empty Monitor. Monitor::canView() of an empty
// Monitor falls back to the user's global Monitors permission, so the
// per-monitor restriction did not apply and such an Event passed both checks.
// Any caller that trusted canView() without checking Id() first failed open
// (the API's events/view did, see test_api_event_view_acl.php).
//
// Run as: php tests/php/test_event_can_view_no_id.php
//
// The real ZM\Event and ZM\ZM_Object are used; their require-only
// dependencies are stubbed and ZM\Monitor is a stub that behaves as the real
// one does for a user denied monitor 2: an empty Monitor (no Id) passes, as
// visibleMonitor(null) does.

namespace ZM;

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

$stubdir = sys_get_temp_dir().'/zm_event_canview_stubs_'.getmypid();
@mkdir($stubdir, 0700, true);
foreach (array('database.php', 'Storage.php', 'functions.php', 'Event_Tag.php', 'Tag.php') as $stub) {
  file_put_contents($stubdir.'/'.$stub, "<?php\n");
}
set_include_path($stubdir.PATH_SEPARATOR.__DIR__.'/../../web/includes'.PATH_SEPARATOR.get_include_path());

function Warning($s) {}
function Error($s) {}
function Debug($s) {}
function Info($s) {}
function dbFetchOne($sql, $col = null, $values = null) {
  // No event rows in this test: every lookup by id is a miss, and no event is
  // in a snapshot.
  return false;
}

class Monitor {
  public $Id = null;
  public static function find_one($params) {
    if (!in_array($params['Id'], array(1, 2))) return null;
    $m = new Monitor();
    $m->Id = $params['Id'];
    return $m;
  }
  public function canView($u = null) {
    // Real behaviour: per-monitor row for 2 is None; no row for null, so the
    // global Monitors permission decides.
    if ($this->Id == 2) return false;
    return $u->Monitors() != 'None';
  }
}

class TestUser {
  private $perms;
  public function __construct($perms) { $this->perms = $perms; }
  public function Events() { return $this->perms['Events']; }
  public function Snapshots() { return $this->perms['Snapshots']; }
  public function Monitors() { return $this->perms['Monitors']; }
  // Same answers as User::canView()/canEdit() for a user without a role, for
  // an Event::canView() that asks the user rather than reading the field.
  public function canView($area) { return $this->perms[$area] != 'None'; }
  public function canEdit($area) { return in_array($this->perms[$area], array('Edit', 'Create')); }
}

require_once __DIR__.'/../../web/includes/Event.php';

$editor = new TestUser(array('Events' => 'Edit', 'Snapshots' => 'None', 'Monitors' => 'View'));
$viewer = new TestUser(array('Events' => 'View', 'Snapshots' => 'None', 'Monitors' => 'View'));

$allowed = new Event(array('Id' => 1, 'MonitorId' => 1));
$hidden = new Event(array('Id' => 2, 'MonitorId' => 2));
check('event on a viewable monitor: canView', $allowed->canView($viewer), true);
check('event on a viewable monitor: canEdit for Events=Edit', $allowed->canEdit($editor), true);
check('event on a viewable monitor: no canEdit for Events=View', $allowed->canEdit($viewer), false);
check('event on a denied monitor: no canView', $hidden->canView($viewer), false);
check('event on a denied monitor: no canEdit', $hidden->canEdit($editor), false);

$empties = array(
  'new Event()' => new Event(),
  'new Event(array())' => new Event(array()),
  'new Event(<missing id>)' => new Event(999),
  'new Event(row without Id)' => new Event(array('Name' => 'x')),
);
foreach ($empties as $label => $empty) {
  check("$label: no canView", $empty->canView($viewer), false);
  check("$label: no canEdit", $empty->canEdit($editor), false);
}

echo "\n$passes passed, $failures failed\n";
array_map('unlink', glob($stubdir.'/*'));
@rmdir($stubdir);
exit($failures ? 1 : 0);
