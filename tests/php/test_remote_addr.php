<?php
// Tests how the client address is determined:
//   getRemoteAddr() (Network.php) - REMOTE_ADDR, or the right-most untrusted
//                   X-Forwarded-For hop when REMOTE_ADDR is a trusted proxy
//
// Neither file touches ZM's config or database, so this runs from a source
// checkout: php tests/php/test_remote_addr.php

// Included before any output: session.php installs a save handler at include
// time, and zm_session_regenerate_id_login() needs a live session. ZM's logging
// functions are stubbed so this runs without the rest of the web bootstrap.
namespace ZM {
  function Debug($m) {}
  function Warning($m) {}
  function Error($m) {}
}

namespace {
  function Debug($m) {}
  function Warning($m) {}
  function Error($m) {}

  // The minimum config session.php reads on the paths exercised here.
  define('ZM_COOKIE_LIFETIME', 0);
  define('ZM_OPT_USE_REMEMBER_ME', 'None');

  require_once __DIR__.'/../../web/includes/Network.php';
  require_once __DIR__.'/../../web/includes/session.php';

  // Buffer output so the session functions below don't see headers as sent.
  ob_start();
  // session.php installs a database-backed save handler at include time; swap
  // it for the file handler so this runs without a ZoneMinder database.
  session_module_name('files');
  session_save_path(sys_get_temp_dir());
  // Match the production session name so zm_session_start() treats the session
  // as already configured rather than re-running its setup on an active one.
  ini_set('session.name', 'ZMSESSID');
  session_start();

$failures = 0;
$passes = 0;

function check($name, $got, $expected) {
  global $failures, $passes;
  if ($got === $expected) {
    $passes++;
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "not ok - $name (got ".var_export($got, true)." expected ".var_export($expected, true).")\n";
  }
}

// ---- getRemoteAddr() ----

$_SERVER = array();
check('no address at all returns empty', getRemoteAddr(), '');

$_SERVER = array('REMOTE_ADDR' => '192.168.1.55');
check('falls back to REMOTE_ADDR', getRemoteAddr(), '192.168.1.55');

// With no trusted proxies (the default) X-Forwarded-For is client-controlled
// and must be ignored, otherwise a client picks the address its hash binds to.
$_SERVER = array('REMOTE_ADDR' => '203.0.113.9', 'HTTP_X_FORWARDED_FOR' => '192.168.1.55');
check('ignores XFF when no proxy is trusted', getRemoteAddr(''), '203.0.113.9');
check('config default (undefined) trusts nothing', getRemoteAddr(), '203.0.113.9');
check('ignores XFF from an untrusted peer', getRemoteAddr('10.0.0.1'), '203.0.113.9');

$_SERVER = array('REMOTE_ADDR' => '10.0.0.1', 'HTTP_X_FORWARDED_FOR' => '192.168.1.55');
check('uses XFF from a trusted proxy', getRemoteAddr('10.0.0.1'), '192.168.1.55');

// A proxy appends; the left-most values are whatever the client sent.
$_SERVER = array('REMOTE_ADDR' => '10.0.0.1', 'HTTP_X_FORWARDED_FOR' => '1.2.3.4, 192.168.1.55');
check('takes the right-most hop, not a spoofed left-most one', getRemoteAddr('10.0.0.1'), '192.168.1.55');

$_SERVER = array('REMOTE_ADDR' => '10.0.0.1', 'HTTP_X_FORWARDED_FOR' => '1.2.3.4,  192.168.1.55 , 10.0.0.9 ');
check('skips trusted hops from the right', getRemoteAddr('10.0.0.1, 10.0.0.9'), '192.168.1.55');
check('list may be space separated', getRemoteAddr('10.0.0.1 10.0.0.9'), '192.168.1.55');

$_SERVER = array('REMOTE_ADDR' => '10.0.0.1', 'HTTP_X_FORWARDED_FOR' => '10.0.0.9, 10.0.0.1');
check('all hops trusted returns the left-most', getRemoteAddr('10.0.0.1,10.0.0.9'), '10.0.0.9');

$_SERVER = array('REMOTE_ADDR' => '10.0.0.1', 'HTTP_X_FORWARDED_FOR' => '');
check('empty XFF falls back to REMOTE_ADDR', getRemoteAddr('10.0.0.1'), '10.0.0.1');

$_SERVER = array('REMOTE_ADDR' => '10.0.0.1', 'HTTP_X_FORWARDED_FOR' => ' , ');
check('XFF of only separators falls back to REMOTE_ADDR', getRemoteAddr('10.0.0.1'), '10.0.0.1');

// ---- zm_session_set_remote_addr(): the address-change handoff ----
//
echo "\n$passes passed, $failures failed\n";
ob_end_flush();
exit($failures ? 1 : 0);
} // end global namespace block
?>
