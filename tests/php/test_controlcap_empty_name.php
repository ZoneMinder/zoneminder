<?php
// Saving a control capability with an empty Name stored '' (ZM_Object::set keeps '' for string
// defaults), leaving a blank row in the control list and the monitor Control dropdown. A blank
// Name is now refused with an error and nothing is saved.
//
// php tests/php/test_controlcap_empty_name.php [root]   (root defaults to the repo)

namespace ZM {
  function Warning($s) {}
  function AuditAction() {}
  class Control {
    static $saved = 0;
    function __construct($id = null) {}
    function save($v) { self::$saved++; return true; }
    function Id() { return 1; }
    function Name() { return 'x'; }
    function get_last_error() { return ''; }
  }
}

namespace {
  function canEdit($a) { return true; }

  $root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
  $stub = sys_get_temp_dir().'/zm_ccname_'.getmypid();
  @mkdir($stub.'/includes', 0700, true);
  file_put_contents($stub.'/includes/Control.php', "<?php\n");
  set_include_path($stub.PATH_SEPARATOR.get_include_path());

  $failures = 0;
  function check($n, $got, $want) {
    global $failures;
    if ($got === $want) echo "ok - $n\n";
    else { $failures++; echo "FAIL - $n (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
  }

  function run($name) {
    global $root, $action, $error_message, $redirect;
    ZM\Control::$saved = 0;
    $action = 'Save';
    $error_message = '';
    $redirect = null;
    $_REQUEST = array('action' => 'Save', 'Control' => array('Name' => $name));
    include $root.'/web/includes/actions/controlcap.php';
    return array(ZM\Control::$saved, $error_message, $redirect);
  }

  list($saved, $err, $redir) = run('Pelco D');
  check('a named control is saved', $saved, 1);
  check('a named control goes back to the list', $redir, '?view=options&tab=control');
  foreach (array('', '   ') as $blank) {
    list($saved, $err, $redir) = run($blank);
    check('blank name "'.$blank.'" is not saved', $saved, 0);
    check('blank name "'.$blank.'" reports an error', $err !== '', true);
    check('blank name "'.$blank.'" does not redirect', $redir, null);
  }

  @unlink($stub.'/includes/Control.php'); @rmdir($stub.'/includes'); @rmdir($stub);
  echo $failures ? "\n$failures failed\n" : "\nall passed\n";
  exit($failures ? 1 : 0);
}
