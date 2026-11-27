<?php
// Tests the first-run helpers in web/includes/auth.php.
//
// A new install starts with authentication on and the admin password admin,
// which is refused everywhere. The login page then offers a choice instead of
// the login form: set the admin password, or turn authentication off.
// firstRunAccount() says whether that is still pending, authSetInConfigFile()
// whether zm.conf/conf.d has already decided, and applyFirstRunSetup() applies
// the answer.
//
// Run: php tests/php/test_first_run.php
//
// Including auth.php needs a database, so the functions are lifted out of the
// file, as in test_default_password.php, and the database and logging calls
// they make are stubbed here.
namespace ZM {
  $GLOBALS['audit'] = array();
  function AuditAction($action, $target_type, $target_id, $details) {
    $GLOBALS['audit'][] = "$action $target_type $target_id $details";
  }
  function Warning($s) {}
  function Info($s) {}
  class User {
    private $row;
    function __construct($row) { $this->row = $row; }
    function Id() { return $this->row['Id']; }
    function Username() { return $this->row['Username']; }
  }
}

namespace {
$auth_src = file_get_contents(__DIR__.'/../../web/includes/auth.php');
// config.php is generated from config.php.in at build time; either has it.
$config_file = __DIR__.'/../../web/includes/config.php.in';
if (!file_exists($config_file)) $config_file = __DIR__.'/../../web/includes/config.php';
$config_src = file_get_contents($config_file);
foreach (array(
  array($auth_src, "/^define\('ZM_DEFAULT_PASSWORD',.*?;$/m"),
  array($auth_src, '/^function requestString\(.*?^}$/ms'),
  array($auth_src, '/^function isDefaultPassword\(.*?^}$/ms'),
  array($auth_src, '/^function passwordProblem\(.*?^}$/ms'),
  array($auth_src, '/^function passwordMinLength\(.*?^}$/ms'),
  array($auth_src, '/^function newPasswordProblem\(.*?^}$/ms'),
  array($auth_src, '/^function firstRunAccount\(.*?^}$/ms'),
  array($auth_src, '/^function authSetInConfigFile\(.*?^}$/ms'),
  array($auth_src, '/^function applyFirstRunSetup\(.*?^}$/ms'),
  array($config_src, '/^function process_configfile\(.*?^}$/ms'),
) as $lift) {
  if (!preg_match($lift[1], $lift[0], $matches)) {
    echo "FAIL {$lift[1]} not found\n";
    exit(1);
  }
  eval($matches[0]);
}

// A fresh install: authentication on, privacy page not answered yet, admin
// with the seeded password. Each check changes one of these.
$dir = sys_get_temp_dir().'/zm_first_run_'.getmypid();
mkdir($dir.'/conf.d', 0700, true);
file_put_contents($dir.'/zm.conf', "ZM_PATH_BUILD=/tmp\n");
define('ZM_CONFIG', $dir.'/zm.conf');
define('ZM_CONFIG_SUBDIR', $dir.'/conf.d');
define('ZM_AUTH_TYPE', 'builtin');

$seeded = password_hash('admin', PASSWORD_BCRYPT);
$db = array();
$queries = array();
function reset_install() {
  global $db, $queries, $seeded;
  $db = array('ZM_OPT_USE_AUTH' => '1', 'ZM_SHOW_PRIVACY' => '1',
    'admin' => array('Id' => 1, 'Username' => 'admin', 'Password' => $seeded, 'Enabled' => 1));
  $queries = array();
  $GLOBALS['audit'] = array();
  $_SESSION = array('firstRunNonce' => 'n0nce');
  $_REQUEST = array('first_run_nonce' => 'n0nce');
  $GLOBALS['firstRunProblem'] = null;
}
function dbFetchOne($sql, $col, $params) {
  global $db;
  return ($params[0] === 'admin' and $db['admin']['Enabled']) ? $db['admin'] : false;
}
function dbQuery($sql, $params = array()) {
  global $db, $queries;
  $queries[] = $sql;
  if (strpos($sql, 'UPDATE Users SET Password') === 0) $db['admin']['Password'] = $params[0];
  if (strpos($sql, "UPDATE Config SET Value='0' WHERE Name='ZM_OPT_USE_AUTH'") === 0) $db['ZM_OPT_USE_AUTH'] = '0';
  return true;
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
    echo "FAIL $name: expected ".var_export($expected, true).', got '.var_export($got, true)."\n";
  }
}

// The constants firstRunAccount() also reads (authentication on, privacy page
// not answered, builtin auth) can be defined only once here; the holodeck probe
// covers them being off.
define('ZM_OPT_USE_AUTH', '1');
define('ZM_SHOW_PRIVACY', '1');

