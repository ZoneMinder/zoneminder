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
// constants (the default from config.php.in) and the function are lifted out, as
// test_auth_request_string.php does.
$auth_src = file_get_contents(__DIR__.'/../../web/includes/auth.php');
if ($auth_src === false) {
  echo "FAIL could not read auth.php\n";
  exit(1);
}
$config_src = file_get_contents(__DIR__.'/../../web/includes/config.php.in');
foreach (array(array($config_src, "/^define\('ZM_AUTH_HASH_SECRET_DEFAULT'.*?;$/m"),
               array($auth_src, "/^define\('ZM_AUTH_HASH_SECRET_MIN_LENGTH'.*?;$/m"),
               array($auth_src, '/^function authHashSecretValid\(.*?^}$/ms')) as list($src, $pattern)) {
  if (!preg_match($pattern, $src, $matches)) {
    echo "FAIL $pattern not found\n";
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

// Every secret authHashSecretValid() accepts must be one php-jwt accepts. php-jwt 7
// enforces the same minimum, so there the two must agree; older versions (php-jwt 5
// still ships in some vendor trees) accept any key, so only the first half holds.
$autoload = __DIR__.'/../../web/vendor/autoload.php';
if (file_exists($autoload)) {
  require_once $autoload;
  $enforces = method_exists('Firebase\JWT\JWT', 'validateHmacKeyLength');
  foreach (array(31, 32) as $len) {
    $key = str_repeat('k', $len);
    try {
      $ok = is_string(Firebase\JWT\JWT::encode(array('user' => 'u'), $key, 'HS256'));
    } catch (DomainException $e) {
      $ok = false;
    }
    if ($enforces)
      check("php-jwt agrees about a $len character secret", $ok, authHashSecretValid($key));
    else if (authHashSecretValid($key))
      check("php-jwt accepts a $len character secret", $ok, true);
  }
}

// The API must check the value Model::set() will save: Config[Value], or a bare
// Value when there is no Config entry. Reading only Config[Value] let
// {"Value":"short"} through.
$configs_src = file_get_contents(__DIR__.'/../../web/api/app/Controller/ConfigsController.php');
check('the API Configs editor reads the secret through requestField()',
  strpos($configs_src, "requestField('Config', 'Value')") !== false, true);
// Config.Name is the primary key: {"Config":{"Name":"ZM_AUTH_HASH_SECRET","Value":"short"}}
// sent to configs/edit/ZM_LANG_DEFAULT would validate one row and save another.
$pin_at = strpos($configs_src, 'pinRequestId($this->Config, $id)');
check('the API Configs editor pins the row before validating it',
  $pin_at !== false and $pin_at < strpos($configs_src, "requestField('Config', 'Value')"), true);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
