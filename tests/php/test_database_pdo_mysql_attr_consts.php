<?php
// PDO::MYSQL_ATTR_* is deprecated since PHP 8.5 (use Pdo\Mysql::ATTR_*, available from 8.4).
// dbConnect() read them on every connection, so every request raised the deprecation.
// dbMysqlAttr() must return the same option numbers and dbConnect() must not use the old names.
//
// php tests/php/test_database_pdo_mysql_attr_consts.php   (from the repo root)

$failures = 0;
function check($name, $got, $expected) {
  global $failures;
  if ($got === $expected) {
    echo "ok - $name\n";
  } else {
    $failures++;
    echo 'not ok - '.$name.' (got '.var_export($got, true).' expected '.var_export($expected, true).")\n";
  }
}

$file = __DIR__.'/../../web/includes/database.php';
$old = array();
$tokens = token_get_all(file_get_contents($file));
foreach ($tokens as $i => $t) {
  if (is_array($t) && $t[0] == T_STRING && strpos($t[1], 'MYSQL_ATTR_') === 0)
    $old[] = $t[1].' line '.$t[2];
}
check('database.php names no PDO::MYSQL_ATTR_ constant', $old, array());

$stub = sys_get_temp_dir().'/zm_dbattr_stub_'.getmypid();
mkdir($stub);
file_put_contents($stub.'/logger.php', "<?php\n");
set_include_path($stub.PATH_SEPARATOR.get_include_path());
if (extension_loaded('pdo_mysql') && !$failures) {
  define('ZM_DB_TYPE', 'mysql');
  define('ZM_DB_HOST', '127.0.0.1:1');
  define('ZM_DB_NAME', 'zm');
  define('ZM_DB_USER', 'zm');
  define('ZM_DB_PASS', 'zm');
  define('ZM_DB_SSL_CA_CERT', '/nonexistent/ca.pem');
  define('ZM_DB_SSL_CLIENT_KEY', '/nonexistent/key.pem');
  define('ZM_DB_SSL_CLIENT_CERT', '/nonexistent/cert.pem');
  define('ZM_DB_SSL_VERIFY_SERVER_CERT', '0');
  require_once($file);

  $deprecations = array();
  set_error_handler(function($errno, $errstr) use (&$deprecations) {
    $deprecations[] = $errstr;
    return true;
  });
  ini_set('error_log', '/dev/null');
  foreach (array('MULTI_STATEMENTS', 'SSL_CA', 'SSL_KEY', 'SSL_CERT', 'SSL_VERIFY_SERVER_CERT') as $name) {
    check("dbMysqlAttr('$name') is the PDO::MYSQL_ATTR_ option number",
      dbMysqlAttr($name), @constant('PDO::MYSQL_ATTR_'.$name));
  }
  $deprecations = array();
  check('dbConnect with an unreachable server returns null', dbConnect(), null);
  check('dbConnect raises no PHP deprecation', $deprecations, array());
  restore_error_handler();
  unlink($stub.'/logger.php');
  rmdir($stub);
} else {
  @unlink($stub.'/logger.php');
  @rmdir($stub);
  echo "# runtime part skipped\n";
}
exit($failures ? 1 : 0);
