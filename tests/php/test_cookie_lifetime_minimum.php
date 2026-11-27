<?php
// ZM_COOKIE_LIFETIME is the session cookie lifetime, and zm_session_start()
// regenerates the session id once a session is older than half of it. At 0
// every request a second after login regenerated the id, so parallel ajax
// calls raced on the old id and dropped the session. zmCookieLifetime() keeps
// the option at 60 seconds or more, else warns once and uses the default 3600.
//
// Run: php tests/php/test_cookie_lifetime_minimum.php

namespace {
$failures = 0;
function check($name, $got, $expected) {
  global $failures;
  if ($got === $expected) {
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "not ok - $name\n     got:      ".var_export($got, true)."\n     expected: ".var_export($expected, true)."\n";
  }
}
}

namespace ZM {
  $GLOBALS['warnings'] = array();
  function Warning($msg) { $GLOBALS['warnings'][] = $msg; }
}

namespace {
  $source = file_get_contents(__DIR__.'/../../web/includes/session.php');
  $found = preg_match('/^function zmCookieLifetime\(.*?^}/ms', $source, $m);
  check('zmCookieLifetime() is defined in session.php', $found, 1);
  if (!$found) {
    echo "1 failure(s)\n";
    exit(1);
  }
  eval($m[0]);

  check('0 falls back to 3600', zmCookieLifetime(0), 3600);
  check('0 warned once', count($warnings), 1);
  check('warning names the option', strpos($warnings[0], 'ZM_COOKIE_LIFETIME') !== false, true);
  check('warning names the value used', strpos($warnings[0], '3600') !== false, true);
  zmCookieLifetime(0);
  check('warning is not repeated', count($warnings), 1);
  check('30 falls back to 3600', zmCookieLifetime(30), 3600);
  check('59 falls back to 3600', zmCookieLifetime(59), 3600);
  check('60 is kept', zmCookieLifetime(60), 60);
  check('86400 is kept', zmCookieLifetime(86400), 86400);
  check('numeric string is kept', zmCookieLifetime('7200'), 7200);
  define('ZM_COOKIE_LIFETIME', 1800);
  check('null reads the constant', zmCookieLifetime(), 1800);

  $outside = preg_replace('/^function zmCookieLifetime\(.*?^}/ms', '', $source);
  $files = array(
    'session.php' => $outside,
    'actions/login.php' => file_get_contents(__DIR__.'/../../web/includes/actions/login.php'),
    'modals/logout.php' => file_get_contents(__DIR__.'/../../web/ajax/modals/logout.php'),
  );
  foreach ($files as $name => $code) {
    $code = preg_replace('/^\s*(\/\/|#).*$/m', '', $code);
    check("$name reads ZM_COOKIE_LIFETIME only through zmCookieLifetime()", preg_match('/ZM_COOKIE_LIFETIME/', $code), 0);
  }
  check('login.php uses zmCookieLifetime()', substr_count($files['actions/login.php'], 'zmCookieLifetime()'), 2);
  check('logout.php uses zmCookieLifetime()', substr_count($files['modals/logout.php'], 'zmCookieLifetime()'), 1);

  echo $failures ? "$failures failure(s)\n" : "all passed\n";
  exit($failures ? 1 : 0);
}
