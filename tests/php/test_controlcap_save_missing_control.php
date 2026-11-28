<?php
// Saving a control capability whose cid no longer exists (deleted in another tab) built an
// empty Control from the failed load and saved it, so a new capability was inserted with the
// posted values and the save was audited and redirected as an update. It has to refuse and
// report that the capability is gone, while an existing one and a new one still save.
//
// Runs the real web/includes/actions/controlcap.php with a stubbed Control.
//
// php tests/php/test_controlcap_save_missing_control.php [root]   (root defaults to the repo)

namespace ZM {
  function Warning($s) {}
  function AuditAction($a = null, $t = null, $id = null, $n = null) {
    $GLOBALS['audits'][] = array($a, $t, $id);
  }
  class Control {
    private $id = null;
    // Control 4 is the only one that exists; any other id fails to load, as ZM_Object does.
    function __construct($id = null) { if ($id and (int)$id === 4) $this->id = 4; }
    function save($v) {
      $GLOBALS['saves'][] = $this->id === null ? 'insert' : 'update '.$this->id;
      if ($this->id === null) $this->id = 60;
      return true;
    }
    function Id() { return $this->id; }
    function Name() { return 'x'; }
    function get_last_error() { return ''; }
  }
}

namespace {
  function canEdit($a) { return true; }
  // Copied from web/includes/functions.php.
  function validCardinal($input) {
    if (!is_scalar($input)) return '';
    return preg_replace('/\D/', '', $input);
  }

  $root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
  $stub = sys_get_temp_dir().'/zm_ccmissing_'.getmypid();
  @mkdir($stub.'/includes', 0700, true);
  file_put_contents($stub.'/includes/Control.php', "<?php\n");
  set_include_path($stub.PATH_SEPARATOR.get_include_path());

  $failures = 0;
  function check($n, $got, $want) {
    global $failures;
    if ($got === $want) echo "ok - $n\n";
    else { $failures++; echo "FAIL - $n (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
  }

  function run($request) {
    global $root, $action, $error_message, $redirect;
    $GLOBALS['saves'] = array();
    $GLOBALS['audits'] = array();
    $action = 'Save';
    $error_message = '';
    $redirect = null;
    $_REQUEST = $request + array('action' => 'Save', 'Control' => array('Name' => 'Pelco D'));
    include $root.'/web/includes/actions/controlcap.php';
    return array($GLOBALS['saves'], $error_message, $redirect);
  }

  list($saves, $err, $redir) = run(array('cid' => '9'));
  check('a deleted capability is not inserted again', $saves, array());
  check('a deleted capability is not audited', $GLOBALS['audits'], array());
  check('a deleted capability reports that it is gone', $err, 'Control capability not found.</br>');
  check('a deleted capability does not redirect', $redir, null);

  list($saves, $err, $redir) = run(array('cid' => '4'));
  check('an existing capability is updated', $saves, array('update 4'));
  check('an existing capability reports no error', $err, '');
  check('an existing capability goes back to the list', $redir, '?view=options&tab=control');

  list($saves, $err, $redir) = run(array());
  check('a new capability is inserted', $saves, array('insert'));
  check('a new capability is audited as a create', $GLOBALS['audits'], array(array('create', 'control', 60)) );
  check('a new capability reports no error', $err, '');

  @unlink($stub.'/includes/Control.php'); @rmdir($stub.'/includes'); @rmdir($stub);
  echo $failures ? "\n$failures failed\n" : "\nall passed\n";
  exit($failures ? 1 : 0);
}
