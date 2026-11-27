<?php
// The "Remember me" box on the login page decides whether the session cookie
// outlives the browser. The session cookie is issued while the user is being
// authenticated, before actions/login.php reads the box, so a login that ticked
// the box got a session cookie that ended with the browser, and one that did not
// tick it kept a 30 day cookie when an earlier remember cookie was still around.
//
// Runs the real session.php and actions/login.php behind PHP's built-in web
// server, with authentication done first as index.php does it, and reads the
// Set-Cookie headers of a login post.
//
// Run: php tests/php/test_login_remember_me_cookie.php

$web = realpath(__DIR__.'/../../web');

if (php_sapi_name() === 'cli-server') {
  ob_start();
  $db = new PDO('sqlite:'.getenv('ZM_TEST_DB'));
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $db->exec('CREATE TABLE IF NOT EXISTS Sessions (id CHAR(32) PRIMARY KEY, access INT, data TEXT)');
  function zmDbConnOrNull() { global $db; return $db; }
  function getRemoteAddr() { return '127.0.0.1'; }
  define('ZM_COOKIE_LIFETIME', 3600*24*30);
  define('ZM_AUTH_TYPE', 'builtin');
  if (($_GET['feature'] ?? '') !== 'off') define('ZM_OPT_USE_REMEMBER_ME', 'Yes');
  $src = file_get_contents($web.'/includes/session.php');
  $src = preg_replace('/^require_once\(__DIR__\.\'\/Network\.php\'\);$/m', '', $src);
  eval('?>'.$src);

  // What zm_authenticate_request() does with a login post, before any action file runs.
  $user = null;
  zm_session_start();
  if (($_POST['password'] ?? '') === 'good') {
    zm_session_regenerate_id_login();
    $_SESSION['username'] = $_POST['username'];
    $user = new stdClass();
  }
  session_write_close();

  $_REQUEST = $_POST;
  $action = 'login';
  $view = 'login';
  include $web.'/includes/actions/login.php';
  echo 'view='.$view;
  return;
}

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

if (!extension_loaded('pdo_sqlite')) {
  echo "skip - needs pdo_sqlite\n";
  exit(0);
}

$port = 18000 + (getmypid() % 1000);
$dbfile = tempnam(sys_get_temp_dir(), 'zmsess');
putenv('ZM_TEST_DB='.$dbfile);
$server = proc_open(
  array(PHP_BINARY, '-S', '127.0.0.1:'.$port, __FILE__),
  array(1 => array('file', '/dev/null', 'w'), 2 => array('file', '/dev/null', 'w')),
  $pipes
);
usleep(700000);

// Whether the last ZMSESSID the response sets outlives the browser; null if it sets none.
function sessionCookieLasts($fields, $cookie = '', $query = '') {
  global $port;
  $ctx = stream_context_create(array('http' => array(
    'method' => 'POST',
    'header' => "Content-Type: application/x-www-form-urlencoded\r\n".($cookie ? "Cookie: $cookie\r\n" : ''),
    'content' => http_build_query($fields),
    'ignore_errors' => true,
  )));
  @file_get_contents('http://127.0.0.1:'.$port.'/'.$query, false, $ctx);
  $last = null;
  foreach (($http_response_header ?? array()) as $line) {
    if (stripos($line, 'Set-Cookie: ZMSESSID=') === 0) $last = (stripos($line, 'expires=') !== false);
  }
  return $last;
}

$login = array('action' => 'login', 'username' => 'admin', 'password' => 'good');

check('ticked on a fresh browser: session cookie outlives the browser',
  sessionCookieLasts($login + array('remember_me' => '1')), true);
check('not ticked, no remember cookie: session cookie ends with the browser',
  sessionCookieLasts($login), false);
check('not ticked, stale remember cookie: session cookie ends with the browser',
  sessionCookieLasts($login, 'ZM_REMEMBER_ME=1'), false);
check('ticked, stale remember cookie: session cookie outlives the browser',
  sessionCookieLasts($login + array('remember_me' => '1'), 'ZM_REMEMBER_ME=1'), true);
check('remember me turned off: session cookie always outlives the browser',
  sessionCookieLasts($login, '', '?feature=off'), true);

proc_terminate($server);
@unlink($dbfile);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
