<?php
// Tests the age bookkeeping of the auth hash cache slots in generateAuthHash()
// in web/includes/auth.php.
//
// generateAuthHash() caches one hash per address slot ('AuthHash'.$remoteAddr).
// Each slot must also keep its own timestamp: if the IP-less call (the one zmu
// makes) refreshed a single shared timestamp, it would keep the browser's
// IP-bound slot looking young forever, and a poll would be served a hash older
// than ZM_AUTH_HASH_TTL. That hash no longer validates, and the browser is
// bounced to the login page.
//
// Run: php tests/php/test_auth_hash_slot_age.php [path/to/auth.php]
//
// auth.php can't be included without a database, so generateAuthHash() is
// lifted out of the file and evaluated with stand-ins for what it calls. The
// clock is controlled by rewriting its time() call to read $GLOBALS['now'].
//
// With ZM_AUTH_HASH_TTL=2 the freshness limit is 2*1800 = 3600s.

$failures = 0;
$passes = 0;

function check($name, $got, $expected) {
  global $failures, $passes;
  if ($got === $expected) {
    $passes++;
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "not ok - $name\n     got:      ".var_export($got, true)."\n     expected: ".var_export($expected, true)."\n";
  }
}

$auth_file = isset($argv[1]) ? $argv[1] : __DIR__.'/../../web/includes/auth.php';
$auth_src = @file_get_contents($auth_file);
if ($auth_src === false) {
  echo "not ok - cannot read $auth_file\n";
  exit(1);
}
if (!preg_match('/^function generateAuthHash\(.*?^}$/ms', $auth_src, $matches)) {
  echo "not ok - generateAuthHash() not found in $auth_file\n";
  exit(1);
}

// generateAuthHash() is not defined yet, so rewrite the clock call before eval.
$fn_src = str_replace('time()', "\$GLOBALS['now']", $matches[0]);
check('the clock call is rewritten to $GLOBALS[\'now\']', strpos($fn_src, 'time()'), false);

define('ZM_OPT_USE_AUTH', true);
define('ZM_AUTH_RELAY', 'hashed');
define('ZM_AUTH_HASH_TTL', 2);
define('ZM_AUTH_HASH_IPS', true);

$counter = 0;
function calculateAuthHash($remoteAddr = '') {
  global $counter;
  return 'hash:'.$remoteAddr.':'.(++$counter);
}

class FakeUser {
  public function Username() { return 'bob'; }
}
$user = new FakeUser();

eval($fn_src);

if (!function_exists('generateAuthHash')) {
  echo "not ok - generateAuthHash() did not evaluate\n";
  exit(1);
}

// The IP slot is minted, then a repeat inside the TTL is served from cache.
$_SESSION = array('remoteAddr' => '1.2.3.4');
$GLOBALS['now'] = 1000000;
$A = generateAuthHash(true);

$GLOBALS['now'] = 1000100;
check('a repeat call inside the TTL returns the cached IP-bound hash', generateAuthHash(true), $A);

// The IP-less call (zmu) lands in its own slot and must not refresh the age of
// the IP-bound slot the browser uses.
$GLOBALS['now'] = 1002000;
$ipLess = generateAuthHash(false);
check('the IP-less call mints its own hash', $ipLess, 'hash::2');
check('the IP-less call leaves the IP-bound slot alone', $_SESSION['AuthHash1.2.3.4'], $A);

// 4000s after it was minted the IP-bound slot is older than the 3600s limit,
// even though the IP-less call was made 2000s ago, so it must be regenerated.
$GLOBALS['now'] = 1004000;
$B = generateAuthHash(true);
check('the IP-bound slot older than the TTL is regenerated', $B !== $A, true);

// A slot that has a hash but no timestamp (written by an older version) must
// not raise an undefined-key warning, and must be regenerated.
$_SESSION = array('remoteAddr' => '1.2.3.4', 'AuthHash1.2.3.4' => 'stale');
$GLOBALS['now'] = 2000000;
$warnings = 0;
set_error_handler(function ($errno, $errstr) use (&$warnings) {
  if ($errno & (E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE | E_DEPRECATED)) {
    $warnings++;
  }
  return true;
});
$result = generateAuthHash(true);
restore_error_handler();
check('a slot with a hash but no timestamp does not raise a warning', $warnings, 0);
check('a slot with a hash but no timestamp is regenerated', $result !== 'stale', true);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