echo "firstRunAccount\n";
reset_install();
check('fresh install: the admin account', firstRunAccount() ? firstRunAccount()->Username() : null, 'admin');
$db['admin']['Password'] = password_hash('chosen', PASSWORD_BCRYPT);
check('admin password changed: over', firstRunAccount(), null);
reset_install();
$db['admin']['Enabled'] = 0;
check('admin disabled: over', firstRunAccount(), null);
reset_install();
$db['admin']['Password'] = '*4ACFE3202A5FF5CF467898FC58AAB1D615029441'; # mysql PASSWORD('admin')
check('legacy mysql hash: not offered (login and its change form handle it)', firstRunAccount(), null);

echo "authSetInConfigFile\n";
check('not in any file', authSetInConfigFile(), false);
file_put_contents($dir.'/conf.d/99-auth.conf', "ZM_OPT_USE_AUTH=yes\n");
check('in conf.d', authSetInConfigFile(), true);
unlink($dir.'/conf.d/99-auth.conf');
file_put_contents($dir.'/zm.conf', "ZM_PATH_BUILD=/tmp\nZM_OPT_USE_AUTH=1\n");
check('in zm.conf', authSetInConfigFile(), true);
file_put_contents($dir.'/zm.conf', "ZM_PATH_BUILD=/tmp\n# ZM_OPT_USE_AUTH=0\n");
check('commented out', authSetInConfigFile(), false);

echo "applyFirstRunSetup: require a login\n";
reset_install();
$_REQUEST += array('first_run_auth' => 'on', 'new_password' => 's3cret-pass', 'new_password_confirm' => 's3cret-pass');
check('returns the login to use', applyFirstRunSetup(), array('admin', 's3cret-pass'));
check('password stored as bcrypt', password_verify('s3cret-pass', $db['admin']['Password']), true);
check('authentication left on', $db['ZM_OPT_USE_AUTH'], '1');
check('nonce used up', isset($_SESSION['firstRunNonce']), false);
check('audited', $GLOBALS['audit'], array('update user 1 Changed: Password (first-run setup)'));
check('the first run is over', firstRunAccount(), null);

reset_install();
$_REQUEST += array('first_run_auth' => 'on', 'new_password' => 'admin', 'new_password_confirm' => 'admin');
check('admin again: refused', applyFirstRunSetup(), null);
check('admin again: says why', $firstRunProblem, 'NewPasswordIsDefault');
check('admin again: nothing stored', $queries, array());

reset_install();
$_REQUEST += array('first_run_auth' => 'on', 'new_password' => 's3cret-pass', 'new_password_confirm' => 's3cret-pas');
check('confirmation differs: refused', applyFirstRunSetup(), null);
check('confirmation differs: says why', $firstRunProblem, 'PasswordsDifferent');

reset_install();
$_REQUEST += array('first_run_auth' => 'on', 'new_password' => '', 'new_password_confirm' => '');
check('empty password: refused', applyFirstRunSetup(), null);
check('empty password: says why', $firstRunProblem, 'NewPasswordRequired');

echo "applyFirstRunSetup: no login\n";
reset_install();
$_REQUEST['first_run_auth'] = 'off';
check('returns nothing to log in with', applyFirstRunSetup(), null);
check('authentication turned off', $db['ZM_OPT_USE_AUTH'], '0');
check('admin password untouched', $db['admin']['Password'], $seeded);
check('audited', $GLOBALS['audit'], array('update config 0 Changed: ZM_OPT_USE_AUTH 1 to 0 (first-run setup)'));

reset_install();
$_REQUEST['first_run_auth'] = 'off';
file_put_contents($dir.'/conf.d/99-auth.conf', "ZM_OPT_USE_AUTH=1\n");
check('set in conf.d: refused', applyFirstRunSetup(), null);
check('set in conf.d: database untouched', $queries, array());
unlink($dir.'/conf.d/99-auth.conf');

echo "applyFirstRunSetup: the form itself\n";
foreach (array(
  'no nonce posted' => function () { unset($_REQUEST['first_run_nonce']); },
  'another nonce' => function () { $_REQUEST['first_run_nonce'] = 'other'; },
  'nonce as an array' => function () { $_REQUEST['first_run_nonce'] = array('n0nce'); },
  'no nonce in the session' => function () { unset($_SESSION['firstRunNonce']); },
  'first run already over' => function () { global $db; $db['admin']['Password'] = password_hash('x', PASSWORD_BCRYPT); },
  'unknown choice' => function () { $_REQUEST['first_run_auth'] = 'maybe'; },
) as $name => $tamper) {
  foreach (array('on', 'off') as $choice) {
    reset_install();
    $_REQUEST += array('first_run_auth' => $choice, 'new_password' => 's3cret-pass', 'new_password_confirm' => 's3cret-pass');
    $tamper();
    check("$name ($choice): refused", applyFirstRunSetup(), null);
    check("$name ($choice): nothing written", $queries, array());
  }
}

array_map('unlink', glob($dir.'/conf.d/*') ?: array());
rmdir($dir.'/conf.d');
unlink($dir.'/zm.conf');
rmdir($dir);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
}
