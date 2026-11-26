<?php
// Monitor model fixes, run against the real Object.php and Monitor.php with the
// database and the related models stubbed:
//  - Server(), Storage() and Control() kept the object loaded for the first
//    ServerId, StorageId or ControlId after the monitor was moved.
//  - shared_write() did not terminate or pad C string fields, so a shorter value
//    left the tail of the previous one and an empty value cleared nothing.
// Run as: php tests/php/test_monitor_model.php

$root = __DIR__.'/../..';
$failures = 0;
function check($label, $got, $want) {
  global $failures;
  $ok = ($got === $want);
  if (!$ok) $failures++;
  printf("[%s] %s%s\n", $ok ? 'PASS' : 'FAIL', $label, $ok ? '' : "\n        got: ".var_export($got, true)."\n       want: ".var_export($want, true));
}

$stub_dir = sys_get_temp_dir().'/zm_monitor_model_'.getmypid();
@mkdir($stub_dir);
file_put_contents($stub_dir.'/database.php', <<<'STUB'
<?php
namespace {
function dbQuery($sql, $params = null) { return true; }
function dbLastError() { return ''; }
function dbError($sql) { return ''; }
function dbInsertId() { return 1; }
function dbFetchAll($sql, $col = null, $params = null) { return array(); }
function dbFetchOne($sql, $col = null, $params = null) { return null; }
function translate($s) { return $s; }
}
namespace ZM { function Debug($m) {} function Warning($m) {} function Error($m) {} function Info($m) {} }
STUB
);
foreach (array('Control', 'Group', 'Manufacturer', 'Model', 'Zone') as $c) {
  file_put_contents("$stub_dir/$c.php", "<?php namespace ZM; class $c extends ZM_Object { protected static \$table='{$c}s'; protected \$defaults=array('Id'=>null,'Name'=>''); public static function find_one(\$p=array(),\$o=array()) { return null; } public static function find(\$p=array(),\$o=array()) { return array(); } }");
}
// find_one() hands back an object that remembers which Id it was asked for.
file_put_contents($stub_dir.'/Server.php', '<?php namespace ZM; class Server extends ZM_Object { protected static $table="Servers"; protected $defaults=array("Id"=>null,"Name"=>""); public static function find_one($p=array(),$o=array()) { $s=new Server(); $s->Id=$p["Id"]; return $s; } }');
file_put_contents($stub_dir.'/Storage.php', '<?php namespace ZM; class Storage extends ZM_Object { protected static $table="Storage"; protected $defaults=array("Id"=>null,"Name"=>"","Path"=>""); public static function find_one($p=array(),$o=array()) { $s=new Storage(); $s->Id=$p["Id"]; return $s; } }');
set_include_path($stub_dir.PATH_SEPARATOR.$root.'/web/includes'.PATH_SEPARATOR.get_include_path());
require_once($root.'/web/includes/Object.php');
require_once($root.'/web/includes/Monitor.php');

// Server(), Storage(), Control() follow the Id
$m = new ZM\Monitor(array('Id'=>1, 'ServerId'=>1, 'StorageId'=>1));
check('Server for the first ServerId', $m->Server()->Id(), 1);
$m->set(array('ServerId'=>2));
check('Server follows a changed ServerId', $m->Server()->Id(), 2);
check('Storage for the first StorageId', $m->Storage()->Id(), 1);
$m->set(array('StorageId'=>3));
check('Storage follows a changed StorageId', $m->Storage()->Id(), 3);
check('Storage is kept while the Id is unchanged', $m->Storage() === $m->Storage(), true);
check('Server is kept while the Id is unchanged', $m->Server() === $m->Server(), true);
$explicit = new ZM\Storage();
$explicit->Id = 9;
$m2 = new ZM\Monitor(array('Id'=>2, 'StorageId'=>1));
$m2->Storage($explicit);
check('an explicitly assigned Storage is kept', $m2->Storage()->Id(), 9);

// shared_write() of C string fields
$f = tempnam(sys_get_temp_dir(), 'zmshm');
file_put_contents($f, str_repeat("\0", 1500));
$sm = new ZM\Monitor(array('Id'=>9));
$rp = new ReflectionProperty($sm, 'shm_id');
$rp->setAccessible(true);
$rp->setValue($sm, fopen($f, 'r+'));
$sm->shared_write('TriggerData', 'cause', 'Forced Web');
$sm->shared_write('TriggerData', 'cause', 'Abc');
check('a shorter cause replaces the longer one', rtrim($sm->shared_read('TriggerData', 'cause'), "\0"), 'Abc');
$sm->shared_write('TriggerData', 'cause', '');
check('an empty cause clears the field', rtrim($sm->shared_read('TriggerData', 'cause'), "\0"), '');
$sm->shared_write('TriggerData', 'cause', str_repeat('x', 40));
check('a long cause is cut to leave a NUL', strlen(rtrim($sm->shared_read('TriggerData', 'cause'), "\0")), 31);
$sm->shared_write('TriggerData', 'text', str_repeat('y', 300));
$raw = file_get_contents($f);
check('a long text does not run into showtext', $raw[1192], "\0");
check('and ends in a NUL', $raw[936 + 255], "\0");
$sm->shared_write('SharedData', 'action', 5);
check('integer fields are still packed', $sm->shared_read('SharedData', 'action'), 5);
unlink($f);

@unlink($stub_dir.'/database.php');
foreach (array('Control', 'Group', 'Manufacturer', 'Model', 'Zone', 'Server', 'Storage') as $c) @unlink("$stub_dir/$c.php");
@rmdir($stub_dir);
print $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
