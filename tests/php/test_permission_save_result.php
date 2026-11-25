<?php
/*
 * role.php and user.php saved group/monitor permission rows with
 * `$permission->save(...)` and ignored the result. A failed save was lost while the
 * action audited and redirected as if the permissions had been stored. save() returns
 * false and get_last_error() carries the message, so the action must append an escaped
 * 'Error saving permission: ...' and clear the redirect. On user.php a failed group
 * save must not reload the group's permissions.
 *
 * Runs the real action files against stubbed models, so it also works against an
 * unpatched copy of the repo to show the behaviour difference.
 *
 * Run: php tests/php/test_permission_save_result.php [repo root]
 */

namespace ZM {
  function Warning($m) {}
  function Error($m) {}
  function Debug($m) {}
  function AuditAction($a, $o, $id, $t) {}

  class PermStub {
    public $kind; public $id; public $perm; public $ok; public $err;
    function __construct($kind, $id, $perm, $ok, $err) {
      $this->kind = $kind; $this->id = $id; $this->perm = $perm;
      $this->ok = $ok; $this->err = $err;
    }
    function Permission() { return $this->perm; }
    function RoleId($v = null) {}
    function save($v) {
      $GLOBALS['perm_saves'][] = array($this->kind, $this->id, $v['Permission']);
      if ($this->ok) { $this->perm = $v['Permission']; return true; }
      return false;
    }
    function get_last_error() { return $this->err; }
  }

  class User_Role {
    function __construct($id = 0) {}
    static function find_one($w) { return null; }
    function changes($new) { return array(); }
    function save($c) { return true; }
    function Id() { return (int)$GLOBALS['role_id']; }
    function Name() { return 'r'; }
    function Group_Permission($gid) {
      return new PermStub('group', $gid, 'None', $GLOBALS['role_group_ok'], 'bad <b>x</b>');
    }
    function Monitor_Permission($mid) {
      return new PermStub('monitor', $mid, 'None', $GLOBALS['role_monitor_ok'], 'bad <b>x</b>');
    }
  }

  class Group {
    public $id;
    function __construct($id) { $this->id = $id; }
    function Id() { return $this->id; }
    static function find() { return $GLOBALS['groups']; }
    function Group_Permission($uid) {
      return new PermStub('group', $this->id, 'None', $GLOBALS['user_group_ok'], 'bad <b>x</b>');
    }
    function Permissions($n = null) {
      if ($n === null) $GLOBALS['group_reloads'][] = $this->id;
    }
  }

  class Monitor {
    public $id;
    function __construct($id) { $this->id = $id; }
    function Id() { return $this->id; }
    static function find($p = null) { return $GLOBALS['monitors']; }
  }

  class User {
    function __construct($id = 0) {}
    static function find_one($p) { return null; }
    function Id() { return 1; }
    function Username() { return 'alice'; }
    function changes($new) { return array(); }
    function save($c) { return true; }
    function get_last_error() { return 'err'; }
    function Monitor_Permission($mid) {
      return new PermStub('monitor', $mid, 'None', $GLOBALS['user_monitor_ok'], 'bad <b>x</b>');
    }
    function Monitor_Permissions($n = null) {}
  }
}

namespace {
  $root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';

  // The action files require_once their models; empty files satisfy that, the
  // stub classes above are already defined.
  $stubdir = sys_get_temp_dir().'/zm_perm_save_result_stubs_'.getmypid();
  @mkdir($stubdir.'/includes', 0700, true);
  foreach (array('User_Role', 'Role_Group_Permission', 'Role_Monitor_Permission',
                 'Group', 'Monitor', 'User') as $f) {
    file_put_contents($stubdir.'/includes/'.$f.'.php', "<?php\n");
  }
  set_include_path($stubdir.PATH_SEPARATOR.get_include_path());

  define('ZM_USER_SELF_EDIT', 0);
  define('ZM_PATH_WEB', '/nonexistent');
  define('ZM_AUTH_HASH_IPS', 0);

  function canEdit($area, $mid = false) { return true; }
  function validInt($v) { return (int)$v; }
  function passwordProblem($p) { return null; }
  function passwordProblemText($p) { return $p; }
  function zm_session_start() {}
  function generateAuthHash($a, $b) {}

  $tests = 0; $failures = 0;
  function check($label, $cond) {
    global $tests, $failures;
    $tests++;
    if ($cond) echo "  ok   - $label\n"; else { $failures++; echo "  FAIL - $label\n"; }
  }

  function check_failure($state, $label) {
    check("$label: reports Error saving permission", strpos($state['error_message'], 'Error saving permission') !== false);
    check("$label: escapes the save error", strpos($state['error_message'], '&lt;b&gt;') !== false
          && strpos($state['error_message'], '<b>') === false);
    check("$label: redirect stays unset", $state['redirect_set'] === false);
    check("$label: raises no warnings", count($state['problems']) == 0);
  }

