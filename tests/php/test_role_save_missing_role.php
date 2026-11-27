<?php
// includes/actions/role.php built the role from rid and saved it whether or not the row
// still existed. Editing a role that was deleted from another tab inserted a new role with
// the posted name and permissions instead of saying the role is gone. Runs the real action
// file with stubbed classes.
//
// Run as: php tests/php/test_role_save_missing_role.php [repo root]

namespace ZM {
  function Warning($s) {}
  function Error($s) {}
  function Debug($s) {}
  function AuditAction($a, $b, $c, $d) {}
  class User_Role {
    private $id = null;
    function __construct($id = 0) { if ($id and in_array((int)$id, $GLOBALS['existing_roles'])) $this->id = (int)$id; }
    static function find_one($w) { return null; }
    function changes($new) { return $new; }
    function save($c) { $GLOBALS['saves'][] = $this->id === null ? 'insert' : 'update '.$this->id; if ($this->id === null) $this->id = 50; return true; }
    function Id() { return $this->id; }
    function Name() { return 'r'; }
  }
}

namespace {
  $root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
  $stubdir = sys_get_temp_dir().'/zm_role_missing_stubs_'.getmypid();
  @mkdir($stubdir.'/includes', 0700, true);
  foreach (array('User_Role', 'Role_Group_Permission', 'Role_Monitor_Permission', 'Group', 'Monitor') as $f)
    file_put_contents($stubdir.'/includes/'.$f.'.php', "<?php\n");
  set_include_path($stubdir.PATH_SEPARATOR.get_include_path());

  function canEdit($area, $mid = false) { return true; }
  function validInt($v) { return is_scalar($v) ? preg_replace('/[^\-\d]/', '', $v) : ''; }

  $failures = 0;
  function check($name, $got, $want) {
    global $failures;
    if ($got === $want) echo "ok - $name\n";
    else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
  }

  $existing_roles = array(3);

  function runRole($request) {
    global $root, $error_message;
    $GLOBALS['saves'] = array(); $error_message = '';
    $_POST = array(); $_REQUEST = $request + array('role' => array('Name' => 'Viewer'));
    $action = 'Save';
    include $root.'/web/includes/actions/role.php';
    return $GLOBALS['saves'];
  }

  check('editing an existing role updates it', runRole(array('rid' => '3')), array('update 3'));
  check('a new role is inserted', runRole(array('rid' => '0')), array('insert'));
  check('a new role without rid is inserted', runRole(array()), array('insert'));
  check('a deleted role is not inserted again', runRole(array('rid' => '9')), array());
  check('a deleted role reports that it is gone', $error_message, 'Role not found<br/>');

  foreach (array('User_Role', 'Role_Group_Permission', 'Role_Monitor_Permission', 'Group', 'Monitor') as $f)
    @unlink($stubdir.'/includes/'.$f.'.php');
  @rmdir($stubdir.'/includes'); @rmdir($stubdir);
  echo $failures ? "\n$failures failed\n" : "\nall passed\n";
  exit($failures ? 1 : 0);
}
