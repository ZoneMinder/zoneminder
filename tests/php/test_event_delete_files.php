<?php
// ZM\Event::delete() must remove the secondary storage copy for every scheme
// and must leave the files alone when the primary Storage has DoDelete off,
// as the Perl delete does.
//
// Run as: php tests/php/test_event_delete_files.php

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

$stubdir = sys_get_temp_dir().'/zm_event_delete_files_'.getmypid();
@mkdir($stubdir, 0700, true);
foreach (array('database.php', 'Storage.php', 'functions.php', 'Event_Tag.php', 'Tag.php') as $stub) {
  file_put_contents($stubdir.'/'.$stub, "<?php\n");
}
set_include_path($stubdir.PATH_SEPARATOR.__DIR__.'/../../web/includes'.PATH_SEPARATOR.get_include_path());

$root = $stubdir.'/storage';
$storages = array(
  1 => array('path' => $root.'/one', 'dodelete' => 1),
  2 => array('path' => $root.'/two', 'dodelete' => 1),
);
$calls = array();

function Warning($s) {}
function Error($s) {}
function Debug($s) {}
function Info($s) {}
function dbFetchOne($sql, $col = null, $values = null) {
  return array();
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
  public function beginTransaction() {}
  public function commit() {}
  public function rollback() {}
}
function zmDbConn() {
  return new StubDbConn();
}

class Storage {
  private $id;
  public function __construct($id = null) { $this->id = $id; }
  public static function find_one($params) { return new Storage($params['Id']); }
  public function Id() { return $this->id; }
  public function Path() { global $storages; return $storages[$this->id]['path']; }
  public function DoDelete() { global $storages; return $storages[$this->id]['dodelete']; }
  public function Scheme($s = null) { return 'Medium'; }
}

class Monitor {}

require_once __DIR__.'/../../web/includes/Event.php';

function paths_deleted($calls) {
  $out = array();
  foreach ($calls as $c) {
    if ($c['sql'] == 'deletePath') $out[] = $c['params'];
  }
  return $out;
}

function run_delete($fields) {
  global $calls;
  $calls = array();
  $event = new Event($fields);
  $event->delete();
  return $calls;
}

function has_call($calls, $prefix) {
  foreach ($calls as $c) {
    if (strpos($c['sql'], $prefix) === 0) return true;
  }
  return false;
}

// A Deep event on storage 1 with its day link, as the daemons create it
$day = $root.'/one/1/24/05/06';
mkdir($day.'/07/08', 0755, true);
symlink('07/08/09', $day.'/.77');
mkdir($day.'/07/08/09', 0755, true);

$base = array('Id' => 77, 'MonitorId' => 1, 'StorageId' => 1, 'SecondaryStorageId' => 2,
  'Scheme' => 'Deep', 'Archived' => 0, 'StartDateTime' => '2024-05-06 07:08:09');

$calls = run_delete($base);
$paths = paths_deleted($calls);
check('deep: primary event dir deleted', in_array($day.'/07/08/09', $paths), true);
check('deep: primary link deleted', in_array($day.'/.77', $paths), true);
check('deep: secondary event dir deleted', in_array($root.'/two/1/24/05/06/07/08/09', $paths), true);
check('deep: secondary link deleted', in_array($root.'/two/1/24/05/06/.77', $paths), true);
check('deep: db row deleted', has_call($calls, 'DELETE FROM Events'), true);

$calls = run_delete(array_merge($base, array('SecondaryStorageId' => null)));
check('deep without secondary: nothing under storage 2', count(array_filter(paths_deleted($calls), function ($p) use ($root) {
  return strpos($p, $root.'/two') === 0;
})), 0);

$storages[1]['dodelete'] = 0;
$calls = run_delete($base);
check('deep DoDelete off: no files deleted', paths_deleted($calls), array());
check('deep DoDelete off: db row deleted', has_call($calls, 'DELETE FROM Events'), true);

$calls = run_delete(array_merge($base, array('Scheme' => 'Medium')));
check('medium DoDelete off: no files deleted', paths_deleted($calls), array());
check('medium DoDelete off: db row deleted', has_call($calls, 'DELETE FROM Events'), true);

$storages[1]['dodelete'] = 1;
$calls = run_delete(array_merge($base, array('Scheme' => 'Medium')));
check('medium: primary and secondary deleted', paths_deleted($calls),
  array($root.'/one/1/2024-05-06/77', $root.'/two/1/2024-05-06/77'));

echo "\n$passes passed, $failures failed\n";
exec('rm -rf '.escapeshellarg($stubdir));
exit($failures ? 1 : 0);
