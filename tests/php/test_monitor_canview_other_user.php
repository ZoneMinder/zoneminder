<?php
// Monitor::canView($u) for a user other than the logged-in one (the RTSP user list on the
// monitor settings page) ignored that user's own View or Edit permission on the monitor when
// their global Monitors permission was None, and let a role's None on the monitor override it.
// visibleMonitor(), which decides for the logged-in user, gives the user's own permission on
// the monitor priority over groups and role; canView($u) now does the same.
//
// php tests/php/test_monitor_canview_other_user.php [root]   (root defaults to the repo)

namespace ZM {
  function Debug($s) {}
}

namespace {
  $root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
  $src = file_get_contents($root.'/web/includes/Monitor.php');
  $start = strpos($src, 'function canView($u=null) {');
  $end = strpos($src, '} # end function canView', $start);
  $method = substr($src, $start, $end - $start + 1);

  $failures = 0;
  function check($name, $got, $want) {
    global $failures;
    if ($got === $want) echo "ok - $name\n";
    else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
  }

  class FakePermission {
    private $mid, $p;
    function __construct($mid, $p) { $this->mid = $mid; $this->p = $p; }
    function Permission() { return $this->p; }
    function MonitorId() { return $this->mid; }
  }
  class FakeRole {
    public $mons = array();
    function Monitor_Permissions() { return $this->mons; }
    function Group_Permissions() { return array(); }
    function Monitors() { return 'None'; }
  }
  class FakeUser {
    public $id, $monitors, $own = null, $role = null;
    function __construct($id, $monitors) { $this->id = $id; $this->monitors = $monitors; }
    function Id() { return $this->id; }
    function Monitors() { return $this->monitors; }
    function Monitor_Permission($mid) { return $this->own; }
    function Group_Permissions() { return array(); }
    function Role() { return $this->role; }
  }
  function visibleMonitor($mid) { return true; }

  eval('namespace ZM; class CanViewHarness { public $Id = 3; function Id() { return $this->Id; } '.$method.' }');

  $user = new FakeUser(1, 'Edit');   // the logged-in admin looking at the list
  $m = new ZM\CanViewHarness();

  $u = new FakeUser(2, 'None');
  $u->own = new FakePermission(3, 'View');
  check('own View on the monitor with Monitors=None', $m->canView($u), true);

  $u->own = new FakePermission(3, 'Edit');
  check('own Edit on the monitor with Monitors=None', $m->canView($u), true);

  $u->own = new FakePermission(3, 'None');
  check('own None on the monitor still refuses', $m->canView($u), false);

  $u = new FakeUser(2, 'View');
  $u->role = new FakeRole();
  $u->role->mons = array(new FakePermission(3, 'None'));
  $u->own = new FakePermission(3, 'View');
  check('own View wins over a role None on the monitor', $m->canView($u), true);

  $u->own = null;
  check('a role None with no own permission still refuses', $m->canView($u), false);

  $u = new FakeUser(2, 'None');
  check('no permission anywhere with Monitors=None refuses', $m->canView($u), false);

  echo $failures ? "\n$failures failed\n" : "\nall passed\n";
  exit($failures ? 1 : 0);
}
