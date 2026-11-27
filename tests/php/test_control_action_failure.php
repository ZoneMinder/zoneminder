<?php
// The PTZ control form (the control page, and preset save from the watch page) posts
// action=control. actions/control.php ignored the result of sendControlCommand(), which is
// false when zmcontrol.pl can't be reached or rejects the command, and redirected back as if
// the command had worked. A failed command is now reported on the control page.
//
// php tests/php/test_control_action_failure.php [action-file]

namespace ZM {
  function Warning($s) {}
  class Monitor {
    static $result = true;
    static $sent = array();
    function __construct($id) {}
    function sendControlCommand($c) { self::$sent[] = $c; return self::$result; }
  }
}

namespace {
  function canView($a, $id = null) { return true; }
  function validInt($v) { return (int)$v; }
  function buildControlCommand($m) { return '--command=moveConUp'; }
  function localRedirectUrl($referer, $default) { return $default; }

  // The action requires these two by include_path; give it empty ones.
  $stub = sys_get_temp_dir().'/zm_ctlfail_'.getmypid();
  @mkdir($stub.'/includes', 0700, true);
  file_put_contents($stub.'/includes/control_functions.php', "<?php\n");
  file_put_contents($stub.'/includes/Monitor.php', "<?php\n");
  set_include_path($stub.PATH_SEPARATOR.get_include_path());

  $action_file = isset($argv[1]) ? $argv[1] : __DIR__.'/../../web/includes/actions/control.php';

  $failures = 0;
  function check($n, $got, $want) {
    global $failures;
    if ($got === $want) echo "ok - $n\n";
    else { $failures++; echo "FAIL - $n (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
  }

  function run($result) {
    global $action_file, $action, $error_message, $redirect;
    ZM\Monitor::$result = $result;
    ZM\Monitor::$sent = array();
    $action = 'control';
    $error_message = '';
    $redirect = null;
    $_REQUEST = array('mid' => '3', 'action' => 'control', 'control' => 'moveConUp');
    include $action_file;
    return array($error_message, $redirect);
  }

  list($err, $redir) = run(true);
  check('a command that works goes back to the control page', $redir, '?view=control&mid=3');
  check('a command that works reports nothing', $err, '');
  list($err, $redir) = run(false);
  check('a failed command is reported', strpos($err, 'Failed to send the control command') !== false, true);
  check('a failed command does not redirect as if it worked', $redir, null);

  @unlink($stub.'/includes/control_functions.php'); @unlink($stub.'/includes/Monitor.php');
  @rmdir($stub.'/includes'); @rmdir($stub);
  echo $failures ? "\n$failures failed\n" : "\nall passed\n";
  exit($failures ? 1 : 0);
}
