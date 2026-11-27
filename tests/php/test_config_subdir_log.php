<?php
// loadConfigFiles() in web/includes/config.php.in logs a warning when the conf.d
// folder is missing or not readable. The message named the folder with
// $configSubFolder, a variable that does not exist: PHP 8 raised "Undefined
// variable" on every request and the log line ended with nothing to check. It
// now names ZM_CONFIG_SUBDIR.
//
// Run: php tests/php/test_config_subdir_log.php   (from the repo root)
//
// config.php needs zm.conf and a database, so the two functions are lifted out
// of the file.
$src = file_get_contents(__DIR__.'/../../web/includes/config.php.in');
if ($src === false) {
  echo "FAIL could not read config.php.in\n";
  exit(1);
}
foreach (array('/^function loadConfigFiles\(.*?^}$/ms', '/^function process_configfile\(.*?^}$/ms') as $pattern) {
  if (!preg_match($pattern, $src, $matches)) {
    echo "FAIL $pattern not found in config.php.in\n";
    exit(1);
  }
  eval($matches[0]);
}

$passed = 0;
$failed = 0;
function check($label, $got, $want) {
  global $passed, $failed;
  if ($got === $want) {
    $passed++;
    echo "  ok $label\n";
  } else {
    $failed++;
    echo "  FAIL $label\n";
    echo "       got:  ".var_export($got, true)."\n";
    echo "       want: ".var_export($want, true)."\n";
  }
}

$dir = sys_get_temp_dir().'/zm_test_config_subdir_'.getmypid();
mkdir($dir, 0755, true);
$conf = $dir.'/zm.conf';
file_put_contents($conf, "ZM_DB_HOST=localhost\n");
define('ZM_CONFIG', $conf);

$log = $dir.'/php.log';
ini_set('error_log', $log);
ini_set('log_errors', '1');
ini_set('display_errors', '0');
// No zm.conf in the cwd, so loadConfigFiles() reads ZM_CONFIG.
mkdir($dir.'/cwd');
chdir($dir.'/cwd');

$warnings = array();
set_error_handler(function($no, $str) use (&$warnings) { $warnings[] = $str; return true; });

// The conf.d folder does not exist.
define('ZM_CONFIG_SUBDIR', $dir.'/conf.d');
$config = loadConfigFiles();
check('zm.conf is still read', isset($config['ZM_DB_HOST']), true);
check('a missing conf.d raises no PHP warning', $warnings, array());
$logged = file_get_contents($log);
check('the log line names the missing folder', strpos($logged, 'Check '.ZM_CONFIG_SUBDIR) !== false, true);

restore_error_handler();
system('rm -rf '.escapeshellarg($dir));

echo "\n$passed passed, $failed failed\n";
exit($failed ? 1 : 0);
