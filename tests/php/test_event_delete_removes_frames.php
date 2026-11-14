<?php
// Deleting an event in the web interface must remove its Frames, Stats and Event_Data rows
// and its files along with the Events row.
//
// ZM_OPT_FAST_DELETE used to make ZM\Event::delete() remove only the Events
// row and leave the rest for zmaudit, which only runs when ZM_RUN_AUDIT is on,
// so with the audit off the Frames table grew without bound. The option is
// gone; a box whose Config table still has it on (until zmupdate.pl rebuilds
// Config) defines the constant, so the test defines it too.
//
// Run as: php tests/php/test_event_delete_removes_frames.php
//
// The real ZM\Event and ZM\ZM_Object are used; the database and filesystem
// calls are stubs that record what was done.

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

define('ZM_OPT_FAST_DELETE', true);

function Warning($s) {}
function Error($s) {}
function Debug($s) {}
function Info($s) {}

$queries = array();
$deleted_paths = array();
$event_row = array('Id' => 7, 'MonitorId' => 3, 'StorageId' => 1, 'Scheme' => 'Medium',
  'StartDateTime' => '2026-09-01 10:00:00', 'Archived' => 0, 'SecondaryStorageId' => 0);

function dbQuery($sql, $params = null) {
  global $queries;
  $queries[] = preg_replace('/\s+/', ' ', trim($sql)).' ['.implode(',', (array)$params).']';
  return true;
}
function dbFetchOne($sql, $col = null, $params = null) {
  global $event_row;
  return $event_row;
}
function deletePath($path) {
  global $deleted_paths;
  $deleted_paths[] = $path;
}
class TestConn {
  public $committed = false;
  public function beginTransaction() {}
  public function commit() { $this->committed = true; }
  public function rollback() {}
}
$conn = new TestConn();
function zmDbConn() { return $GLOBALS['conn']; }

class Storage {
  public function __construct($id = null) {}
  public static function find_one($params) { return new Storage(); }
  public function Path() { return '/var/cache/zoneminder/events'; }
  public function Scheme($s = null) { return 'Medium'; }
}

require_once __DIR__.'/../../web/includes/Event.php';

$event = new Event($event_row);
$event->delete();

check('the event\'s Stats rows are deleted', in_array('DELETE FROM Stats WHERE EventId = ? [7]', $queries), true);
check('the event\'s Event_Data rows are deleted', in_array('DELETE FROM Event_Data WHERE EventId = ? [7]', $queries), true);
check('the event\'s Frames rows are deleted', in_array('DELETE FROM Frames WHERE EventId = ? [7]', $queries), true);
check('the Events row is deleted', in_array('DELETE FROM Events WHERE Id = ? [7]', $queries), true);
check('the Events row goes last, after the rows that refer to it',
  end($queries), 'DELETE FROM Events WHERE Id = ? [7]');
check('the event\'s files are deleted', $deleted_paths, array('/var/cache/zoneminder/events/3/2026-09-01/7'));
check('the deletes are committed', $conn->committed, true);

foreach (array('database.php', 'Storage.php', 'functions.php', 'Event_Tag.php', 'Tag.php') as $stub) {
  @unlink($stubdir.'/'.$stub);
}
@rmdir($stubdir);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
