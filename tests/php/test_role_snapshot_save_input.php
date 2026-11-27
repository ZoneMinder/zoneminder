<?php
// includes/actions/role.php stored group_permission/monitor_permission values unchecked
// (any string or array reached the Permission enum column), took an array role[Name]
// into find_one(), and includes/actions/snapshot.php passed a missing snapshot[] field
// to changes() (foreach over null). Runs the real action files with stubbed helpers.
//
// Run as: php tests/php/test_role_snapshot_save_input.php

namespace ZM {
  function Warning($s) {}
  function Error($s) {}
  function Debug($s) {}
  function AuditAction($a, $b, $c, $d) {}
  class Perm {
    public $id; public $kind;
    function __construct($kind, $id) { $this->kind = $kind; $this->id = $id; }
    function Permission() { return 'Inherit'; }
    function RoleId($v = null) {}
    function save($c) { $GLOBALS['saved'][] = $this->kind.$this->id.'='.var_export($c['Permission'], true); return true; }
  }
  class User_Role {
    function __construct($id = 0) {}
    static function find_one($w) { $GLOBALS['find_one'][] = $w; return null; }
    function changes($new) { foreach ($new as $k => $v) {} return array(); }
    function save($c) { return true; }
    function Id() { return 3; }
    function Name() { return 'r'; }
    function Group_Permission($id) { return new Perm('g', $id); }
    function Monitor_Permission($id) { return new Perm('m', $id); }
  }
  class Group { static function find() { return array(new Group()); } function Id() { return 1; } }
  class Monitor { static function find($w) { return array(new Monitor()); } function Id() { return 2; } }
  class Snapshot {
    function __construct($id = null) {}
    function CreatedBy() { return 2; }
    function Id() { return 5; }
    function changes($new) { foreach ($new as $k => $v) {} $GLOBALS['changes_arg'] = $new; return array(); }
    function save($c) { return true; }
  }
}

namespace {
  $stubdir = sys_get_temp_dir().'/zm_role_snapshot_stubs_'.getmypid();
  @mkdir($stubdir.'/includes', 0700, true);
  foreach (array('User_Role', 'Role_Group_Permission', 'Role_Monitor_Permission', 'Group', 'Monitor', 'Snapshot') as $f)
    file_put_contents($stubdir.'/includes/'.$f.'.php', "<?php\n");
  set_include_path($stubdir.PATH_SEPARATOR.get_include_path());

  class StubUser { function Id() { return 2; } }
  $user = new StubUser();
  function canEdit($area, $mid = false) { return true; }
  function validInt($v) { return (int)$v; }
  function validCardinal($v) { return (int)$v; }

  set_error_handler(function ($no, $str) { throw new ErrorException($str); });

  $failures = 0;
  function check($name, $got, $want) {
    global $failures;
    if ($got === $want) echo "ok - $name\n";
    else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
  }

  function runRole($post) {
    $GLOBALS['saved'] = array(); $GLOBALS['find_one'] = array();
    $_POST = $post; $_REQUEST = array_merge(array('rid' => '3', 'redirect' => 'x'), $post);
    $action = 'Save';
    try { include __DIR__.'/../../web/includes/actions/role.php'; } catch (\Throwable $e) { return 'threw: '.$e->getMessage(); }
    return $GLOBALS['saved'];
  }

  $ok = array('Name' => 'r');
  check('valid values are saved',
    runRole(array('role' => $ok, 'group_permission' => array(1 => 'Edit'), 'monitor_permission' => array(2 => 'None'))),
    array("g1='Edit'", "m2='None'"));
  check('unknown permission strings are skipped',
    runRole(array('role' => $ok, 'group_permission' => array(1 => 'Admin'), 'monitor_permission' => array(2 => "View' OR 1"))),
    array());
  check('array permission values are skipped',
    runRole(array('role' => $ok, 'group_permission' => array(1 => array('Edit')), 'monitor_permission' => array(2 => array('Edit')))),
    array());
  check('array role[Name] is rejected without a lookup', runRole(array('role' => array('Name' => array('a')))), array());
  check('array role[Name] never reaches find_one', $GLOBALS['find_one'], array());
  check('string role field is rejected', runRole(array('role' => 'x')), array());

  function runSnap($req) {
    $GLOBALS['changes_arg'] = 'unset';
    $_REQUEST = array_merge(array('id' => '5'), $req);
    $action = 'save';
    try { include __DIR__.'/../../web/includes/actions/snapshot.php'; } catch (\Throwable $e) { return 'threw: '.$e->getMessage(); }
    return $GLOBALS['changes_arg'];
  }
  check('missing snapshot field gives empty changes', runSnap(array()), array());
  check('string snapshot field gives empty changes', runSnap(array('snapshot' => 'x')), array());
  check('array snapshot field is used', runSnap(array('snapshot' => array('Name' => 'n'))), array('Name' => 'n'));

  foreach (array('User_Role', 'Role_Group_Permission', 'Role_Monitor_Permission', 'Group', 'Monitor', 'Snapshot') as $f)
    @unlink($stubdir.'/includes/'.$f.'.php');
  @rmdir($stubdir.'/includes'); @rmdir($stubdir);
  echo $failures ? "\n$failures failed\n" : "\nall passed\n";
  exit($failures ? 1 : 0);
}
