<?php
// ZM_AUTH_HASH_TTL is an integer option the Options page lets be 0. With 0 the
// web side tried no hour at all, so every auth= hash was refused, and it
// regenerated the hash on every request, while zms and zmu (zm_user.cpp) treat 0
// as 2 hours. authHashTtl() now does the same as zm_user.cpp, and every reader in
// auth.php goes through it.
//
// Run as: php tests/php/test_auth_hash_ttl_minimum.php [repo root]

namespace ZM {
  $GLOBALS['warnings'] = array();
  function Warning($m) { $GLOBALS['warnings'][] = $m; }
}

namespace {
  $root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
  $source = file_get_contents($root.'/web/includes/auth.php');
  $failures = 0;
  function check($name, $got, $want) {
    global $failures;
    if ($got === $want) echo "ok - $name\n";
    else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
  }

  if (!preg_match('/^function authHashTtl\(.*?^}/ms', $source, $fn)) {
    echo "FAIL - auth.php has no authHashTtl()\n";
    exit(1);
  }
  eval($fn[0]);

  check('0 hours is treated as 2, as zm_user.cpp does', authHashTtl(0), 2);
  check('it warns once', count($GLOBALS['warnings']), 1);
  authHashTtl('0');
  check('the warning is not repeated', count($GLOBALS['warnings']), 1);
  check('a negative value is treated as 2', authHashTtl(-3), 2);
  check('1 hour is kept', authHashTtl(1), 1);
  check('24 hours is kept', authHashTtl('24'), 24);

  // Every place that reads the option uses the helper.
  $body = preg_replace('/^function authHashTtl\(.*?^}/ms', '', $source);
  $raw = preg_match_all('/^(?!\s*\/\/).*\bZM_AUTH_HASH_TTL\b/m', $body);
  check('no reader uses ZM_AUTH_HASH_TTL directly', $raw, 0);

  echo $failures ? "\n$failures failed\n" : "\nall passed\n";
  exit($failures ? 1 : 0);
}
