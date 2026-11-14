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

$stubdir = sys_get_temp_dir().'/zm_event_delete_fail_stubs_'.getmypid();
@mkdir($stubdir, 0700, true);
foreach (array('database.php', 'Storage.php', 'functions.php', 'Event_Tag.php', 'Tag.php') as $stub) {
  file_put_contents($stubdir.'/'.$stub, "<?php\n");
}
set_include_path($stubdir.PATH_SEPARATOR.__DIR__.'/../../web/includes'.PATH_SEPARATOR.get_include_path());

$calls = array();
$row = array();
$fail_on = '';

function Warning($s) {}
function Error($s) {}
function Debug($s) {}
function Info($s) {}
function dbFetchOne($sql, $col = null, $values = null) {
  global $row;
  return $row;
}
function dbQuery($sql, $params = null) {
  global $calls, $fail_on;
  $calls[] = $sql;
  return ($fail_on !== '' and strpos($sql, $fail_on) === 0) ? null : true;
}
function deletePath($path) {
  global $calls;
  $calls[] = 'deletePath '.$path;
}

class StubDbConn {
  public function beginTransaction() { global $calls; $calls[] = 'begin'; }
  public function commit() { global $calls; $calls[] = 'commit'; }
  public function rollback() { global $calls; $calls[] = 'rollback'; }
}
function zmDbConn() {
  return new StubDbConn();
}

class Storage {
  private $id;
  public function __construct($id = null) { $this->id = $id; }
  public static function find_one($params) { return new Storage($params['Id']); }
  public function Id() { return $this->id; }
  public function Path() { return '/var/cache/zoneminder/events'; }
  public function Scheme($s = null) { return 'Medium'; }
}

class Monitor {}

require_once __DIR__.'/../../web/includes/Event.php';

function run_delete($failing_sql) {
  global $calls, $row, $fail_on;
  $calls = array();
  $fail_on = $failing_sql;
  $row = array('Id' => 77, 'MonitorId' => 1, 'StorageId' => 3, 'DiskSpace' => 12345,
    'Scheme' => 'Medium', 'Archived' => 0, 'StartDateTime' => '2024-05-06 07:08:09');
  $event = new Event($row);
  $event->delete();
  return $calls;
}

function any_with($calls, $prefix) {
  foreach ($calls as $c) {
    if (strpos($c, $prefix) === 0) return true;
  }
  return false;
}

$calls = run_delete('DELETE FROM Events');
check('failed Events delete: files are kept', any_with($calls, 'deletePath'), false);
check('failed Events delete: storage is not credited', any_with($calls, 'UPDATE Storage'), false);
check('failed Events delete: rolled back', any_with($calls, 'rollback'), true);
check('failed Events delete: not committed', any_with($calls, 'commit'), false);

$calls = run_delete('DELETE FROM Frames');
check('failed Frames delete: files are kept', any_with($calls, 'deletePath'), false);
check('failed Frames delete: Events row is not deleted', any_with($calls, 'DELETE FROM Events'), false);

$calls = run_delete('');
check('successful delete: files are removed', any_with($calls, 'deletePath /var/cache/zoneminder/events/1/2024-05-06/77'), true);
check('successful delete: files are removed after the commit',
  array_search('commit', $calls) < array_search('deletePath /var/cache/zoneminder/events/1/2024-05-06/77', $calls), true);

echo "\n$passes passed, $failures failed\n";
array_map('unlink', glob($stubdir.'/*'));
@rmdir($stubdir);
exit($failures ? 1 : 0);
