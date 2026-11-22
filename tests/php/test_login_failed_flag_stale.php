<?php
// Tests web/skins/classic/views/js/login.js.php: the "Invalid username or
// password" box is shown only on the response to a failed login post. The
// loginFailed flag stays in the session, so the box used to reappear on every
// later visit to the login page, before any attempt had been made.
//
// Run: php tests/php/test_login_failed_flag_stale.php <web root>

$root = $argv[1] ?? __DIR__.'/../../web';
$file = $root.'/skins/classic/views/js/login.js.php';

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

function render($file, $session, $post) {
  $_SESSION = $session;
  $_POST = $post;
  ob_start();
  include $file;
  return trim(ob_get_clean());
}

check('failed post shows the error', render($file, array('loginFailed' => true), array('action' => 'login')), 'var failed = true;');
check('later visit does not show the error', render($file, array('loginFailed' => true), array()), 'var failed = false;');
check('no failure, no error', render($file, array(), array('action' => 'login')), 'var failed = false;');

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
