<?php
// Cookies are only given the Secure flag when the request arrived over https,
// whether directly (HTTPS=on) or via a proxy that reports it in
// X-Forwarded-Proto. The forwarder's first hop is the one that decides.
//
// php tests/php/test_cookie_secure_flag.php .   (from the repo root)

$root = realpath($argv[1] ?? __DIR__.'/../..');
require_once $root.'/web/includes/session.php';

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

// Report the function's answer, or a distinct value when it doesn't exist, so
// an older session.php still fails every behavioural case.
function secure() {
  if (!function_exists('zm_request_is_secure')) return 'missing';
  return zm_request_is_secure();
}

function request_secure($https = null, $forwarded = null) {
  unset($_SERVER['HTTPS']);
  unset($_SERVER['HTTP_X_FORWARDED_PROTO']);
  if ($https !== null) $_SERVER['HTTPS'] = $https;
  if ($forwarded !== null) $_SERVER['HTTP_X_FORWARDED_PROTO'] = $forwarded;
  return secure();
}

check('zm_request_is_secure exists', function_exists('zm_request_is_secure'), true);

check('nothing set is not secure', request_secure(), false);
check('HTTPS=on is secure', request_secure('on'), true);
check('HTTPS=1 is secure', request_secure('1'), true);
check('HTTPS=off is not secure', request_secure('off'), false);
check('HTTPS empty is not secure', request_secure(''), false);
check('forwarded https is secure', request_secure(null, 'https'), true);
check('forwarded HTTPS uppercase is secure', request_secure(null, 'HTTPS'), true);
check('forwarded http is not secure', request_secure(null, 'http'), false);
check('forwarded https,http is secure', request_secure(null, 'https,http'), true);
check('forwarded http,https is not secure', request_secure(null, 'http,https'), false);
check('HTTPS=off with forwarded https is secure', request_secure('off', 'https'), true);

unset($_SERVER['HTTPS']);
unset($_SERVER['HTTP_X_FORWARDED_PROTO']);

$src = file_get_contents($root.'/web/includes/session.php');
check('session cookie params take the secure flag', strpos($src, "\$currentCookieParams['secure'] = true") !== false, true);
check('zm_setcookie sets the secure option', strpos($src, "\$options['secure'] = true") !== false, true);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
