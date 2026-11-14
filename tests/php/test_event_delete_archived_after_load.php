<?php
namespace ZM;

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

$stubdir = sys_get_temp_dir().'/zm_event_delete_arch_stubs_'.getmypid();
@mkdir($stubdir, 0700, true);
foreach (array('database.php', 'Storage.php', 'functions.php', 'Event_Tag.php', 'Tag.php') as $stub) {
  file_put_contents($stubdir.'/'.$stub, "<?php\n");
}
set_include_path($stubdir.PATH_SEPARATOR.__DIR__.'/../../web/includes'.PATH_SEPARATOR.get_include_path());

$calls = array();
$db_row = array();

function Warning($s) {}
function Error($s) {}
function Debug($s) {}
function Info($s) {}
function dbFetchOne($sql, $col = null, $values = null) {
  global $db_row;
  return $db_row;
}
function dbQuery($sql, $params = null) {
  global $calls;
  $calls[] = $sql;
  return true;
}
function deletePath($path) {
  global $calls;
  $calls[] = 'deletePath '.$path;
}

class StubDbConn {
  public function beginTransaction() {}
  public function commit() { global $calls; $calls[] = 'commit'; }
  public function rollback() { global $calls; $calls[] = 'rollback'; }
}
function zmDbConn() {
  return new StubDbConn();
}

class Storage {
  public function __construct($id = null) {}
  public static function find_one($params) { return new Storage($params['Id']); }
  public function Id() { return 3; }
  public function Path() { return '/var/cache/zoneminder/events'; }
  public function Scheme($s = null) { return 'Medium'; }
}

class Monitor {}

require_once __DIR__.'/../../web/includes/Event.php';

$loaded = array('Id' => 77, 'MonitorId' => 1, 'StorageId' => 3, 'DiskSpace' => 100,
  'Scheme' => 'Medium', 'Archived' => 0, 'StartDateTime' => '2024-05-06 07:08:09');

$db_row = array_merge($loaded, array('Archived' => 1));
$event = new Event($loaded);
$event->delete();
check('archived since it was loaded: nothing is deleted', $calls, array('rollback'));

$calls = array();
$db_row = $loaded;
$event = new Event($loaded);
$event->delete();
check('still unarchived: files are deleted', in_array('deletePath /var/cache/zoneminder/events/1/2024-05-06/77', $calls), true);

echo "\n$passes passed, $failures failed\n";
array_map('unlink', glob($stubdir.'/*'));
@rmdir($stubdir);
exit($failures ? 1 : 0);
