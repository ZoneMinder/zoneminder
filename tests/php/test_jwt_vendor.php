<?php
// Tests the vendored firebase/php-jwt against the way ZoneMinder calls it.
//
// web/vendor shipped php-jwt 5 while composer.lock pinned 7. From php-jwt 6 on,
// JWT::decode() takes a Key object instead of a key and an array of allowed
// algorithms, so the v5 form validateToken() used, JWT::decode($token, $key,
// array('HS256')), fails every token once vendor is brought up to the lock.
//
// Run: php tests/php/test_jwt_vendor.php
require_once __DIR__.'/../../web/vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$failures = 0;
$passes = 0;

function check($name, $got, $expected) {
  global $failures, $passes;
  if ($got === $expected) {
    $passes++;
    echo "ok   $name\n";
  } else {
    $failures++;
    echo "FAIL $name\n     got:      ".var_export($got, true)."\n     expected: ".var_export($expected, true)."\n";
  }
}

function decodes($token, $key) {
  try {
    return JWT::decode($token, new Key($key, 'HS256'));
  } catch (Exception $e) {
    return get_class($e);
  }
}

$secret = bin2hex(random_bytes(32));
$now = time();

// As HostController::login() and zmuCredentials() encode.
$token = JWT::encode(array('iss' => 'ZoneMinder', 'iat' => $now, 'exp' => $now + 7200,
  'user' => 'admin', 'type' => 'access'), $secret, 'HS256');
$decoded = decodes($token, $secret);
check('access token round trips', is_object($decoded) ? $decoded->user.'/'.$decoded->type : $decoded, 'admin/access');

check('wrong secret is refused', decodes($token, str_repeat('x', 64)), 'Firebase\JWT\SignatureInvalidException');

$expired = JWT::encode(array('iat' => $now - 7300, 'exp' => $now - 100, 'user' => 'admin', 'type' => 'access'),
  $secret, 'HS256');
check('expired token is refused', decodes($expired, $secret), 'Firebase\JWT\ExpiredException');

$none = rtrim(strtr(base64_encode('{"alg":"none","typ":"JWT"}'), '+/', '-_'), '=').'.'.
  rtrim(strtr(base64_encode('{"user":"admin","type":"access"}'), '+/', '-_'), '=').'.';
check('alg none is refused', is_string(decodes($none, $secret)), true);

// Every decode in the web code and these tests must use the Key form.
$v5_calls = array();
foreach (array_merge(array('web/includes/auth.php', 'web/api/app/Controller/HostController.php'),
    array_map(function ($f) { return 'tests/php/'.basename($f); }, glob(__DIR__.'/*.php'))) as $file) {
  foreach (file(__DIR__.'/../../'.$file) as $n => $line) {
    if (preg_match('#^\s*(//|\#|\*)#', $line)) continue;
    if (preg_match('/JWT::decode\(\s*\$/', $line) and !preg_match('/new (\\\\?Firebase\\\\JWT\\\\)?Key\(/', $line))
      $v5_calls[] = "$file:".($n + 1);
  }
}
check('no JWT::decode() call uses the v5 signature', $v5_calls, array());

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
