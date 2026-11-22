<?php
// ZM\Event::delete() must return true when the event rows were deleted (commit
// succeeded) and false on every refusal/failure path.
//
// Run as: php tests/php/test_event_delete_returns_result.php

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

$stubdir = sys_get_temp_dir().'/zm_event_delete_returns_'.getmypid();
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
  $calls[] = array('sql' => $sql, 'params' => $params);
  return true;
}
function deletePath($path) {}

class StubDbConn {
  public function beginTransaction() {}
  public function commit() { global $calls; $calls[] = array('sql' => 'commit', 'params' => null); }
  public function rollback() { global $calls; $calls[] = array('sql' => 'rollback', 'params' => null); }
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
  public function DoDelete() { return 1; }
  public function Scheme($s = null) { return 'Medium'; }
}

class Monitor {}

require_once __DIR__.'/../../web/includes/Event.php';

function has_delete_query($calls) {
  foreach ($calls as $c) {
    if (isset($c['sql']) && strpos($c['sql'], 'DELETE') === 0) return true;
  }
  return false;
}

// Archived event -> false and no DELETE query.
$calls = array();
$db_row = array();
$archived = new Event(array('Id' => 77, 'MonitorId' => 1, 'StorageId' => 3, 'DiskSpace' => 100,
  'Scheme' => 'Medium', 'Archived' => 1, 'StartDateTime' => '2024-05-06 07:08:09'));
$result = $archived->delete();
check('archived event returns false', $result, false);
check('archived event issues no DELETE query', has_delete_query($calls), false);

// Normal event -> true.
$calls = array();
$normal_row = array('Id' => 88, 'MonitorId' => 1, 'StorageId' => 3, 'DiskSpace' => 100,
  'Scheme' => 'Medium', 'Archived' => 0, 'StartDateTime' => '2024-05-06 07:08:09');
$db_row = $normal_row;
$normal = new Event($normal_row);
$result = $normal->delete();
check('normal event returns true', $result, true);

echo "\n$passes passed, $failures failed\n";
array_map('unlink', glob($stubdir.'/*'));
@rmdir($stubdir);
exit($failures ? 1 : 0);
