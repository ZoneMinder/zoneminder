<?php
// Security regression test: includes/actions/snapshot.php action=create forces an
// alarm on every monitor it is given (zmu -a) and archives the events recorded,
// and used to do it for any logged-in user, on any monitor id. It now needs
// Snapshots edit and only uses monitors the user can view; nothing is created
// when no monitor is left.
//
// Includes the real action file with the web helpers stubbed and records what
// it does.
//
// Run as: php tests/php/test_snapshot_create_requires_snapshots_edit.php

namespace ZM {
  function Warning($s) { $GLOBALS['log'][] = "warning: $s"; }
  function Error($s) { $GLOBALS['log'][] = "error: $s"; }
  function Debug($s) {}
  function AuditAction($a, $b, $c, $d) {}
  class Snapshot {
    public function save($a) { $GLOBALS['done'][] = 'Snapshot::save'; return true; }
    public function Id() { return 5; }
    public function Events() { return array(); }
  }
  class Snapshot_Event {
    public function save($a) { $GLOBALS['done'][] = 'Snapshot_Event::save'; return true; }
  }
  class Monitor {
    private $id;
    public function __construct($id) { $this->id = $id; }
    public static function find_one($w) {
      return in_array((int)$w['Id'], $GLOBALS['monitors_exist'], true) ? new Monitor((int)$w['Id']) : null;
    }
    public function Id() { return $this->id; }
    public function TriggerOn() { $GLOBALS['done'][] = 'TriggerOn '.$this->id; return 100 + $this->id; }
    public function TriggerOff() { $GLOBALS['done'][] = 'TriggerOff '.$this->id; }
  }
}

namespace {
  $stubdir = sys_get_temp_dir().'/zm_snapshot_create_stubs_'.getmypid();
  @mkdir($stubdir.'/includes', 0700, true);
  file_put_contents($stubdir.'/includes/Snapshot.php', "<?php\n");
  file_put_contents($stubdir.'/includes/Monitor.php', "<?php\n");
  set_include_path($stubdir.PATH_SEPARATOR.get_include_path());

  class StubUser { public function Id() { return 2; } }
  class StubConn { public function beginTransaction() {} public function commit() {} }
  $user = new StubUser();
  $dbConn = new StubConn();
  $GLOBALS['monitors_exist'] = array(1, 2);

  function canEdit($area, $mid = false) { return ($GLOBALS['perms'][$area] ?? 'None') === 'Edit'; }
  function visibleMonitor($mid) { return in_array($mid, $GLOBALS['visible'], true); }
  function validCardinal($v) { return preg_replace('/\D/', '', $v); }
  function validHtmlStr($s) { return htmlspecialchars((string)$s); }

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

  function create($perms, $visible, $ids) {
    global $user, $dbConn;
    $GLOBALS['perms'] = $perms;
    $GLOBALS['visible'] = $visible;
    $GLOBALS['done'] = array();
    $GLOBALS['log'] = array();
    $_REQUEST = array('view' => 'snapshot', 'action' => 'create', 'monitor_ids' => $ids);
    $action = 'create';
    $redirect = null;
    include __DIR__.'/../../web/includes/actions/snapshot.php';
    return $GLOBALS['done'];
  }

  $all = array('Snapshots' => 'Edit', 'Events' => 'Edit', 'Monitors' => 'Edit', 'Stream' => 'View');
  $viewer = array('Snapshots' => 'None', 'Events' => 'View', 'Monitors' => 'View', 'Stream' => 'View');
  $full = array('Snapshot::save', 'TriggerOn 1', 'Snapshot_Event::save', 'TriggerOff 1');

  check('no permissions: nothing created, no alarm', create(array(), array(1, 2), array('1')), array());
  check('viewer (Snapshots None): nothing created, no alarm', create($viewer, array(1, 2), array('1')), array());
  check('Snapshots view: nothing created',
    create(array_merge($viewer, array('Snapshots' => 'View')), array(1, 2), array('1')), array());
  check('Snapshots edit: snapshot of a visible monitor', create($all, array(1, 2), array('1')), $full);
  check('Snapshots edit: a hidden monitor is skipped', create($all, array(1), array('2', '1')), $full);
  check('Snapshots edit: only hidden monitors, nothing created', create($all, array(1), array('2')), array());
  check('Snapshots edit: a missing monitor is skipped', create($all, array(1, 2, 9), array('9', '1')), $full);
  check('Snapshots edit: no monitor ids, nothing created', create($all, array(1, 2), array()), array());
  // last: before the fix an invalid id reached Error(), the PHP class, and was fatal
  check('Snapshots edit: a non-numeric id is skipped (no fatal)', create($all, array(1, 2), array('1 OR 1', array('1'), '1')), $full);

  @unlink($stubdir.'/includes/Snapshot.php');
  @unlink($stubdir.'/includes/Monitor.php');
  @rmdir($stubdir.'/includes');
  @rmdir($stubdir);

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
