<?php
namespace ZM {
  function Warning($m) {}
  function AuditAction($action, $object, $id, $text) {}
}

namespace {
  $queries = array();
  function canEdit($area) { return true; }
  function dbQuery($sql, $params = null) { global $queries; $queries[] = array($sql, $params); return true; }
  function userLogout() {}

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
    global $queries, $action, $user, $redirect;
    $queries = array();
    $action = 'delete';
    $user = new StubUser();
    $_REQUEST = array('markUids' => $ids);
    include __DIR__.'/../../web/includes/actions/options.php';
    return $queries;
  }

  $q = run_delete(array('7', '8'));
  check('preferences removed before each user', array_map(function($x) { return $x[0].'|'.$x[1][0]; }, $q), array(
    'DELETE FROM User_Preferences WHERE UserId = ?|7',
    'DELETE FROM Users WHERE Id = ?|7',
    'DELETE FROM User_Preferences WHERE UserId = ?|8',
    'DELETE FROM Users WHERE Id = ?|8',
  ));

  echo "$passed passed, $failed failed\n";
  exit($failed ? 1 : 0);
}
