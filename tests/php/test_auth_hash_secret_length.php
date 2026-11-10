<?php
// Tests authHashSecretValid() in web/includes/auth.php.
//
// ZM_AUTH_HASH_SECRET is the HS256 key for API/login tokens. php-jwt 7 refuses
// an HMAC key shorter than the hash: JWT::encode() and JWT::decode() throw
// "Provided key is too short" for a key under 32 bytes, so a short secret made
// every token login fail, and HostController::login() answer with an uncaught
// DomainException. authHashSecretValid() is what the token paths and the
// option editors check, so a short secret is refused with a message instead.
//
// Run: php tests/php/test_auth_hash_secret_length.php
//
// Including auth.php needs a database (User.php pulls in database.php), so the
// constants and the function are lifted out of the file, as
// test_auth_request_string.php does.
$auth_src = file_get_contents(__DIR__.'/../../web/includes/auth.php');
if ($auth_src === false) {
  echo "FAIL could not read auth.php\n";
  exit(1);
}
foreach (array("/^define\('ZM_AUTH_HASH_SECRET_DEFAULT'.*?;$/m",
               "/^define\('ZM_AUTH_HASH_SECRET_MIN_LENGTH'.*?;$/m",
               '/^function authHashSecretValid\(.*?^}$/ms') as $pattern) {
  if (!preg_match($pattern, $auth_src, $matches)) {
    echo "FAIL $pattern not found in auth.php\n";
    exit(1);
  }
  eval($matches[0]);
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
    echo "FAIL $name\n     got:      ".var_export($got, true)."\n     expected: ".var_export($expected, true)."\n";
  }
}

check('minimum is the HS256 key size in bytes', ZM_AUTH_HASH_SECRET_MIN_LENGTH, 32);
check('empty secret is refused', authHashSecretValid(''), false);
check('shipped default is refused', authHashSecretValid(ZM_AUTH_HASH_SECRET_DEFAULT), false);
check('31 characters is refused', authHashSecretValid(str_repeat('a', 31)), false);
check('32 characters is accepted', authHashSecretValid(str_repeat('a', 32)), true);
check('a generated 64 hex character secret is accepted', authHashSecretValid(bin2hex(random_bytes(32))), true);
check('a non-string is refused', authHashSecretValid(array('x')), false);

// Every secret authHashSecretValid() accepts must be one php-jwt accepts.
$autoload = __DIR__.'/../../web/vendor/autoload.php';
if (file_exists($autoload)) {
  require_once $autoload;
  foreach (array(31, 32) as $len) {
    $key = str_repeat('k', $len);
    try {
      $token = Firebase\JWT\JWT::encode(array('user' => 'u'), $key, 'HS256');
      $headers = new stdClass();
      $ok = Firebase\JWT\JWT::decode($token, new Firebase\JWT\Key($key, 'HS256'), $headers)->user === 'u';
    } catch (DomainException $e) {
      $ok = false;
    }
    check("php-jwt agrees about a $len character secret", $ok, authHashSecretValid($key));
  }
}

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
