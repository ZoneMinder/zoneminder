<?php
// Tests the stored-password comparisons in validateUser()
// (web/includes/auth.php) for the two password formats it compares itself:
// a plain-text Password column and a MySQL PASSWORD() hash. bcrypt goes
// through password_verify() and isn't touched here.
//
// Both used ==. Two strings that look like numbers compare as numbers under
// ==, so a plain-text password stored as "100" also accepted "1e2", "100.0"
// and " 100". hash_equals() compares the bytes, in constant time.
//
// Run: php tests/php/test_validate_user_compare.php
//
// Including auth.php needs a database, so validateUser() and the functions and
// constants it calls (password_type(), isDefaultPassword(), ZM_DEFAULT_PASSWORD*)
// are lifted out of the file, as test_auth_request_string.php does, and
// evaluated in a namespace where the database and logger are stubs. Whatever
// validateUser() starts calling must be lifted here too.

namespace ValidateUserTest\ZM {
  class User {
    public $row;
    public function __construct($row) { $this->row = $row; }
  }
  function Debug($s) {}
  function Warning($s) {}
}

namespace ValidateUserTest {
  const ZM_CASE_INSENSITIVE_USERNAMES = false;
  $GLOBALS['row'] = null;
  function dbFetchOne($sql, $col = null, $values = array()) { return $GLOBALS['row']; }
}

namespace {
  $auth_src = file_get_contents(__DIR__.'/../../web/includes/auth.php');
  if ($auth_src === false) {
    echo "FAIL could not read auth.php\n";
    exit(1);
  }
  foreach (array('ZM_DEFAULT_PASSWORD', 'ZM_DEFAULT_PASSWORD_REFUSED') as $const) {
    if (!preg_match("/^define\\('$const', .*?\\);$/m", $auth_src, $matches)) {
      echo "FAIL $const not found in auth.php\n";
      exit(1);
    }
    eval($matches[0]);
  }
  foreach (array('password_type', 'isDefaultPassword', 'validateUser') as $fn) {
    if (!preg_match('/^function '.$fn.'\(.*?^}/ms', $auth_src, $matches)) {
      echo "FAIL $fn() not found in auth.php\n";
      exit(1);
    }
    eval('namespace ValidateUserTest; '.$matches[0]);
  }

  $failures = 0;
  $passes = 0;

  function check($name, $got, $expected) {
    global $failures, $passes;
    if ($got === $expected) {
      $passes++;
      echo "ok   $name\n";
    } else {
      $failures++;
      echo "FAIL $name: expected ".var_export($expected, true).', got '.var_export($got, true)."\n";
    }
  }

  function accepts($stored, $typed) {
    $GLOBALS['row'] = array('Id' => 3, 'Username' => 'bob', 'Password' => $stored);
    $ret = ValidateUserTest\validateUser('bob', $typed);
    return $ret[0] !== false;
  }

  // Plain text in the Password column.
  check('plain: the stored password is accepted', accepts('100', '100'), true);
  check('plain: a different password is refused', accepts('100', '101'), false);
  check('plain: "1e2" is refused for "100"', accepts('100', '1e2'), false);
  check('plain: "100.0" is refused for "100"', accepts('100', '100.0'), false);
  check('plain: " 100" is refused for "100"', accepts('100', ' 100'), false);
  check('plain: "0e1" is refused for "0e2"', accepts('0e2', '0e1'), false);
  check('plain: an array is refused', accepts('100', array('100')), false);

  // MySQL PASSWORD(): '*' followed by 40 upper-case hex digits.
  $mysql = '*'.strtoupper(sha1(sha1('secret', true)));
  check('mysql: the right password is accepted', accepts($mysql, 'secret'), true);
  check('mysql: a wrong password is refused', accepts($mysql, 'Secret'), false);

  // bcrypt is untouched; checked so a regression in the switch shows up here.
  $bcrypt = password_hash('secret', PASSWORD_BCRYPT);
  check('bcrypt: the right password is accepted', accepts($bcrypt, 'secret'), true);
  check('bcrypt: a wrong password is refused', accepts($bcrypt, 'nope'), false);

  // The documented default password is refused even when it matches, with
  // its own message (the login page then asks for a new one).
  $GLOBALS['row'] = array('Id' => 1, 'Username' => 'admin', 'Password' => 'admin');
  $ret = ValidateUserTest\validateUser('admin', 'admin');
  check('default: plain "admin" is refused', $ret[0], false);
  check('default: with its own message', $ret[1], ZM_DEFAULT_PASSWORD_REFUSED);
  $GLOBALS['row'] = array('Id' => 1, 'Username' => 'admin', 'Password' => '*'.strtoupper(sha1(sha1('admin', true))));
  check('default: MySQL-hashed "admin" is refused', ValidateUserTest\validateUser('admin', 'admin')[1],
        ZM_DEFAULT_PASSWORD_REFUSED);
  check('default: a wrong password still reads as one', ValidateUserTest\validateUser('admin', 'nope')[1],
        'Invalid username or password');

  // No such user.
  $GLOBALS['row'] = null;
  check('unknown user is refused', ValidateUserTest\validateUser('nobody', 'x')[0], false);

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
