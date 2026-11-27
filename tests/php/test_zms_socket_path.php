<?php
// Security regression test: ajax/stream.php must address a zms by the names zms
// itself uses, which carry the id of the user zms authenticated. Before, the
// names held only the 6-digit connkey, so any logged-in user who guessed one
// could query, pause or quit another user's stream, of a monitor they may not
// view included.
//
// tests/zm_stream.cpp checks the C++ side (StreamBase::openComms()) against the
// same names.
//
// zmsSocketPath() is taken from functions.php, which can't be loaded on its own.
//
// Run as: php tests/php/test_zms_socket_path.php

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

$source = file_get_contents(__DIR__.'/../../web/includes/functions.php');
if (!preg_match('/^function zmsSocketPath\(.*?^}/ms', $source, $m)) {
  echo "FAIL - zmsSocketPath() not found in functions.php\n";
  exit(1);
}
define('ZM_PATH_SOCKS', '/run/zm');
eval($m[0]);

check('command socket of user 7', zmsSocketPath('004242', 7, 's.sock'), '/run/zm/zms-004242-7s.sock');
check('reply socket of user 7', zmsSocketPath(4242, '7', 'w.sock'), '/run/zm/zms-004242-7w.sock');
check('lock of user 7', zmsSocketPath(4242, 7, '.lock'), '/run/zm/zms-004242-7.lock');
check('another user gets another socket', zmsSocketPath(4242, 8, 's.sock') !== zmsSocketPath(4242, 7, 's.sock'), true);
check('no authentication: owner 0', zmsSocketPath(4242, 0, 's.sock'), '/run/zm/zms-004242-0s.sock');
check('the connkey stays numeric', zmsSocketPath('42/../x', 7, 's.sock'), '/run/zm/zms-000042-7s.sock');
check('the owner stays numeric', zmsSocketPath(4242, '7/../x', 's.sock'), '/run/zm/zms-004242-7s.sock');

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
