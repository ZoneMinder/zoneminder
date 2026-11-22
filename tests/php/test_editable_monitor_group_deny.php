<?php
// Security regression test: a monitor hidden by a group None deny can't be edited.
//
// editableMonitor() only looked for an Edit grant, so a None on one of the user's groups (or on
// a role group) fell through to the global Monitors permission: a Monitors=Edit user scoped out
// of a monitor could still change it while visibleMonitor() hid it. editableMonitor() now
// refuses any monitor visibleMonitor() refuses.
//
// auth.php can't be included without a database, so visibleMonitor() and editableMonitor() are
// taken from the source and run against stand-in users, groups and roles.
//
// Run as: php tests/php/test_editable_monitor_group_deny.php

namespace ZM {
  function Debug($s) { /* noop */ }

  class Monitor_Permission {
    public static $rows = array();
    private $MonitorId;
    private $Permission;
    public function __construct($mid, $perm) { $this->MonitorId = $mid; $this->Permission = $perm; }
    public function MonitorId() { return $this->MonitorId; }
    public function Permission() { return $this->Permission; }
    public static function find($where) { return self::$rows; }
  }

  // One group holding the given monitors, with this user's or role's permission on it.
  class Group_Permission {
    private $Permission;
    private $mids;
    public function __construct($perm, $mids) { $this->Permission = $perm; $this->mids = $mids; }
    public static function find($where) { global $user; return $user ? $user->groups : array(); }
    public function Permission() { return $this->Permission; }
    public function MonitorPermission($mid) { return in_array($mid, $this->mids) ? $this->Permission : 'Inherit'; }
    public function Group() { return new class { public function Name() { return 'G'; } }; }
  }

  class StubRole {
    public $monitors;
    public $groups;
    public function __construct($monitors, $groups) { $this->monitors = $monitors; $this->groups = $groups; }
    public function Monitors() { return $this->monitors; }
    public function Monitor_Permissions() { return array(); }
    public function Group_Permissions() { return $this->groups; }
  }

  class StubUser {
    public $monitors;
    public $groups;
    public $role;
    public function __construct($monitors, $groups, $role = null) {
      $this->monitors = $monitors; $this->groups = $groups; $this->role = $role;
    }
    public function Id() { return 7; }
    public function Monitors() { return $this->monitors; }
    public function Monitor_Permissions() { return array_to_hash_by_key('MonitorId', Monitor_Permission::$rows); }
    public function Group_Permissions() { return $this->groups; }
    public function Role() { return $this->role; }
  }
}

namespace {
  function array_to_hash_by_key($key, $array) {
    $results = array();
    foreach ($array as $a) $results[$a->$key()] = $a;
    return $results;
  }

  $source = file_get_contents(__DIR__.'/../../web/includes/auth.php');
  foreach (array('visibleMonitor', 'editableMonitor') as $fn) {
    if (!preg_match('/^function '.$fn.'\(.*?^}\n/ms', $source, $m)) {
      echo "FAIL - could not find $fn in auth.php\n";
      exit(1);
    }
    eval($m[0]);
  }

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

  // Each case starts with empty permission caches, as a new request does.
  function as_user($u, $monitor_rows = array()) {
    global $user, $monitor_permissions, $group_permissions, $role_monitor_permissions, $role_group_permissions;
    $user = $u;
    ZM\Monitor_Permission::$rows = $monitor_rows;
    $monitor_permissions = $group_permissions = $role_monitor_permissions = $role_group_permissions = null;
  }

  $denied = 2;
  $other = 1;

  foreach (array('Edit', 'Create') as $level) {
    as_user(new ZM\StubUser($level, array(new ZM\Group_Permission('None', array($denied)))));
    check("Monitors=$level, group None: not visible", visibleMonitor($denied), false);
    check("Monitors=$level, group None: not editable", editableMonitor($denied), false);
    check("Monitors=$level, group None: other monitor still editable", editableMonitor($other), true);
  }

  as_user(new ZM\StubUser('None', array(), new ZM\StubRole('Edit', array(new ZM\Group_Permission('None', array($denied))))));
  check('role Monitors=Edit, role group None: not editable', editableMonitor($denied), false);
  check('role Monitors=Edit, role group None: other monitor still editable', editableMonitor($other), true);

  // Grants keep working.
  as_user(new ZM\StubUser('Edit', array(new ZM\Group_Permission('Edit', array($denied)))));
  check('group Edit: editable', editableMonitor($denied), true);
  as_user(new ZM\StubUser('View', array(), null), array(new ZM\Monitor_Permission($denied, 'Edit')));
  check('per-monitor Edit over Monitors=View: editable', editableMonitor($denied), true);
  as_user(new ZM\StubUser('Edit', array(), null), array(new ZM\Monitor_Permission($denied, 'View')));
  check('per-monitor View: not editable', editableMonitor($denied), false);
  as_user(new ZM\StubUser('Edit', array()));
  check('Monitors=Edit, no scoping: editable', editableMonitor($denied), true);
  as_user(new ZM\StubUser('View', array()));
  check('Monitors=View, no scoping: not editable', editableMonitor($denied), false);
  as_user(null);
  check('not logged in: not editable', editableMonitor($denied), false);

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
