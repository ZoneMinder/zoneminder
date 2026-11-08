<?php
// Tests that a client can't choose its session id (web/includes/session.php).
//
// zm_session_start() sets session.use_strict_mode, but PHP asks a user session
// handler whether an id exists only when the handler implements
// SessionUpdateTimestampHandlerInterface. ZMSessionHandler didn't, so any
// ZMSESSID a client sent became its session id, and a session id planted in a
// victim's browser (a cookie set from a sibling subdomain, or over plain HTTP)
// became the victim's session once they authenticated in it (session fixation).
//
// Run: php tests/php/test_session_strict_id.php
//
// The Sessions table is SQLite in memory, standing in for ZoneMinder's
// database, so session.php's handler runs as it does in the web server.

$failures = 0;
$passes = 0;

function check($name, $got, $expected) {
  global $failures, $passes;
  if ($got === $expected) {
    $passes++;
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "not ok - $name\n     got:      ".var_export($got, true)."\n     expected: ".var_export($expected, true)."\n";
  }
}

if (!extension_loaded('pdo_sqlite')) {
  echo "skip - needs pdo_sqlite\n";
  exit(0);
}

$db = new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec('CREATE TABLE Sessions (id CHAR(32) PRIMARY KEY, access INT, data TEXT)');
function zmDbConnOrNull() { global $db; return $db; }
function getRemoteAddr() { return '127.0.0.1'; }

// session.php requires Network.php for getRemoteAddr(); stubbed above.
$src = file_get_contents(__DIR__.'/../../web/includes/session.php');
$src = preg_replace('/^require_once\(__DIR__\.\'\/Network\.php\'\);$/m', '', $src);
eval('?>'.$src);

ini_set('session.use_strict_mode', 1);
ini_set('session.use_cookies', 0);
ini_set('session.cache_limiter', '');

$handler = new ZMSessionHandler();
$db->exec("INSERT INTO Sessions VALUES ('issued00000000000000000000000001', ".time().", '')");
check('validateId: an id the server issued', $handler->validateId('issued00000000000000000000000001'), true);
check('validateId: an id it never issued', $handler->validateId('planted0000000000000000000000001'), false);

// A request carrying an id nobody issued gets a new one.
$_COOKIE[session_name()] = 'planted0000000000000000000000001';
session_id('planted0000000000000000000000001');
session_start();
check('a made-up id is replaced', session_id() !== 'planted0000000000000000000000001', true);
$_SESSION['username'] = 'admin';
session_write_close();
check('nothing is stored under the made-up id',
  $db->query("SELECT COUNT(*) FROM Sessions WHERE id = 'planted0000000000000000000000001'")->fetchColumn(), 0);

// A request carrying an issued id keeps it.
session_id('issued00000000000000000000000001');
session_start();
check('an issued id is kept', session_id(), 'issued00000000000000000000000001');
session_write_close();

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