  // Runs role.php (action Save) with one group and one monitor permission.
  function run_role($group_ok, $monitor_ok) {
    global $root, $error_message, $action;
    $GLOBALS['role_id'] = 3;
    $GLOBALS['role_group_ok'] = $group_ok;
    $GLOBALS['role_monitor_ok'] = $monitor_ok;
    $GLOBALS['groups'] = array(new ZM\Group(1));
    $GLOBALS['monitors'] = array(new ZM\Monitor(2));
    $GLOBALS['perm_saves'] = array();
    $GLOBALS['group_reloads'] = array();
    $error_message = '';
    $action = 'Save';
    $_POST = array('group_permission' => array(1 => 'View'),
                   'monitor_permission' => array(2 => 'View'));
    $_REQUEST = array('rid' => '3', 'redirect' => 'x', 'role' => array('Name' => 'r'));
    $problems = array();
    set_error_handler(function ($no, $str) use (&$problems) { $problems[] = $str; return true; });
    include $root.'/web/includes/actions/role.php';
    restore_error_handler();
    return array(
      'error_message' => $GLOBALS['error_message'],
      'redirect_set' => isset($_REQUEST['redirect']),
      'saves' => $GLOBALS['perm_saves'],
      'problems' => $problems,
    );
  }

  // Runs user.php (action Save) with the given stubbed groups/monitors/posted perms.
  function run_user($group_ok, $monitor_ok, $post, $groups, $monitors) {
    global $root, $error_message, $action, $user;
    $GLOBALS['user_group_ok'] = $group_ok;
    $GLOBALS['user_monitor_ok'] = $monitor_ok;
    $GLOBALS['groups'] = $groups;
    $GLOBALS['monitors'] = $monitors;
    $GLOBALS['perm_saves'] = array();
    $GLOBALS['group_reloads'] = array();
    $error_message = '';
    $action = 'Save';
    $user = null;
    $_REQUEST = array('uid' => '1', 'redirect' => 'x', 'user' => array('Username' => 'alice'));
    $_POST = $post;
    $problems = array();
    set_error_handler(function ($no, $str) use (&$problems) { $problems[] = $str; return true; });
    include $root.'/web/includes/actions/user.php';
    restore_error_handler();
    return array(
      'error_message' => $GLOBALS['error_message'],
      'redirect_set' => isset($_REQUEST['redirect']),
      'saves' => $GLOBALS['perm_saves'],
      'reloads' => $GLOBALS['group_reloads'],
      'problems' => $problems,
    );
  }

  echo "role.php\n";
  echo "  group permission save fails:\n";
  $s = run_role(false, true);
  check_failure($s, 'group');
  check('group: monitor permission is still tried', in_array(array('monitor', 2, 'View'), $s['saves'], true));

  echo "  monitor permission save fails:\n";
  $s = run_role(true, false);
  check_failure($s, 'monitor');
  check('monitor: group permission is still tried', in_array(array('group', 1, 'View'), $s['saves'], true));

  echo "  both saves succeed:\n";
  $s = run_role(true, true);
  check('success: no error message', $s['error_message'] === '');
  check('success: redirect is kept', $s['redirect_set'] === true);
  check('success: both permissions saved', $s['saves'] == array(array('group', 1, 'View'), array('monitor', 2, 'View')));

  echo "user.php\n";
  echo "  group permission save fails:\n";
  $s = run_user(false, true, array('group_permission' => array(1 => 'View')),
    array(new ZM\Group(1)), array());
  check_failure($s, 'group');
  check('group: Permissions(null) is not called', $s['reloads'] === array());

  echo "  group permission save succeeds:\n";
  $s = run_user(true, true, array('group_permission' => array(1 => 'View')),
    array(new ZM\Group(1)), array());
  check('group success: no error message', $s['error_message'] === '');
  check('group success: redirect is kept', $s['redirect_set'] === true);
  check('group success: Permissions(null) is called', $s['reloads'] === array(1));

  echo "  monitor permission save fails:\n";
  $s = run_user(true, false, array('monitor_permission' => array(2 => 'View')),
    array(), array(new ZM\Monitor(2)));
  check_failure($s, 'monitor');

  echo "  monitor permission save succeeds:\n";
  $s = run_user(true, true, array('monitor_permission' => array(2 => 'View')),
    array(), array(new ZM\Monitor(2)));
  check('monitor success: no error message', $s['error_message'] === '');
  check('monitor success: redirect is kept', $s['redirect_set'] === true);

  foreach (array('User_Role', 'Role_Group_Permission', 'Role_Monitor_Permission',
                 'Group', 'Monitor', 'User') as $f) {
    @unlink($stubdir.'/includes/'.$f.'.php');
  }
  @rmdir($stubdir.'/includes'); @rmdir($stubdir);

  echo "\n$tests tests, $failures failures\n";
  exit($failures ? 1 : 0);
}
