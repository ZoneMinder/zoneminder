<?php
// The user Save action filtered the typed Username into $username but then saved and checked
// the raw request value, so the allowed-character filter had no effect and "bob!" passed the
// uniqueness check against an existing "bob". It now saves and checks the filtered name.
//
// Run: php tests/php/test_user_username_filtered.php [root]
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
  $root = isset($argv[1]) ? rtrim($argv[1], '/') : __DIR__.'/../..';
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
    try { include $GLOBALS['root'].'/web/includes/actions/user.php'; }
    catch (ErrorException $e) { $notice = $e->getMessage(); }
    return $notice;
  }

  $groups = array();
  $users = array(1 => array('Id' => 1, 'Username' => 'alice', 'Password' => 'x', 'Enabled' => 1),
                 2 => array('Id' => 2, 'Username' => 'bob', 'Password' => 'x', 'Enabled' => 1));
  $alice = new ZM\User(1);

  // A new user whose name only differs from an existing one by characters that are filtered out.
  $n = run_save(array('user' => array('Username' => 'bob!', 'Password' => 'x')), array(), $alice);
  check('filtered name that exists: no notice', $n, null);
  check('filtered name that exists: refused', $error_message, 'There already exists a user with this Username<br/>');
  check('filtered name that exists: nothing saved', $user_saves, array());

  // The saved name is the filtered one.
  $n = run_save(array('user' => array('Username' => ' carol<b> ', 'Password' => 'x')), array(), $alice);
  check('filtered name: no notice', $n, null);
  check('filtered name: saved without the removed characters', $user_saves[0]['Username'] ?? null, 'carolb');

  echo "$passed passed, $failed failed\n";
  exit($failed ? 1 : 0);
}
