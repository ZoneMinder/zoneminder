<?php
// userFromSession() ends a session whose password has changed since login: the
// session keeps a copy of the password hash and it must match the database row.
// Sessions that predate that check (no passwordHash) still load the user.
//
// auth.php can't be included (it needs a database), so userFromSession() is
// taken from the source and run against stubs.
// Run as: php tests/php/test_session_password_change.php .

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

$root = realpath($argv[1] ?? __DIR__.'/../..');
$source = file_get_contents($root.'/web/includes/auth.php');
$start = strpos($source, 'function userFromSession()');
$open = strpos($source, '{', $start);
$depth = 0;
for ($i = $open; $i < strlen($source); $i++) {
  if ($source[$i] == '{') $depth++;
  else if ($source[$i] == '}' && --$depth == 0) break;
}
if ($start === false || $open === false) {
  echo "FAIL - userFromSession() not found in auth.php\n";
  exit(1);
}
$method = substr($source, $start, $i - $start + 1);

define('ZM_AUTH_HASH_LOGINS', false);
define('ZM_AUTH_RELAY', 'none');
define('ZM_CASE_INSENSITIVE_USERNAMES', false);

function dbFetchOne($sql, $col = null, $params = array()) {
  global $dbRow;
  return $dbRow;
}
function authHashSessionAddrs() {
  return array();
}

eval('namespace ZM;
function Debug($m) {}
function Info($m) {}
class User {
  private $row;
  function __construct($row) { $this->row = $row ?: array(); }
  function Id() { return $this->row[\'Id\'] ?? null; }
  function Password() { return $this->row[\'Password\'] ?? null; }
  function Username() { return $this->row[\'Username\'] ?? null; }
}');

eval($method);

// (1) Password unchanged -> the session's user comes back.
$_SESSION = array('username' => 'bob', 'passwordHash' => 'h1');
$dbRow = array('Id' => 5, 'Username' => 'bob', 'Password' => 'h1');
$u = userFromSession();
check('session with the matching password gets its user', $u !== null && $u->Id() === 5, true);

// (2) Password was reset -> the session ends.
$_SESSION = array('username' => 'bob', 'passwordHash' => 'h1');
$dbRow = array('Id' => 5, 'Username' => 'bob', 'Password' => 'h2');
$u = userFromSession();
check('session whose password changed returns null', $u, null);

// (3) Old session without passwordHash (or remote auth) -> user still loads.
$_SESSION = array('username' => 'bob');
$dbRow = array('Id' => 5, 'Username' => 'bob', 'Password' => 'h2');
$u = userFromSession();
check('session without passwordHash gets its user', $u !== null && $u->Id() === 5, true);

// (4) The user row is gone (missing or disabled) -> no user, and no throw.
$_SESSION = array('username' => 'bob', 'passwordHash' => 'h1');
$dbRow = false;
$threw = false;
try {
  $u = userFromSession();
} catch (Throwable $e) {
  $threw = true;
  $u = null;
}
check('missing user row does not throw', $threw, false);
check('missing user row returns no user', $u === null || $u->Id() === null, true);

// (5) No session username -> no user.
$_SESSION = array();
$dbRow = array('Id' => 5, 'Username' => 'bob', 'Password' => 'h1');
check('session without a username returns null', userFromSession(), null);

echo $failures ? "$failures failed\n" : "all passed\n";
exit($failures ? 1 : 0);
