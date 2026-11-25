<?php
// Runs web/includes/actions/user.php (Save) against stubbed models. Run: php tests/php/test_user_edit_save.php
namespace ZM {
  $GLOBALS['users'] = array();
  $GLOBALS['saved_perms'] = array();
  function Warning($m) {}
  function Error($m) {}
  function AuditAction($a, $o, $id, $t) {}

  class StubPerm {
    public $p; public $gid;
    function __construct($p, $gid) { $this->p = $p; $this->gid = $gid; }
    function Permission() { return $this->p; }
    function save($v) { $GLOBALS['saved_perms'][] = array($this->gid, $v['Permission']); $this->p = $v['Permission']; return true; }
  }
  class Group {
    public $id;
    function __construct($id) { $this->id = $id; }
    static function find() { return $GLOBALS['groups']; }
    function Id() { return $this->id; }
    function Group_Permission($uid) { return new StubPerm('Inherit', $this->id); }
    function Permissions($n = null) {}
  }
  class Monitor {
    static function find($p = null) { return array(); }
  }
  class User {
    public $f;
    function __construct($id = 0) {
      $this->f = $id ? $GLOBALS['users'][$id] : array('Id' => null, 'Username' => '', 'Password' => '');
    }
    static function find_one($p) {
      foreach ($GLOBALS['users'] as $u) {
        foreach ($p as $k => $v) { if ($u[$k] != $v) continue 2; }
        $o = new User(); $o->f = $u; return $o;
      }
    }
    function Id() { return $this->f['Id']; }
    function Username() { return $this->f['Username']; }
    function Password() { return $this->f['Password']; }
    function changes($new) {
      $c = array();
      foreach ($new as $k => $v) {
        if ($k == 'Username') $v = preg_replace('/[^\w\.@ ]/', '', trim($v));
        if (($this->f[$k] ?? null) != $v) $c[$k] = $v;
      }
      return $c;
    }
    function save($c) { $GLOBALS['user_saves'][] = $c; $this->f = array_merge($this->f, $c); if (!$this->f['Id']) $this->f['Id'] = 99; $GLOBALS['users'][$this->f['Id']] = $this->f; return true; }
    function get_last_error() { return 'err'; }
    function Monitor_Permissions($n = null) {}
  }
}

namespace {
  $GLOBALS['zm_root'] = $argv[1] ?? __DIR__.'/../..';
  $tmp = sys_get_temp_dir().'/zm_user_edit_save_'.getmypid();
  @mkdir($tmp.'/includes', 0700, true);
  file_put_contents($tmp.'/includes/User.php', "<?php\n");
  set_include_path($tmp.PATH_SEPARATOR.get_include_path());

  define('ZM_USER_SELF_EDIT', 0);
  define('ZM_PATH_WEB', '/nonexistent');
  define('ZM_AUTH_HASH_IPS', 0);
  function canEdit($a) { return true; }
  function validInt($v) { return (int)$v; }
  function passwordProblem($p) { return null; }
  function passwordProblemText($p) { return $p; }
  $auth_calls = 0;
  function zm_session_start() {}
  function generateAuthHash($a, $b) { global $auth_calls; $auth_calls++; }

  set_error_handler(function($no, $str) { throw new ErrorException($str); });

  $passed = 0; $failed = 0;
  function check($label, $got, $want) {
    global $passed, $failed;
    if ($got === $want) { $passed++; echo "  ok $label\n"; }
    else { $failed++; echo "  FAIL $label\n       got:  ".var_export($got, true)."\n       want: ".var_export($want, true)."\n"; }
  }

  function run_save($request, $post = array(), $me = null) {
    global $action, $user, $error_message, $user_saves, $saved_perms, $auth_calls;
    $action = 'Save'; $error_message = ''; $user_saves = array(); $saved_perms = array(); $auth_calls = 0;
    $user = $me;
    $_REQUEST = $request; $_POST = $post;
    $notice = null;
    try { include ($GLOBALS['zm_root'] ?? __DIR__.'/../..').'/web/includes/actions/user.php'; }
    catch (ErrorException $e) { $notice = $e->getMessage(); }
    return $notice;
  }

  $groups = array();
  $users = array(1 => array('Id' => 1, 'Username' => 'alice', 'Password' => 'x', 'Enabled' => 1),
                 2 => array('Id' => 2, 'Username' => 'bob', 'Password' => 'x', 'Enabled' => 1));
  $alice = new ZM\User(1);

  // Admin renames their own account: the session needs a new auth hash.
  $n = run_save(array('uid' => '1', 'user' => array('Username' => 'alice2')), array(), $alice);
  check('rename self: no notice', $n, null);
  check('rename self: auth hash regenerated', $auth_calls, 1);

  // Admin changes their own password: the session keeps the new hash, so the
  // password check on the next request doesn't log them out.
  $_SESSION = array('passwordHash' => 'x');
  $n = run_save(array('uid' => '1', 'user' => array('Username' => 'alice2', 'Password' => 'n3w-Passw0rd!')), array(), $alice);
  check('own password change: no notice', $n, null);
  check('own password change: session hash follows the new password',
    password_verify('n3w-Passw0rd!', $_SESSION['passwordHash'] ?? ''), true);

  // A group missing from the posted group_permission must not be overwritten with null.
  $groups = array(new ZM\Group(5), new ZM\Group(6));
  $n = run_save(array('uid' => '2', 'user' => array('Username' => 'bob')),
    array('group_permission' => array(5 => 'View')), $alice);
  check('missing group key: no notice', $n, null);
  check('missing group key: only posted group saved', $saved_perms, array(array(5, 'View')));
  $groups = array();

  echo "$passed passed, $failed failed\n";
  exit($failed ? 1 : 0);
}
