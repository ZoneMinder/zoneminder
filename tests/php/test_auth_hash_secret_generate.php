<?php
// Tests ensureAuthHashSecret() in web/includes/config.php.in.
//
// zm_create.sql is generated at build time, so the ZM_AUTH_HASH_SECRET it
// inserts is the public shipped default on every server. zmupdate.pl -f
// replaces it, but not every install runs that (an rpm install, a container,
// a database created by hand from zm_create.sql). loadConfig() therefore
// replaces a default or empty secret with a random one the first time it reads
// the Config table, so each server gets its own.
//
// Run: php tests/php/test_auth_hash_secret_generate.php
//
// config.php needs zm.conf and a database, so the function is lifted out of
// the file and given a fake PDO holding the one Config row.
$src = file_get_contents(__DIR__.'/../../web/includes/config.php.in');
if ($src === false) {
  echo "FAIL could not read config.php.in\n";
  exit(1);
}
// loadConfig() first runs at file scope, and ensureAuthHashSecret() reads the
// constant, so the define must come before that call or every request dies with
// "Undefined constant".
$define_at = strpos($src, "define('ZM_AUTH_HASH_SECRET_DEFAULT'");
$call_at = strpos($src, '$zm_config = loadConfig();');
if ($define_at === false or $call_at === false or $define_at > $call_at) {
  echo "FAIL ZM_AUTH_HASH_SECRET_DEFAULT must be defined before the first loadConfig() call\n";
  exit(1);
}
foreach (array("/^define\('ZM_AUTH_HASH_SECRET_DEFAULT'.*?;$/m",
               '/^function ensureAuthHashSecret\(.*?^}$/ms') as $pattern) {
  if (!preg_match($pattern, $src, $matches)) {
    echo "FAIL $pattern not found in config.php.in\n";
    exit(1);
  }
  eval($matches[0]);
}

// One Config row. $race, when set, is written just before the UPDATE runs, as
// another request that got there first would.
class FakeStatement {
  private $db, $sql;
  function __construct($db, $sql) { $this->db = $db; $this->sql = $sql; }
  function execute($params) {
    if (strpos($this->sql, 'UPDATE') === 0) {
      if ($this->db->race !== null) { $this->db->value = $this->db->race; $this->db->race = null; }
      $this->db->updates++;
      if ($params[1] === 'ZM_AUTH_HASH_SECRET' and $this->db->value === $params[2]) $this->db->value = $params[0];
    }
    return true;
  }
  function fetchColumn() { return $this->db->value; }
}
class FakeDb {
  public $value, $race = null, $updates = 0;
  function __construct($value) { $this->value = $value; }
  function prepare($sql) { return new FakeStatement($this, $sql); }
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

$db = new FakeDb(ZM_AUTH_HASH_SECRET_DEFAULT);
$got = ensureAuthHashSecret($db, ZM_AUTH_HASH_SECRET_DEFAULT);
check('default is replaced with 64 hex characters', (bool)preg_match('/^[0-9a-f]{64}$/', $got), true);
check('the new secret is stored', $db->value, $got);

$db2 = new FakeDb(ZM_AUTH_HASH_SECRET_DEFAULT);
check('each server gets a different secret', ensureAuthHashSecret($db2, ZM_AUTH_HASH_SECRET_DEFAULT) !== $got, true);

$db = new FakeDb('');
check('empty is replaced', strlen(ensureAuthHashSecret($db, '')), 64);

$mine = str_repeat('m', 40);
$db = new FakeDb($mine);
check('a secret already set is returned unchanged', ensureAuthHashSecret($db, $mine), $mine);
check('and the database is not written', $db->updates, 0);

$theirs = str_repeat('t', 64);
$db = new FakeDb(ZM_AUTH_HASH_SECRET_DEFAULT);
$db->race = $theirs;
check('a concurrent first request wins and both agree on its secret',
  ensureAuthHashSecret($db, ZM_AUTH_HASH_SECRET_DEFAULT), $theirs);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
