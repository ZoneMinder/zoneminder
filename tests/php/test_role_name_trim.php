<?php
// includes/actions/role.php accepted a whitespace-only role name (it only tested the string
// for truthiness), saving a role that shows as blank in the role and user lists, and let
// ' Admin ' be stored with its padding. Runs the real action file with stubbed classes.
//
// Run as: php tests/php/test_role_name_trim.php [repo root]

namespace ZM {
  function Warning($s) {}
  function Error($s) {}
  function Debug($s) {}
  function AuditAction($a, $b, $c, $d) {}
  class User_Role {
    function __construct($id = 0) {}
    static function find_one($w) { $GLOBALS['lookup'] = $w['Name']; return null; }
    function changes($new) { $GLOBALS['saved_name'] = $new['Name']; return array(); }
    function save($c) { return true; }
    function Id() { return 3; }
    function Name() { return 'r'; }
  }
}

namespace {
  $root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
  $stubdir = sys_get_temp_dir().'/zm_role_trim_stubs_'.getmypid();
  @mkdir($stubdir.'/includes', 0700, true);
  foreach (array('User_Role', 'Role_Group_Permission', 'Role_Monitor_Permission', 'Group', 'Monitor') as $f)
    file_put_contents($stubdir.'/includes/'.$f.'.php', "<?php\n");
  set_include_path($stubdir.PATH_SEPARATOR.get_include_path());

  function canEdit($area, $mid = false) { return true; }
  function validInt($v) { return (int)$v; }

  $failures = 0;
  function check($name, $got, $want) {
    global $failures;
    if ($got === $want) echo "ok - $name\n";
    else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
  }

  function runRole($name) {
    global $root, $error_message;
    $GLOBALS['saved_name'] = null; $GLOBALS['lookup'] = null; $error_message = '';
    $_POST = array(); $_REQUEST = array('rid' => '3', 'redirect' => 'x', 'role' => array('Name' => $name));
    $action = 'Save';
    include $root.'/web/includes/actions/role.php';
    return array($GLOBALS['saved_name'], $error_message !== '');
  }

  check('whitespace-only name is rejected', runRole("   "), array(null, true));
  check('name is trimmed before save', runRole("  Admin "), array('Admin', false));
  check('name is trimmed before the duplicate lookup', $GLOBALS['lookup'], 'Admin');

  foreach (array('User_Role', 'Role_Group_Permission', 'Role_Monitor_Permission', 'Group', 'Monitor') as $f)
    @unlink($stubdir.'/includes/'.$f.'.php');
  @rmdir($stubdir.'/includes'); @rmdir($stubdir);
  echo $failures ? "\n$failures failed\n" : "\nall passed\n";
  exit($failures ? 1 : 0);
}
