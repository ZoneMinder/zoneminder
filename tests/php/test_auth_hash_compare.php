<?php
// Tests the auth hash comparison in getAuthUser() (web/includes/auth.php).
//
// The hash a client presents (auth=) was compared to the one the server builds
// with ==. Two strings that both look like numbers compare as numbers under ==,
// so a computed md5 of the form 0e<digits> would have equalled any other
// 0e<digits> string, and == also stops at the first differing byte.
// hash_equals() compares the bytes, in constant time.
//
// Run: php tests/php/test_auth_hash_compare.php
//
// Including auth.php needs a database (User.php pulls in database.php), so
// getAuthUser() is lifted out of the file, as test_auth_request_string.php
// does, and evaluated in a namespace where its collaborators are stubs. md5()
// is stubbed there too, so a test can make the server's hash a 0e<digits>
// string without searching for one.

namespace AuthHashCompareTest\ZM {
  class User {
    public $row;
    public function __construct($row) { $this->row = $row; }
  }
  function Debug($s) {}
  function Info($s) {}
  function Error($s) {}
}

namespace AuthHashCompareTest {
  const ZM_OPT_USE_AUTH = true;
  const ZM_AUTH_RELAY = 'hashed';
  const ZM_AUTH_HASH_IPS = false;
  const ZM_AUTH_HASH_TTL = 2;
  const ZM_AUTH_HASH_SECRET = 'test-secret';
  const ZM_CASE_INSENSITIVE_USERNAMES = false;

  $GLOBALS['md5_override'] = null;
  $GLOBALS['rows'] = array();      // rows for the query filtered by username
  $GLOBALS['alt_rows'] = array();  // rows for the multi-server fallback query

  function md5($s) {
    return $GLOBALS['md5_override'] !== null ? $GLOBALS['md5_override'] : \md5($s);
  }
  function getRemoteAddr() { return ''; }
  function authHashSessionAddrs() { return array(''); }
  function requestString($key) {
    return (isset($_REQUEST[$key]) && is_string($_REQUEST[$key])) ? $_REQUEST[$key] : null;
  }
  function dbFetchAll($sql, $col = null, $values = array()) {
    return (strpos($sql, '!=') !== false) ? $GLOBALS['alt_rows'] : $GLOBALS['rows'];
  }
}

namespace {
  $auth_src = file_get_contents(__DIR__.'/../../web/includes/auth.php');
  if ($auth_src === false) {
    echo "FAIL could not read auth.php\n";
    exit(1);
  }
  if (!preg_match('/^function getAuthUser\(.*?^}/ms', $auth_src, $matches)) {
    echo "FAIL getAuthUser() not found in auth.php\n";
    exit(1);
  }
  eval('namespace AuthHashCompareTest; '.$matches[0]);

  $failures = 0;
  $passes = 0;

  function check($name, $ok) {
    global $failures, $passes;
    if ($ok) {
      $passes++;
      echo "ok   $name\n";
    } else {
      $failures++;
      echo "FAIL $name\n";
    }
  }

  // The hash the server builds for $row in the current hour, as getAuthUser() does.
  function current_hash($row) {
    $time = localtime(time());
    return md5('test-secret'.$row['Username'].$row['Password'].''.$time[2].$time[3].$time[4].$time[5]);
  }

  $alice = array('Id' => 2, 'Username' => 'alice', 'Password' => '$2y$10$abcdefghijklmnopqrstuv');
  $auth = 'AuthHashCompareTest\getAuthUser';

  // Primary loop: the request names the user.
  $_REQUEST = array('user' => 'alice');
  $GLOBALS['rows'] = array($alice);
  $GLOBALS['alt_rows'] = array();

  $got = $auth(current_hash($alice));
  check('the right hash logs the user in', $got !== null && $got->row['Username'] === 'alice');
  check('a wrong hash is refused', $auth(str_repeat('a', 32)) === null);
  check('the right hash in upper case is refused, as before', $auth(strtoupper(current_hash($alice))) === null);

  // Two different 0e<digits> strings are equal under ==.
  $GLOBALS['md5_override'] = '0e462097431906509019562988736854';
  check('a different 0e<digits> string is refused', $auth('0e830400451993494058024219903391') === null);
  check('"00" is refused against a 0e<digits> hash', $auth('00') === null);
  check('the exact 0e<digits> hash still matches', $auth('0e462097431906509019562988736854') !== null);

  // Fallback loop: the hash belongs to another user than the one named.
  $GLOBALS['rows'] = array();
  $GLOBALS['alt_rows'] = array($alice);
  check('fallback: a different 0e<digits> string is refused', $auth('0e830400451993494058024219903391') === null);
  check('fallback: the exact hash still matches', $auth('0e462097431906509019562988736854') !== null);
  $GLOBALS['md5_override'] = null;
  check('fallback: the right hash logs the user in', $auth(current_hash($alice)) !== null);

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
