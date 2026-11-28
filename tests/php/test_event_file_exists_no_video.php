<?php
// ZM\Event::file_exists()/file_size() must not treat the event's storage
// directory as the event video. A directory is not a video, and a missing
// DefaultVideo file must report as absent.
//
// Run as: php tests/php/test_event_file_exists_no_video.php [repo-root]

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

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$stubdir = sys_get_temp_dir().'/zm_event_file_exists_'.getmypid();
@mkdir($stubdir, 0700, true);
foreach (array('database.php', 'Storage.php', 'functions.php', 'Event_Tag.php', 'Tag.php') as $stub) {
  file_put_contents($stubdir.'/'.$stub, "<?php\n");
}
set_include_path($stubdir.PATH_SEPARATOR.$root.'/web/includes'.PATH_SEPARATOR.get_include_path());

function Warning($s) {}
function Error($s) {}
function Debug($s) {}
function Info($s) {}

class Storage {
  public function __construct($id = null) {}
  public static function find_one($params) { return new Storage(); }
  public function Id() { return 3; }
  public function Path() { global $stubdir; return $stubdir.'/storage'; }
  public function Scheme($s = null) { return 'Shallow'; }
}
class Monitor {}

require_once $root.'/web/includes/Event.php';

$dir = $stubdir.'/storage/1/88';
mkdir($dir, 0700, true);

// The event's storage directory exists, but DefaultVideo is empty: the
// directory must not be reported as a video file.
$event = new Event(array('Id'=>88, 'MonitorId'=>1, 'StorageId'=>3, 'Scheme'=>'Shallow', 'DefaultVideo'=>''));
check('empty DefaultVideo is not a file', $event->file_exists(), false);
check('empty DefaultVideo has zero size', (int)$event->file_size(), 0);

// A real video file is found and its size reported.
file_put_contents($dir.'/88-video.mp4', 'abcde');
$event = new Event(array('Id'=>88, 'MonitorId'=>1, 'StorageId'=>3, 'Scheme'=>'Shallow', 'DefaultVideo'=>'88-video.mp4'));
check('existing video is a file', $event->file_exists(), true);
check('existing video size', $event->file_size(), 5);

// A DefaultVideo that names a missing file is not a file.
unlink($dir.'/88-video.mp4');
$event = new Event(array('Id'=>88, 'MonitorId'=>1, 'StorageId'=>3, 'Scheme'=>'Shallow', 'DefaultVideo'=>'88-video.mp4'));
check('missing video is not a file', $event->file_exists(), false);

@rmdir($dir);
@rmdir($stubdir.'/storage/1');
@rmdir($stubdir.'/storage');
array_map('unlink', glob($stubdir.'/*'));
@rmdir($stubdir);
exit($failures ? 1 : 0);
