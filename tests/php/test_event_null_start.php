<?php
// An event without a StartDateTime has no date-based path: Deep and Medium
// must not resolve to a 1970 path or delete anything, Shallow still deletes.
//
// Run as: php tests/php/test_event_null_start.php

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
  'Archived' => 0, 'StartDateTime' => null);

foreach (array('Deep', 'Medium') as $scheme) {
  $event = new Event(array_merge($base, array('Scheme' => $scheme)));
  check("$scheme: no relative path", $event->Relative_Path(), '');
  check("$scheme: no path", $event->Path(), '');
  check("$scheme: no link path", $event->Link_Path(), '');

  $calls = run_delete(array_merge($base, array('Scheme' => $scheme)));
  check("$scheme: no files deleted", index_of($calls, 'deletePath'), false);
  check("$scheme: event row kept", index_of($calls, 'DELETE FROM Events'), false);
  check("$scheme: rolled back", index_of($calls, 'rollback') !== false, true);
  check("$scheme: no storage update", index_of($calls, 'UPDATE Storage'), false);

  $event = new Event(array_merge($base, array('Scheme' => $scheme, 'StartDateTime' => '')));
  check("$scheme: empty StartDateTime has no relative path", $event->Relative_Path(), '');
}

$event = new Event(array_merge($base, array('Scheme' => 'Shallow')));
check('Shallow: relative path', $event->Relative_Path(), '1/77');
$calls = run_delete(array_merge($base, array('Scheme' => 'Shallow')));
$p = index_of($calls, 'deletePath');
check('Shallow: event files deleted', $p !== false ? $calls[$p]['params'] : null, '/var/cache/zoneminder/events/1/77');
check('Shallow: event row deleted', index_of($calls, 'DELETE FROM Events') !== false, true);

$calls = run_delete(array_merge($base, array('Scheme' => 'Medium', 'StartDateTime' => '2024-05-06 07:08:09')));
$p = index_of($calls, 'deletePath');
check('Medium with start: files deleted', $p !== false ? strpos($calls[$p]['params'], '/var/cache/zoneminder/events/1/2024-05-0') === 0 : false, true);

echo "\n$passes passed, $failures failed\n";
array_map('unlink', glob($stubdir.'/*'));
@rmdir($stubdir);
exit($failures ? 1 : 0);
