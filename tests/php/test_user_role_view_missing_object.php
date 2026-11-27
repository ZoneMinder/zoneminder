<?php
// The user and role views guarded the loaded object with
//   if (!($User = new ZM\User(validCardinal($_REQUEST['uid'])))) { $view = 'error'; return; }
// but `new` always yields an object, so the guard never fired and a request for a
// uid / rid that matches no row showed a blank add form instead of the error page.
// The guard must test $User->Id() / $Role->Id() after assigning the object.
//
// The view is halted right after the guard (the first translate() call throws STOP),
// so only the guard behaviour is exercised.
//
// Run as: php tests/php/test_user_role_view_missing_object.php [views dir]

namespace ZM {
  class User {
    public $id;
    function __construct($id = null) { $this->id = $id; }
    function Id() { return $this->id == 7 ? 7 : null; }
    function __call($name, $args) { return ''; }
  }
  class User_Role {
    public $id;
    function __construct($id = null) { $this->id = $id; }
    function Id() { return $this->id == 7 ? 7 : null; }
    function __call($name, $args) { return ''; }
  }
}

namespace {
  $views = isset($argv[1]) ? $argv[1] : __DIR__.'/../../web/skins/classic/views';
  $failures = 0;
  function check($name, $got, $want) {
    global $failures;
    if ($got === $want) echo "ok - $name\n";
    else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
  }

  $stub = sys_get_temp_dir().'/zm_user_role_view_'.getmypid();
  mkdir($stub.'/includes', 0777, true);
  foreach (array('User', 'User_Role', 'Group', 'Group_Permission', 'Role_Group_Permission') as $f) {
    file_put_contents($stub."/includes/$f.php", "<?php\n");
  }
  set_include_path($stub.PATH_SEPARATOR.get_include_path());

  if (!defined('ZM_USER_SELF_EDIT')) define('ZM_USER_SELF_EDIT', false);
  function canEdit($a) { return true; }
  function validCardinal($v) { return (int)$v; }
  function translate($s) { throw new RuntimeException('STOP'); }
  function validHtmlStr($s) { return htmlspecialchars($s); }
  function xhtmlHeaders($f, $t) {}
  function getBodyTopHTML() {}
  function getNavBarHTML() { return ''; }
  function xhtmlFooter() {}
  $user = new ZM\User(7);

  function run_view($file, $req) {
    global $user;
    $_REQUEST = $req;
    $view = 'start';
    ob_start();
    try {
      include $file;
    } catch (Throwable $e) {
      if ($e->getMessage() !== 'STOP') {
        global $failures;
        $failures++;
        echo "FAIL - $file threw ".get_class($e).': '.$e->getMessage()."\n";
      }
    } finally {
      ob_end_clean();
    }
    return $view;
  }

  $cases = array(
    array('user.php', 'uid', 'user'),
    array('role.php', 'rid', 'role'),
  );

  foreach ($cases as $case) {
    list($file, $param, $name) = $case;
    $path = $views.'/'.$file;

    $view = run_view($path, array($param => 99));
    check("$name view for a missing id shows the error page", $view, 'error');

    $view = run_view($path, array($param => 7));
    check("$name view for an existing id stays on the view", $view !== 'error', true);
  }

  system('rm -rf '.escapeshellarg($stub));
  echo $failures ? "\n$failures failed\n" : "\nall passed\n";
  exit($failures ? 1 : 0);
}
