<?php
// Security regression test: host/login must not mint a refresh token for a token's user when
// the caller also sends a password.
//
// beforeFilter() validates user=/pass= (or username=/password=), then a token= if there is
// one, and keeps the token's user in $user. login() mints a refresh token for $user whenever
// a user name and password were sent, so one's own password plus someone else's (leaked,
// soon to expire) refresh token gave a new 24 h refresh token for that someone. A refresh
// token alone never yields a new refresh token. refs GHSA-m77q-66v7-j3fq
//
// HostController can't be loaded without CakePHP, so login() is taken from the source and run
// in a stand-in, with $user already set the way beforeFilter() leaves it.
//
// Run as: php tests/php/test_api_login_token.php

$failures = 0;
$passes = 0;

function check($name, $got, $want) {
  global $failures, $passes;
  if ($got === $want) {
    $passes++;
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n";
  }
}

$source = file_get_contents(__DIR__.'/../../web/api/app/Controller/HostController.php');
if (!preg_match('/^  function login\(\) \{.*?^  } \/\/ end function login\(\)\n/ms', $source, $m)) {
  echo "FAIL - could not find login() in HostController.php\n";
  exit(1);
}

define('ZM_OPT_USE_AUTH', true);
define('ZM_OPT_USE_LEGACY_API_AUTH', false);
class UnauthorizedException extends Exception {}
function __($s) { return $s; }
eval('namespace ZM; function Debug($m) {}');

class LoginRequest {
  public $q;
  public function __construct($q) { $this->q = $q; }
  public function query($k) { return isset($this->q[$k]) ? $this->q[$k] : null; }
  public function data($k) { return null; }
}
// release-1.36 reads the user as an array ($user['Username']); earlier branches
// call $user->Username(). Support both so the lifted login() runs either way.
class TokenUser implements ArrayAccess {
  private $name;
  public function __construct($name) { $this->name = $name; }
  public function Username() { return $this->name; }
  #[\ReturnTypeWillChange]
  public function offsetExists($k) { return $k === 'Username'; }
  #[\ReturnTypeWillChange]
  public function offsetGet($k) { return $k === 'Username' ? $this->name : null; }
  #[\ReturnTypeWillChange]
  public function offsetSet($k, $v) {}
  #[\ReturnTypeWillChange]
  public function offsetUnset($k) {}
}

eval('class LoginHarness { public $request; public $minted; public $out;
  private function _getVersion() { return array("1.39", "2.0"); }
  private function _getCredentials($refresh = false, $token = "", $username = "") {
    global $user;
    $this->minted = $refresh ? "refresh for ".$username : "access only for ".$user->Username();
    return array("a", 1, "r", 2);
  }
  public function set($a) { $this->out = $a; }
  '.$m[0].' }');

function login(array $q, $beforeFilterUser) {
  global $user;
  $user = new TokenUser($beforeFilterUser);
  $h = new LoginHarness();
  $h->request = new LoginRequest($q);
  try {
    $h->login();
  } catch (UnauthorizedException $e) {
    return 'refused';
  }
  return $h->minted;
}

// beforeFilter() leaves the token's user in $user when a token is sent.
check('password plus another user\'s token is refused',
  login(array('user' => 'low', 'pass' => 'x', 'token' => 'admin-refresh'), 'admin'), 'refused');
check('username/password plus a token is refused',
  login(array('username' => 'low', 'password' => 'x', 'token' => 'admin-refresh'), 'admin'), 'refused');
check('a password login mints a refresh token for the authenticated user',
  login(array('user' => 'low', 'pass' => 'x'), 'low'), 'refresh for low');
check('a token login mints an access token only',
  login(array('token' => 'admin-refresh'), 'admin'), 'access only for admin');

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
