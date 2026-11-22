<?php
// ZM\Event::DiskSpace() measures the event directory when the row has no
// DiskSpace (an event still recording). An event whose directory cannot be
// worked out has an empty Path(), and measuring that adds up the whole
// filesystem from "/".
//
// Run as: php tests/php/test_event_diskspace_no_path.php

namespace ZM;

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) {
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n";
  }
}

$stubdir = sys_get_temp_dir().'/zm_event_diskspace_'.getmypid();
@mkdir($stubdir, 0700, true);
foreach (array('database.php', 'Storage.php', 'functions.php', 'Event_Tag.php', 'Tag.php') as $stub) {
  file_put_contents($stubdir.'/'.$stub, "<?php\n");
}
set_include_path($stubdir.PATH_SEPARATOR.__DIR__.'/../../web/includes'.PATH_SEPARATOR.get_include_path());

$measured = array();
function folder_size($dir) { global $measured; $measured[] = $dir; return 4096; }
function Warning($s) {}
function Error($s) {}
function Debug($s) {}
function Info($s) {}

class Storage {
  public function __construct($id = null) {}
  public static function find_one($params) { return new Storage(); }
  public function Id() { return 3; }
  public function Path() { return '/var/cache/zoneminder/events'; }
  public function Scheme($s = null) { return 'Medium'; }
}
class Monitor {}

require_once __DIR__.'/../../web/includes/Event.php';

$event = new Event(array('Id'=>77, 'MonitorId'=>1, 'StorageId'=>3, 'Scheme'=>'Medium', 'StartDateTime'=>null, 'DiskSpace'=>null, 'EndDateTime'=>null));
$got = $event->DiskSpace();
check('no path: nothing measured', $measured, array());
check('no path: no size', $got, null);

$event = new Event(array('Id'=>78, 'MonitorId'=>1, 'StorageId'=>3, 'Scheme'=>'Shallow', 'StartDateTime'=>'2024-05-06 07:08:09', 'DiskSpace'=>null, 'EndDateTime'=>null));
$got = $event->DiskSpace();
check('with path: event directory measured', $measured, array('/var/cache/zoneminder/events/1/78'));
check('with path: size returned', $got, 4096);

array_map('unlink', glob($stubdir.'/*'));
@rmdir($stubdir);
exit($failures ? 1 : 0);
