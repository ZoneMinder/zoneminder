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

function rm_rf($path) {
  if (is_link($path) or is_file($path)) {
    unlink($path);
  } else if (is_dir($path)) {
    foreach (scandir($path) as $f) {
      if ($f != '.' and $f != '..') rm_rf($path.'/'.$f);
    }
    rmdir($path);
  }
}

$stubdir = sys_get_temp_dir().'/zm_event_delete_medium_stubs_'.getmypid();
@mkdir($stubdir, 0700, true);
foreach (array('database.php', 'Storage.php', 'functions.php', 'Event_Tag.php', 'Tag.php') as $stub) {
  file_put_contents($stubdir.'/'.$stub, "<?php\n");
}
set_include_path($stubdir.PATH_SEPARATOR.__DIR__.'/../../web/includes'.PATH_SEPARATOR.get_include_path());

$root = sys_get_temp_dir().'/zm_event_delete_medium_root_'.getmypid();
$row = array('Id' => 77, 'MonitorId' => 1, 'StorageId' => 3, 'DiskSpace' => 0,
  'Scheme' => 'Medium', 'Archived' => 0, 'StartDateTime' => '2024-05-06 07:08:09');

function Warning($s) {}
function Error($s) {}
function Debug($s) {}
function Info($s) {}
function dbFetchOne($sql, $col = null, $values = null) {
  global $row;
  return $row;
}
function dbQuery($sql, $params = null) {
  return true;
}
function deletePath($path) {
  rm_rf($path);
}

class StubDbConn {
  public function beginTransaction() {}
  public function commit() {}
  public function rollback() {}
}
function zmDbConn() {
  return new StubDbConn();
}

class Storage {
  public function __construct($id = null) {}
  public static function find_one($params) { return new Storage($params['Id']); }
  public function Id() { return 3; }
  public function Path() { global $root; return $root; }
  public function Scheme($s = null) { return 'Medium'; }
}

class Monitor {}

require_once __DIR__.'/../../web/includes/Event.php';

rm_rf($root);
$event = new Event($row);
$day_path = $root.'/1/2024-05-06';
mkdir($day_path.'/77', 0777, true);
touch($day_path.'/77/00001-capture.jpg');
mkdir($day_path.'/78', 0777, true);

$event->delete();

check('the event files are removed', file_exists($day_path.'/77'), false);
check('the date directory is kept while it holds another event', is_dir($day_path.'/78'), true);

rmdir($day_path.'/78');
mkdir($day_path.'/77', 0777, true);
$event->delete();

check('the empty date directory is removed', file_exists($day_path), false);
check('the monitor directory is kept', is_dir($root.'/1'), true);

rm_rf($root);
echo "\n$passes passed, $failures failed\n";
array_map('unlink', glob($stubdir.'/*'));
@rmdir($stubdir);
exit($failures ? 1 : 0);
