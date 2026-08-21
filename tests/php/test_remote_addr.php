<?php
// Tests how the client address is determined and tracked across a session:
//   getRemoteAddr()              (Network.php) - X-Forwarded-For first hop,
//                                else REMOTE_ADDR
//   zm_session_set_remote_addr() (session.php) - retaining the address an
//                                in-flight auth hash was issued against
//
// Neither file touches ZM's config or database, so this runs from a source
// checkout: php tests/php/test_remote_addr.php
//
// What those retained addresses are then accepted for is auth policy and is
// tested separately in test_auth_hash_candidate_addrs.php.

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

$_SERVER = array('REMOTE_ADDR' => '10.0.0.1', 'HTTP_X_FORWARDED_FOR' => '192.168.1.55');
check('prefers X-Forwarded-For', getRemoteAddr(), '192.168.1.55');

$_SERVER = array('REMOTE_ADDR' => '10.0.0.1', 'HTTP_X_FORWARDED_FOR' => '192.168.1.55, 10.0.0.9, 10.0.0.8');
check('takes only the first XFF hop', getRemoteAddr(), '192.168.1.55');

$_SERVER = array('REMOTE_ADDR' => '10.0.0.1', 'HTTP_X_FORWARDED_FOR' => '   192.168.1.55   , 10.0.0.9');
check('trims whitespace around the first hop', getRemoteAddr(), '192.168.1.55');

$_SERVER = array('REMOTE_ADDR' => '10.0.0.1', 'HTTP_X_FORWARDED_FOR' => '');
check('empty XFF falls back to REMOTE_ADDR', getRemoteAddr(), '10.0.0.1');

// ---- zm_session_set_remote_addr(): the address-change handoff ----
//
// The session's stored address is refreshed on every request, before any auth
// validation runs. Unless the address being replaced is captured at that moment
// it is simply lost, and nothing downstream can tell that it just changed -
// which is why retaining it belongs here and not at the point of validation.

// Request 1 arrives from A; a hash is issued and cached against A.
$_SESSION = array();
$_SERVER = array('REMOTE_ADDR' => '192.168.1.55');
zm_session_set_remote_addr();
$_SESSION['AuthHash192.168.1.55'] = 'hash-issued-to-A';
check('session binds to the first address seen', $_SESSION['remoteAddr'], '192.168.1.55');
check('nothing is retained when there is no earlier address',
  isset($_SESSION['prevRemoteAddr']), false);

// Request 2 arrives from B (wifi -> cellular). The hash the client still holds
// was issued to A, so A has to survive this refresh to remain checkable.
$_SERVER = array('REMOTE_ADDR' => '10.0.0.7');
zm_session_set_remote_addr();
check('session follows the new address', $_SESSION['remoteAddr'], '10.0.0.7');
check('the displaced address is retained', $_SESSION['prevRemoteAddr'], '192.168.1.55');
check('the retention is timestamped', isset($_SESSION['prevRemoteAddrAt']), true);
check('the hash cached against the old address is still reachable',
  isset($_SESSION['AuthHash192.168.1.55']), true);

// An unchanged address must not displace what we are still holding.
zm_session_set_remote_addr();
check('a repeat request does not overwrite the retained address',
  $_SESSION['prevRemoteAddr'], '192.168.1.55');

// Request 3 arrives from C before a hash was ever issued to B. Only one previous
// address is kept, and A's now-unreachable slot is dropped rather than left.
$_SERVER = array('REMOTE_ADDR' => '172.16.0.3');
zm_session_set_remote_addr();
check('only one previous address is retained', $_SESSION['prevRemoteAddr'], '10.0.0.7');
check('its cached hash is discarded rather than accumulating',
  isset($_SESSION['AuthHash192.168.1.55']), false);

// A login is a privilege boundary: nothing from before it stays acceptable.
$_SESSION['prevRemoteAddr'] = '10.0.0.7';
$_SESSION['prevRemoteAddrAt'] = time();
// Silenced: is_session_started() always reports false under CLI (by design, see
// session.php), so this re-enters session_start(). The assertions below still
// cover the behaviour we care about.
@zm_session_regenerate_id_login();
check('login drops any retained earlier address',
  isset($_SESSION['prevRemoteAddr']), false);
check('login drops its timestamp too',
  isset($_SESSION['prevRemoteAddrAt']), false);
check('login binds to the address it came from',
  $_SESSION['remoteAddr'], '172.16.0.3');

echo "\n$passes passed, $failures failed\n";
ob_end_flush();
exit($failures ? 1 : 0);
} // end global namespace block
?>
