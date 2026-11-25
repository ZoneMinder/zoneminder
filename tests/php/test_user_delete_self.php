<?php
// Deleting users from Options > Users: the logout check looked only at the last marked
// user (the loop variable left after foreach), so marking your own account together with
// a user listed after it deleted your account without logging you out.
//
// Run as: php tests/php/test_user_delete_self.php
namespace ZM {
  function Warning($m) {}
  function AuditAction($action, $object, $id, $text) {}
}

namespace {
  chdir(__DIR__.'/../../web');
  $logouts = 0;
  function canEdit($area) { return true; }
  function dbQuery($sql, $params = null) { return true; }
  function userLogout() { global $logouts; $logouts++; }

  class StubUser {
    public function Id() { return 1; }
  }

  $passed = 0;
  $failed = 0;
  function check($label, $got, $want) {
    global $passed, $failed;
    if ($got === $want) {
      $passed++;
      echo "  ok $label\n";
    } else {
      $failed++;
      echo "  FAIL $label\n       got:  ".var_export($got, true)."\n       want: ".var_export($want, true)."\n";
    }
  }

  function run_delete($ids) {
    global $logouts, $action, $user, $redirect;
    $logouts = 0;
    $redirect = null;
    $action = 'delete';
    $user = new StubUser();
    $_REQUEST = array('markUids' => $ids);
    include __DIR__.'/../../web/includes/actions/options.php';
    return array($logouts, $redirect);
  }

  check('own account marked last logs out', run_delete(array('8', '1')), array(1, '?view=login'));
  check('own account marked first logs out', run_delete(array('1', '8')), array(1, '?view=login'));
  check('other users only stays on the tab', run_delete(array('7', '8')), array(0, '?view=options&tab=users'));

  echo "$passed passed, $failed failed\n";
  exit($failed ? 1 : 0);
}
