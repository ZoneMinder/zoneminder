<?php
// Deleting users (Options > Users > Delete) ignored the result of its DELETE queries and
// redirected to the user list as if it had worked, and logged out the current user
// even when their own row was not deleted. A failed delete now shows its error.
//
// Run as: php tests/php/test_options_user_delete_result.php [root]   (root defaults to the repo)

function canEdit($a) { return true; }
function dbLastError() { return 'db down'; }
function validHtmlStr($v) { return htmlspecialchars((string)$v); }
function userLogout() { $GLOBALS['loggedOut'] = true; }
function dbQuery($sql, $values = array()) {
  $GLOBALS['queries'][] = trim($sql);
  return $GLOBALS['dbResult'];
}
eval('namespace ZM; function Warning($m) {} function Error($m) {} function AuditAction() {}');

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
chdir($root.'/web');
// options.php only needs this for the options/save branches
if (!file_exists('includes/config_values.php')) { echo "no config_values.php in $root\n"; exit(2); }
function config_stub() {}

$failures = 0;
function check($n, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $n\n";
  else { $failures++; echo "FAIL - $n (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

class FakeUser { function Id() { return 1; } }

function delUsers($ids, $dbResult) {
  global $error_message, $redirect, $action, $user, $root;
  $error_message = '';
  $redirect = null;
  $action = 'delete';
  $user = new FakeUser();
  $GLOBALS['loggedOut'] = false;
  $GLOBALS['queries'] = array();
  $GLOBALS['dbResult'] = $dbResult;
  $_REQUEST = array('action' => 'delete', 'markUids' => $ids);
  include $root.'/web/includes/actions/options.php';
  return array($error_message, $redirect, $GLOBALS['loggedOut']);
}

list($err, $redir, $out) = delUsers(array('5'), true);
check('a delete that works redirects to the users tab', $redir, '?view=options&tab=users');
check('a delete that works reports nothing', $err, '');

list($err, $redir, $out) = delUsers(array('5'), null);
check('a failed delete is reported', strpos($err, 'Error deleting user') !== false, true);
check('a failed delete does not redirect', $redir, null);

list($err, $redir, $out) = delUsers(array('1'), true);
check('deleting yourself logs out', $out, true);

list($err, $redir, $out) = delUsers(array('1'), null);
check('a failed self delete does not log out', $out, false);
check('a failed self delete does not redirect to login', $redir, null);

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
