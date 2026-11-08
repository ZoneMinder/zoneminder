<?php
// Tests zmuCredentials() in web/includes/auth.php: what the web UI and the API
// put on zmu's command line to authenticate it.
//
// A command line is visible to every local user (ps, /proc/<pid>/cmdline) and
// lands in debug logs. zmu used to get the user's plaintext password there with
// ZM_AUTH_RELAY=plain, and with the default hashed relay an auth hash, which
// logs in to the web UI as auth= for ZM_AUTH_HASH_TTL hours. With a usable
// ZM_AUTH_HASH_SECRET it now gets an access token that lives a minute.
//
// Run: php tests/php/test_zmu_credentials.php
//
// auth.php can't be included without a database (see
// test_auth_no_include_side_effects.php), so the function is lifted out of the
// file and evaluated with stand-ins for what it calls.

require_once __DIR__.'/../../web/vendor/autoload.php';

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

$auth_src = file_get_contents(__DIR__.'/../../web/includes/auth.php');
if (!preg_match('/^function zmuCredentials\(.*?^}$/ms', $auth_src, $matches)) {
  echo "not ok - zmuCredentials() not found in auth.php\n";
  exit(1);
}

define('ZM_OPT_USE_AUTH', true);
define('ZM_AUTH_RELAY', 'plain');
define('ZM_AUTH_HASH_SECRET', 'zmu-credentials-test-secret');

$secretIsSet = true;
function authHashSecretIsSet() { global $secretIsSet; return $secretIsSet; }
function generateAuthHash($useRemoteAddr, $force = false) { return 'authhash'; }

class FakeUser {
  public function Username() { return 'admin'; }
}
$user = new FakeUser();
$password = 'correct horse';
$_SESSION = array('username' => 'admin', 'password' => $password);

eval($matches[0]);

$args = zmuCredentials();
check('with a secret: a token and nothing else', preg_match("/^ -T '[^']+'$/", $args), 1);
check('with a secret: not the password, though the session has it', strpos($args, $password), false);
preg_match("/^ -T '([^']+)'$/", $args, $m);
$claims = (array)\Firebase\JWT\JWT::decode($m[1], ZM_AUTH_HASH_SECRET, array('HS256'));
check('an access token for the user', array($claims['user'], $claims['type']), array('admin', 'access'));
check('that lives a minute', $claims['exp'] - $claims['iat'], 60);

// zmu refuses tokens without a usable secret, so then it gets what it did before.
$secretIsSet = false;
check('without a secret, plain relay: user and password from the session', zmuCredentials(),
  " -U 'admin' -P 'correct horse'");
check('without a secret, plain relay: the password the caller passes comes first', zmuCredentials('from request'),
  " -U 'admin' -P 'from request'");
$secretIsSet = true;
check('with a secret, a password the caller passes is still not used', strpos(zmuCredentials('from request'), 'from request'), false);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
