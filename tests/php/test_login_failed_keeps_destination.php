<?php
// Tests web/includes/actions/login.php: a failed login keeps the saved
// post-login destination in the session, so that a retry with the right
// password still lands on the page that was asked for. The action used to
// drop $_SESSION['postLoginQuery'] on every attempt, and the login form posts
// to ?view=login (no postLoginQuery in the URL), so the retried form carried an
// empty destination and the user ended up on the home view.
//
// Run: php tests/php/test_login_failed_keeps_destination.php

define('ZM_AUTH_TYPE', 'builtin');
define('ZM_OPT_USE_REMEMBER_ME', 'None');
function zm_session_start() {}

$failures = 0;
function check($name, $got, $expected) {
  global $failures;
  if ($got === $expected) {
    echo "ok   $name\n";
  } else {
    $failures++;
    echo "FAIL $name: expected ".var_export($expected, true).', got '.var_export($got, true)."\n";
  }
}

function runLogin($loggedIn) {
  global $action, $user, $view, $defaultPasswordChange;
  $_SESSION = array('postLoginQuery' => 'view=event&eid=5');
  $_REQUEST = array('username' => 'bob', 'password' => 'pw');
  $action = 'login';
  $view = 'login';
  $defaultPasswordChange = null;
  unset($user);
  if ($loggedIn) $user = new stdClass();
  include __DIR__.'/../../web/includes/actions/login.php';
  return $_SESSION;
}

$s = runLogin(false);
check('failed login flags the failure', !empty($s['loginFailed']), true);
check('failed login keeps the destination', $s['postLoginQuery'] ?? null, 'view=event&eid=5');

$s = runLogin(true);
check('good login goes to postlogin', $GLOBALS['view'], 'postlogin');
check('good login clears the destination', isset($s['postLoginQuery']), false);

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
