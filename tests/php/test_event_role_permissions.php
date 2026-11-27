<?php
// Regression test: ZM\Event::canView()/canEdit() must count a permission granted through the
// user's role, as the global canView()/canEdit() helpers and ZM\User::canView()/canEdit() do.
//
// They compared the user's own Events/Snapshots fields, so a user whose own Events was None
// but whose role had Events=View or Edit could not view or edit any event, even on a monitor
// they may view. refs #5189
//
// The snapshot lookup in Event::canView() named a table that does not exist (Snapshot_Events)
// and passed the event id where dbFetchOne() takes a column name, so it never matched: a user
// allowed only through Snapshots could not view the events in a snapshot.
//
// Drives the real ZM\Event, ZM\User and ZM\User_Role. Monitor and dbFetchOne() are stubbed: only
// their answers (per monitor, per snapshot) matter here.
//
// Run as: php tests/php/test_event_role_permissions.php

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

$stubdir = sys_get_temp_dir().'/zm_event_role_permissions_stubs_'.getmypid();
@mkdir($stubdir, 0700, true);
foreach (array('database.php', 'functions.php', 'Storage.php', 'Event_Tag.php', 'Tag.php', 'Monitor.php',
               'Group.php', 'Group_Permission.php', 'Monitor_Permission.php', 'User_Preference.php') as $stub) {
  file_put_contents($stubdir.'/'.$stub, "<?php\n");
}
set_include_path($stubdir.PATH_SEPARATOR.__DIR__.'/../../web/includes'.PATH_SEPARATOR.get_include_path());

function Warning($s) { /* noop */ }
function Error($s)   { /* noop */ }
function Debug($s)   { /* noop */ }
function Info($s)    { /* noop */ }

// Monitor ids the current test may view; Event::canView()/canEdit() ask the event's monitor.
$visibleMonitorIds = array(1);
class Monitor {
  private $id;
  public function __construct($id = null) { $this->id = $id; }
  public static function find_one($params) { return new Monitor($params['Id']); }
  public function canView($u = null) {
    global $visibleMonitorIds;
    return in_array($this->id, $visibleMonitorIds, true);
  }
}

// Event ids held in a snapshot. Answers only the query Event::canView() should send: the
// Snapshots_Events table with the event id bound as a parameter.
$snapshotEventIds = array(7, 8);
function dbFetchOne($sql, $col=false, $params=NULL) {
  global $snapshotEventIds;
  if ($sql != 'SELECT * FROM Snapshots_Events WHERE EventId=?' or $col !== false or !is_array($params))
    return false;
  return in_array($params[0], $snapshotEventIds, true) ? array('SnapshotId' => 1, 'EventId' => $params[0]) : false;
}

require_once __DIR__.'/../../web/includes/User.php';
require_once __DIR__.'/../../web/includes/User_Role.php';
require_once __DIR__.'/../../web/includes/Event.php';

function make_user($id, array $perms, $role = null) {
  $u = new User(array_merge(array('Id' => $id, 'Username' => 'u'.$id), $perms));
  if ($role) $u->Role($role);
  return $u;
}
function make_role($id, array $perms) {
  return new User_Role(array_merge(array('Id' => $id, 'Name' => 'r'.$id), $perms));
}

$event = new Event(array('Id' => 5, 'MonitorId' => 1));
$hiddenEvent = new Event(array('Id' => 6, 'MonitorId' => 2));

$none = array('Events' => 'None', 'Snapshots' => 'None');
$roleEventsView = make_user(20, $none, make_role(1, array('Events' => 'View')));
$roleEventsEdit = make_user(21, $none, make_role(2, array('Events' => 'Edit')));
$roleNone = make_user(22, $none, make_role(3, array('Events' => 'None')));
$noRole = make_user(23, $none);
$ownView = make_user(24, array('Events' => 'View', 'Snapshots' => 'None'));
$ownEdit = make_user(25, array('Events' => 'Edit', 'Snapshots' => 'None'));
$ownViewRoleEdit = make_user(26, array('Events' => 'View', 'Snapshots' => 'None'), make_role(4, array('Events' => 'Edit')));

// ---- granted through the role ----------------------------------------------

check('role Events=View can view an event', $event->canView($roleEventsView), true);
check('role Events=View cannot edit an event', $event->canEdit($roleEventsView), false);
check('role Events=Edit can view an event', $event->canView($roleEventsEdit), true);
check('role Events=Edit can edit an event', $event->canEdit($roleEventsEdit), true);
check('own Events=View with role Events=Edit can edit an event', $event->canEdit($ownViewRoleEdit), true);

// ---- the monitor still decides -----------------------------------------------

check('role Events=Edit cannot view an event of a monitor it may not view', $hiddenEvent->canView($roleEventsEdit), false);
check('role Events=Edit cannot edit an event of a monitor it may not view', $hiddenEvent->canEdit($roleEventsEdit), false);

// ---- no grant anywhere --------------------------------------------------------

check('role Events=None cannot view an event', $event->canView($roleNone), false);
check('role Events=None cannot edit an event', $event->canEdit($roleNone), false);
check('no role, Events=None cannot view an event', $event->canView($noRole), false);
check('no role, Events=None cannot edit an event', $event->canEdit($noRole), false);

// ---- the user's own fields, unchanged -----------------------------------------

check('own Events=View can view an event', $event->canView($ownView), true);
check('own Events=View cannot edit an event', $event->canEdit($ownView), false);
check('own Events=Edit can edit an event', $event->canEdit($ownEdit), true);

// ---- events held in a snapshot ---------------------------------------------------
// A Snapshots grant lets a user view the events in a snapshot, not edit them.

$snapEvent = new Event(array('Id' => 7, 'MonitorId' => 1));
$hiddenSnapEvent = new Event(array('Id' => 8, 'MonitorId' => 2));
$ownSnapshots = make_user(27, array('Events' => 'None', 'Snapshots' => 'View'));
$roleSnapshots = make_user(28, $none, make_role(5, array('Events' => 'None', 'Snapshots' => 'View')));

check('own Snapshots=View can view an event in a snapshot', $snapEvent->canView($ownSnapshots), true);
check('role Snapshots=View can view an event in a snapshot', $snapEvent->canView($roleSnapshots), true);
check('own Snapshots=View cannot view an event outside a snapshot', $event->canView($ownSnapshots), false);
check('role Snapshots=View cannot view an event outside a snapshot', $event->canView($roleSnapshots), false);
check('own Snapshots=View cannot view a snapshot event of a monitor it may not view', $hiddenSnapEvent->canView($ownSnapshots), false);
check('own Snapshots=View cannot edit an event in a snapshot', $snapEvent->canEdit($ownSnapshots), false);
check('no grant cannot view an event in a snapshot', $snapEvent->canView($noRole), false);

array_map('unlink', glob($stubdir.'/*'));
@rmdir($stubdir);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
