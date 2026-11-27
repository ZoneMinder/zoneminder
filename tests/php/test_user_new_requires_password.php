<?php
// Add User with no password stored an empty Password because the action only unset it; a new user must now be refused.
// Runs web/includes/actions/user.php (Save) against stubbed models. Run: php tests/php/test_user_new_requires_password.php
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
    function changes($new) {
      $c = array();
      foreach ($new as $k => $v) {
        if ($k == 'Username') $v = preg_replace('/[^\w\.@ ]/', '', trim($v));
        if (($this->f[$k] ?? null) != $v) $c[$k] = $v;
      }
      return $c;
    }
    function save($c) { $GLOBALS['user_saves'][] = $c; $this->f = array_merge($this->f, $c); if (!$this->f['Id']) $this->f['Id'] = 99; return true; }
    function get_last_error() { return 'err'; }
    function Monitor_Permissions($n = null) {}
  }
}

namespace {
  $root = isset($argv[1]) ? $argv[1] : dirname(__DIR__, 2);
  $tmp = sys_get_temp_dir().'/zm_user_new_requires_password_'.getmypid();
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
    try { include $GLOBALS['root'].'/web/includes/actions/user.php'; }
    catch (ErrorException $e) { $notice = $e->getMessage(); }
    return $notice;
  }
  $GLOBALS['root'] = $root;

  $groups = array();

  // Creating a user with no password must be refused instead of saving an empty Password.
  $GLOBALS['users'] = array();
  $n = run_save(array('user' => array('Username' => 'newbie', 'Password' => '')), array(), null);
  check('new user no password: no notice', $n, null);
  check('new user no password: no save', $GLOBALS['user_saves'], array());
  check('new user no password: error reported', $GLOBALS['error_message'] !== '', true);
  check('new user no password: mentions NewPasswordRequired', strpos($GLOBALS['error_message'], 'NewPasswordRequired') !== false, true);

  // Creating a user with a password stores a bcrypt hash.
  $GLOBALS['users'] = array();
  $n = run_save(array('user' => array('Username' => 'newbie2', 'Password' => 'secretpw1')), array(), null);
  check('new user with password: no notice', $n, null);
  check('new user with password: one save', count($GLOBALS['user_saves']), 1);
  check('new user with password: bcrypt hash', substr($GLOBALS['user_saves'][0]['Password'], 0, 2), '$2');

  // Editing an existing user without touching the Password field must not change it.
  $GLOBALS['users'] = array(5 => array('Id' => 5, 'Username' => 'eve', 'Password' => 'hash', 'Enabled' => 1));
  $n = run_save(array('uid' => '5', 'user' => array('Username' => 'eve2', 'Password' => '')), array(), new ZM\User(5));
  check('edit user no password: no notice', $n, null);
  check('edit user no password: no error', $GLOBALS['error_message'], '');
  check('edit user no password: one save', count($GLOBALS['user_saves']), 1);
  check('edit user no password: Password not changed', array_key_exists('Password', $GLOBALS['user_saves'][0]), false);

  echo "$passed passed, $failed failed\n";
  exit($failed ? 1 : 0);
}
