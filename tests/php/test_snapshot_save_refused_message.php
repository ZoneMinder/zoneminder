<?php
// Saving a snapshot you may not edit (no Events edit, not the creator) was refused silently, and
// the Save button was still shown. The action now sets $error_message and the view disables Save
// for those users.
//
// Includes the real action file with the web helpers stubbed; the views are checked as source.
//
// Run as: php tests/php/test_snapshot_save_refused_message.php [web root]

namespace ZM {
  function Warning($s) {}
  function Error($s) {}
  function Debug($s) {}
  function AuditAction($a, $b, $c, $d) {}
  class Snapshot {
    function __construct($id = null) {}
    function CreatedBy() { return 9; }
    function Id() { return 5; }
    function changes($new) { return array('Name' => 'x'); }
    function save($c) { $GLOBALS['done'][] = 'save'; return true; }
  }
}

namespace {
  $root = isset($argv[1]) ? rtrim($argv[1], '/') : __DIR__.'/../../web';

  $stubdir = sys_get_temp_dir().'/zm_snapshot_save_stubs_'.getmypid();
  @mkdir($stubdir.'/includes', 0700, true);
  foreach (array('Snapshot', 'Monitor') as $f) file_put_contents($stubdir.'/includes/'.$f.'.php', "<?php\n");
  set_include_path($stubdir.PATH_SEPARATOR.get_include_path());

  class StubUser { function Id() { return 2; } }
  $user = new StubUser();
  function canEdit($area, $mid = false) { return ($GLOBALS['perms'][$area] ?? 'None') === 'Edit'; }
  function validCardinal($v) { return preg_replace('/\D/', '', $v); }

  $failures = 0;
  function check($name, $got, $want) {
    global $failures;
    if ($got === $want) echo "ok - $name\n";
    else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
  }

  function save($perms) {
    global $root, $error_message, $user;
    $GLOBALS['perms'] = $perms;
    $GLOBALS['done'] = array();
    $error_message = null;
    $_REQUEST = array('view' => 'snapshot', 'action' => 'save', 'id' => '5', 'snapshot' => array('Name' => 'x'));
    $action = 'save';
    $redirect = null;
    include $root.'/includes/actions/snapshot.php';
    return array($GLOBALS['done'], $redirect, $error_message !== null && $error_message !== '');
  }

  // The snapshot was created by user 9, the current user is 2.
  check('Snapshots edit only, not the creator: refused with a message',
    save(array('Snapshots' => 'Edit')), array(array(), null, true));
  check('Events edit: saved, no message',
    save(array('Events' => 'Edit')), array(array('save'), '?view=snapshots', false));

  $view = file_get_contents($root.'/skins/classic/views/snapshot.php');
  check('snapshot view disables Save for users who cannot save',
    (bool)preg_match('/id="saveBtn".*canEdit\(\'Events\'\).*disabled/', $view), true);

  @unlink($stubdir.'/includes/Snapshot.php');
  @unlink($stubdir.'/includes/Monitor.php');
  @rmdir($stubdir.'/includes');
  @rmdir($stubdir);
  exit($failures ? 1 : 0);
}
