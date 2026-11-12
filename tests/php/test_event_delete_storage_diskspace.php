<?php
// ZM\Event::delete() must subtract the event's DiskSpace from its Storage
// after the delete commits, as the Perl delete does (issue #5605).
//
// Run as: php tests/php/test_event_delete_storage_diskspace.php

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

$stubdir = sys_get_temp_dir().'/zm_event_delete_stubs_'.getmypid();
@mkdir($stubdir, 0700, true);
foreach (array('database.php', 'Storage.php', 'functions.php', 'Event_Tag.php', 'Tag.php') as $stub) {
  file_put_contents($stubdir.'/'.$stub, "<?php\n");
}
set_include_path($stubdir.PATH_SEPARATOR.__DIR__.'/../../web/includes'.PATH_SEPARATOR.get_include_path());

$calls = array();
$row = array();

function Warning($s) {}
function Error($s) {}
function Debug($s) {}
function Info($s) {}
function dbFetchOne($sql, $col = null, $values = null) {
  global $row;
  return $row;
}
function dbQuery($sql, $params = null) {
  global $calls;
  $calls[] = array('sql' => $sql, 'params' => $params);
  return true;
}
function deletePath($path) {
  global $calls;
  $calls[] = array('sql' => 'deletePath', 'params' => $path);
}

class StubDbConn {
  public function beginTransaction() { global $calls; $calls[] = array('sql' => 'begin', 'params' => null); }
  public function commit() { global $calls; $calls[] = array('sql' => 'commit', 'params' => null); }
  public function rollback() { global $calls; $calls[] = array('sql' => 'rollback', 'params' => null); }
}
function zmDbConn() {
  return new StubDbConn();
}

class Storage {
  private $id;
  private $scheme;
  public function __construct($id = null) { $this->id = $id; }
  public static function find_one($params) { return new Storage($params['Id']); }
  public function Id() { return $this->id; }
  public function Path() { return '/var/cache/zoneminder/events'; }
  public function Scheme($s = null) { if ($s) $this->scheme = $s; return $this->scheme; }
}

class Monitor {}

require_once __DIR__.'/../../web/includes/Event.php';

function run_delete($fields) {
  global $calls, $row;
  $calls = array();
  $row = $fields;
  $event = new Event($fields);
  $event->delete();
  return $calls;
}

function index_of($calls, $prefix) {
  foreach ($calls as $i => $c) {
    if (strpos($c['sql'], $prefix) === 0) return $i;
  }
  return false;
}

$base = array('Id' => 77, 'MonitorId' => 1, 'StorageId' => 3, 'DiskSpace' => 12345,
  'Scheme' => 'Medium', 'Archived' => 0, 'StartDateTime' => '2024-05-06 07:08:09');

$calls = run_delete($base);
$u = index_of($calls, 'UPDATE Storage');
$del = index_of($calls, 'DELETE FROM Events');
$commit = index_of($calls, 'commit');
check('storage update issued', $u !== false, true);
check('storage update params', $u !== false ? $calls[$u]['params'] : null, array(12345, 3));
check('storage update after DELETE FROM Events', $u !== false && $del !== false && $u > $del, true);
check('storage update after commit', $u !== false && $commit !== false && $u > $commit, true);
check('storage update is a subtraction', $u !== false ? strpos($calls[$u]['sql'], 'DiskSpace,0)-?') !== false : false, true);

$calls = run_delete(array_merge($base, array('DiskSpace' => null)));
check('NULL DiskSpace: event deleted', index_of($calls, 'DELETE FROM Events') !== false, true);
check('NULL DiskSpace: no storage update', index_of($calls, 'UPDATE Storage'), false);

$calls = run_delete(array_merge($base, array('StorageId' => 0)));
check('StorageId 0: event deleted', index_of($calls, 'DELETE FROM Events') !== false, true);
check('StorageId 0: no storage update', index_of($calls, 'UPDATE Storage'), false);

$calls = run_delete(array_merge($base, array('Archived' => 1)));
check('archived: event not deleted', index_of($calls, 'DELETE FROM Events'), false);
check('archived: no storage update', index_of($calls, 'UPDATE Storage'), false);

echo "\n$passes passed, $failures failed\n";
array_map('unlink', glob($stubdir.'/*'));
@rmdir($stubdir);
exit($failures ? 1 : 0);
