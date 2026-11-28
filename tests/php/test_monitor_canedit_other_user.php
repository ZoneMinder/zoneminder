<?php
// Monitor::canEdit($u) for a user other than the logged-in one called a method on each group
// permission that does not exist, so any user holding a group permission on the monitor made
// it throw. It also read $u['Monitors'] rather than the user's Monitors() value. Groups and the
// user's own permission on the monitor are now consulted the way visibleMonitor() does it.
//
// php tests/php/test_monitor_canedit_other_user.php [root]   (root defaults to the repo)

namespace ZM {
  function Debug($s) {}
}

namespace {
  $root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
  $src = file_get_contents($root.'/web/includes/Monitor.php');
  $start = strpos($src, 'function canEdit($u=null) {');
  $end = strpos($src, 'function canView($u=null) {', $start);
  $method = rtrim(substr($src, $start, $end - $start));

  $failures = 0;
  function check($name, $got, $want) {
    global $failures;
    if ($got === $want) echo "ok - $name\n";
    else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
  }
  function attempt($name, $callable, $want) {
    global $failures;
    try {
      $got = $callable();
    } catch (Throwable $e) {
      $failures++;
      echo "FAIL - $name (threw ".get_class($e).": ".$e->getMessage().")\n";
      return;
    }
    check($name, $got, $want);
  }

  class FakePermission {
    private $mid, $p;
    function __construct($mid, $p) { $this->mid = $mid; $this->p = $p; }
    function Permission() { return $this->p; }
    function MonitorId() { return $this->mid; }
  }
  class FakeGroup {
    private $name;
    function __construct($name) { $this->name = $name; }
    function Name() { return $this->name; }
  }
  class FakeGroupPermission {
    private $value, $group;
    function __construct($value, $group) { $this->value = $value; $this->group = new FakeGroup($group); }
    function MonitorPermission($mid) { return $this->value; }
    function Group() { return $this->group; }
  }
  class FakeUser implements ArrayAccess {
    public $id, $monitors, $own = null, $groups = array();
    function __construct($id, $monitors) { $this->id = $id; $this->monitors = $monitors; }
    function Id() { return $this->id; }
    function Monitors() { return $this->monitors; }
    function Monitor_Permission($mid) { return $this->own; }
    function Group_Permissions() { return $this->groups; }
    #[\ReturnTypeWillChange]
    function offsetGet($offset) { return $offset === 'Monitors' ? $this->monitors : null; }
    #[\ReturnTypeWillChange]
    function offsetExists($offset) { return true; }
    #[\ReturnTypeWillChange]
    function offsetSet($offset, $value) {}
    #[\ReturnTypeWillChange]
    function offsetUnset($offset) {}
  }
  function editableMonitor($mid) { return false; }

  eval('namespace ZM; class CanEditHarness { public $Id = 3; function Id() { return $this->Id; } '.$method.' }');

  $user = new FakeUser(1, 'Edit');   // the logged-in admin looking at the monitor
  $m = new ZM\CanEditHarness();

  $u = new FakeUser(2, 'None');
  $u->groups = array(new FakeGroupPermission('Edit', 'Front'));
  attempt('Monitors=None with a group Edit grants edit', function() use ($m, $u) { return $m->canEdit($u); }, true);

  $u = new FakeUser(2, 'Edit');
  $u->groups = array(new FakeGroupPermission('None', 'Front'));
  attempt('Monitors=Edit with a group None refuses', function() use ($m, $u) { return $m->canEdit($u); }, false);

  $u = new FakeUser(2, 'Edit');
  $u->groups = array(new FakeGroupPermission('View', 'Front'));
  attempt('a group View does not deny edit', function() use ($m, $u) { return $m->canEdit($u); }, true);

  $u = new FakeUser(2, 'None');
  $u->groups = array(new FakeGroupPermission('View', 'Front'));
  attempt('Monitors=None with a group View refuses', function() use ($m, $u) { return $m->canEdit($u); }, false);

  $u = new FakeUser(2, 'Edit');
  attempt('Monitors=Edit with no group permissions grants edit', function() use ($m, $u) { return $m->canEdit($u); }, true);

  $u = new FakeUser(2, 'None');
  attempt('Monitors=None with no group permissions refuses', function() use ($m, $u) { return $m->canEdit($u); }, false);

  $u = new FakeUser(2, 'Edit');
  $u->own = new FakePermission(3, 'View');
  attempt('own View on the monitor refuses edit', function() use ($m, $u) { return $m->canEdit($u); }, false);

  $u = new FakeUser(2, 'None');
  $u->groups = array(new FakeGroupPermission('View', 'Front'), new FakeGroupPermission('Edit', 'Back'));
  attempt('any group Edit grants edit', function() use ($m, $u) { return $m->canEdit($u); }, true);

  echo $failures ? "\n$failures failed\n" : "\nall passed\n";
  exit($failures ? 1 : 0);
}
