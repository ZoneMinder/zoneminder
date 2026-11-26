<?php
// sendControlCommand(): when no control socket accepts, fall back to
// zmcontrol.pl and treat a non-zero exit as failure (the script exits 1 when it
// cannot reach or start the control daemon), and always close the socket.
//
// Run: php tests/php/test_monitor_send_control_command.php [path/to/Monitor.php]
// The optional argument points at the Monitor.php to load (used to show case 1
// fails against the original).

$root = __DIR__.'/../..';
$monitorFile = $argv[1] ?? $root.'/web/includes/Monitor.php';

$failures = 0;
$passes = 0;
function check($label, $got, $want) {
  global $failures, $passes;
  $ok = ($got === $want);
  if ($ok) {
    $passes++;
    echo "ok - $label\n";
  } else {
    $failures++;
    echo "FAIL - $label\n";
    echo "  got:  ".var_export($got, true)."\n";
    echo "  want: ".var_export($want, true)."\n";
  }
}

$tmp = sys_get_temp_dir().'/zm_sendctl_'.getmypid();
@mkdir($tmp.'/socks', 0755, true);
@mkdir($tmp.'/bin', 0755, true);
@mkdir($tmp.'/stub', 0755, true);

// Dependency stubs for Monitor.php/Object.php (no database).
file_put_contents($tmp.'/stub/database.php', <<<'STUB'
<?php
namespace {
function dbQuery($sql, $params = null) { return true; }
function dbFetchOne($sql, $col = null, $params = null) { return null; }
function dbFetchAll($sql, $col = null, $params = null) { return array(); }
function dbEscape($s) { return $s; }
}
namespace ZM { function Debug($m) {} function Warning($m) {} function Error($m) {} function Info($m) {} }
STUB
);
file_put_contents($tmp.'/stub/log_redact.php', "<?php\n");
foreach (array('Control', 'Group', 'Manufacturer', 'Model', 'Server', 'Storage', 'Zone') as $c) {
  file_put_contents($tmp.'/stub/'.$c.'.php', "<?php namespace ZM; class $c extends ZM_Object { protected static \$table='{$c}s'; protected \$defaults=array('Id'=>null,'Name'=>''); public static function find_one(\$p=array(),\$o=array()) { return null; } public static function find(\$p=array(),\$o=array()) { return array(); } }");
}

if (!defined('ZM_PATH_SOCKS')) define('ZM_PATH_SOCKS', $tmp.'/socks');
if (!defined('ZM_PATH_BIN')) define('ZM_PATH_BIN', $tmp.'/bin');
// ZM_SERVER_ID left undefined so the method treats this as the local server.

if (!function_exists('jsonEncode')) {
  function jsonEncode($value) { return json_encode($value); }
}

set_include_path($tmp.'/stub'.PATH_SEPARATOR.$root.'/web/includes'.PATH_SEPARATOR.get_include_path());
require_once($root.'/web/includes/Object.php');
require_once($monitorFile);

// Case 1: stub exits 1, no socket -> command not delivered.
file_put_contents($tmp.'/bin/zmcontrol.pl', "#!/bin/sh\nexit 1\n");
chmod($tmp.'/bin/zmcontrol.pl', 0755);
$m = new ZM\Monitor(array('Id' => 1));
check('stub exits 1 -> sendControlCommand returns false',
  $m->sendControlCommand('--command=moveConUp'), false);

// Case 2: stub exits 0 -> delivered.
file_put_contents($tmp.'/bin/zmcontrol.pl', "#!/bin/sh\nexit 0\n");
chmod($tmp.'/bin/zmcontrol.pl', 0755);
$m2 = new ZM\Monitor(array('Id' => 2));
check('stub exits 0 -> sendControlCommand returns true',
  $m2->sendControlCommand('--command=moveConUp'), true);

@unlink($tmp.'/bin/zmcontrol.pl');
foreach (array('database.php','log_redact.php','Control.php','Group.php','Manufacturer.php','Model.php','Server.php','Storage.php','Zone.php') as $f) @unlink($tmp.'/stub/'.$f);
@rmdir($tmp.'/stub');
@rmdir($tmp.'/socks');
@rmdir($tmp.'/bin');
@rmdir($tmp);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
