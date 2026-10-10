<?php
// Security regression test: web/includes/auth.php must not write credentials
// into the log. Log files are often world-readable, debug logs get pasted into
// support threads, and the Logs table is readable by System=View users.
//
// validateUser() logged the MySQL-encoded form of the typed password next to
// the stored bcrypt hash for users whose hash zmupdate.pl had overlaid
// ("-ZM-" prefix). The MySQL-encoded form is SHA1(SHA1(password)): unsalted,
// so it falls to an offline dictionary attack.
//
// auth.php's dependencies are replaced by stubs; ZM\Debug() and friends record
// every line so the test can search them.
//
// Run as: php tests/php/test_auth_log_redaction.php

namespace ZM {
  $GLOBALS['logged'] = array();
  function Debug($s)   { $GLOBALS['logged'][] = 'DBG '.$s; }
  function Info($s)    { $GLOBALS['logged'][] = 'INF '.$s; }
  function Warning($s) { $GLOBALS['logged'][] = 'WAR '.$s; }
  function Error($s)   { $GLOBALS['logged'][] = 'ERR '.$s; }
  function Fatal($s)   { $GLOBALS['logged'][] = 'FAT '.$s; }
  class User {
    public $row;
    function __construct($row) { $this->row = $row; }
    function Password() { return $this->row['Password']; }
  }
}

namespace {
  $failures = 0;
  $passes = 0;

  function check($name, $ok, $detail = '') {
    global $failures, $passes;
    if ($ok) {
      $passes++;
      echo "ok - $name\n";
    } else {
      $failures++;
      echo "FAIL - $name".($detail !== '' ? " ($detail)" : '')."\n";
    }
  }

  // The log lines recorded since the last call that contain any of $needles.
  function leaked(array $needles) {
    $hits = array();
    foreach ($GLOBALS['logged'] as $line) {
      foreach ($needles as $needle) {
        if ($needle !== '' and stripos($line, $needle) !== false) $hits[] = $line;
      }
    }
    $GLOBALS['logged'] = array();
    return $hits;
  }

  $stubdir = sys_get_temp_dir().'/zm_auth_log_redaction_stubs_'.getmypid();
  @mkdir($stubdir, 0700, true);
  foreach (array('session.php', 'Network.php', 'User.php', 'Group_Permission.php',
                 'Monitor_Permission.php', 'User_Role.php', 'Role_Group_Permission.php',
                 'Role_Monitor_Permission.php') as $stub) {
    file_put_contents($stubdir.'/'.$stub, "<?php\n");
  }
  set_include_path($stubdir.PATH_SEPARATOR.__DIR__.'/../../web/includes'.PATH_SEPARATOR.get_include_path());
  register_shutdown_function(function () use ($stubdir) {
    array_map('unlink', glob($stubdir.'/*.php'));
    @rmdir($stubdir);
  });

  define('ZM_OPT_USE_AUTH', true);
  define('ZM_AUTH_RELAY', 'hashed');
  define('ZM_AUTH_HASH_IPS', false);
  define('ZM_AUTH_HASH_TTL', 2);
  define('ZM_AUTH_HASH_SECRET', 'test-secret-0123456789abcdef0123456789ab');
  define('ZM_CASE_INSENSITIVE_USERNAMES', false);

  $users = array();
  function dbFetchOne($sql, $col = null, $values = array()) {
    global $users;
    foreach ($users as $u) if ($u['Username'] === $values[0]) return $u;
    return false;
  }
  function dbFetchAll($sql, $col = null, $values = array()) {
    global $users;
    return array_values($users);
  }
  function getRemoteAddr($trustedProxies = null) { return '192.0.2.10'; }

  require_once __DIR__.'/../../web/includes/auth.php';

  $password = 'Correct-Horse-7';
  $mysql_encoded = '*'.strtoupper(sha1(sha1($password, true)));
  $bcrypt = password_hash($password, PASSWORD_BCRYPT);
  $overlay = password_hash($mysql_encoded, PASSWORD_BCRYPT);

  echo "validateUser() logs no password material\n";
  foreach (array(
    'mysql' => $mysql_encoded,
    'bcrypt' => $bcrypt,
    'mysql+bcrypt' => '-ZM-'.$overlay,
  ) as $type => $stored) {
    $users = array(array('Id' => 1, 'Username' => 'alice', 'Password' => $stored, 'Enabled' => 1));
    $GLOBALS['logged'] = array();
    $ret = validateUser('alice', $password);
    check("$type: the right password is accepted", $ret[0] !== false);
    $hits = leaked(array($password, $mysql_encoded, $stored, substr($stored, 4), $bcrypt));
    check("$type: no password, encoded password or stored hash in the log", !count($hits), implode(' | ', $hits));

    $ret = validateUser('alice', 'wrong-password');
    check("$type: a wrong password is refused", $ret[0] === false);
    $hits = leaked(array('wrong-password', '*'.strtoupper(sha1(sha1('wrong-password', true))), $stored));
    check("$type: nothing of a wrong password in the log", !count($hits), implode(' | ', $hits));
  }

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
