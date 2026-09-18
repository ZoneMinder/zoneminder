<?php
// Tests requestString() in web/includes/auth.php: the request parameters the
// auth path reads have to be strings before anything does string work on them.
//
// The reported failure was a fatal, not a wrong answer:
//
//   PHP Fatal error: Uncaught TypeError: strcasecmp(): Argument #1 ($string1)
//   must be of type string, array given in includes/auth.php:197
//   #0 auth.php(197): strcasecmp()
//   #1 auth.php(528): getAuthUser()
//   #2 auth.php(687): userFromSession()
//
// reached from ?view=user&uid=2. That page's form posts user[Username],
// user[Password] and the rest, so $_REQUEST['user'] is an array on every save,
// and getAuthUser() handed it straight to strcasecmp(). The same shape arrives
// from anyone who asks for it: ?username[]=x&password[]=y reaches the login
// path with no session at all.
//
// Run: php tests/php/test_auth_request_string.php
//
// requestString() is pure, but including auth.php requires a database --
// User.php pulls in database.php, which connects at include time -- so the
// function is lifted out of the file and evaluated on its own, the same
// dodge test_auth_no_include_side_effects.php makes for the same reason.
$auth_src = file_get_contents(__DIR__.'/../../web/includes/auth.php');
if ($auth_src === false) {
  echo "FAIL could not read auth.php\n";
  exit(1);
}
if (!preg_match('/^function requestString\(.*?^}$/ms', $auth_src, $matches)) {
  echo "FAIL requestString() not found in auth.php\n";
  exit(1);
}
eval($matches[0]);

$failures = 0;
$passes = 0;

function check($name, $got, $expected) {
  global $failures, $passes;
  $got_str = var_export($got, true);
  $expected_str = var_export($expected, true);
  if ($got === $expected) {
    $passes++;
    echo "ok   $name\n";
  } else {
    $failures++;
    echo "FAIL $name: expected $expected_str, got $got_str\n";
  }
}

// A parameter the client did send, as a string, comes back unchanged.
$_REQUEST = array('user' => 'admin');
check('string passes through', requestString('user'), 'admin');

// The empty string is still a string: callers decide what empty means, and the
// login paths already test for it with empty().
$_REQUEST = array('user' => '');
check('empty string is kept', requestString('user'), '');

// A parameter that was never sent.
$_REQUEST = array();
check('missing parameter is null', requestString('user'), null);

// The shape the user edit form posts. This is the reported crash.
$_REQUEST = array('user' => array('Username' => 'admin', 'Password' => 'secret'));
check('form array is refused', requestString('user'), null);

// The shape an attacker sends, on the paths that need no session.
$_REQUEST = array('username' => array('admin'), 'password' => array('x'));
check('username array is refused', requestString('username'), null);
check('password array is refused', requestString('password'), null);

// An auth hash is used to look up a user and compared against the session copy.
$_REQUEST = array('auth' => array('deadbeef'));
check('auth array is refused', requestString('auth'), null);

// Anything else PHP can put in a request parameter.
$_REQUEST = array('user' => array(array('nested')));
check('nested array is refused', requestString('user'), null);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
