<?php
// ZM\Event::GenerateVideo() is called by an export for every event without a
// video file. A second export finds the video made by the first one and must
// return its name, not fail on the log line.
//
// Run as: php tests/php/test_event_generate_video_exists.php

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

$stubdir = sys_get_temp_dir().'/zm_event_genvideo_'.getmypid();
@mkdir($stubdir, 0700, true);
foreach (array('database.php', 'Storage.php', 'functions.php', 'Event_Tag.php', 'Tag.php') as $stub) {
  file_put_contents($stubdir.'/'.$stub, "<?php\n");
}
set_include_path($stubdir.PATH_SEPARATOR.__DIR__.'/../../web/includes'.PATH_SEPARATOR.get_include_path());

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

require_once __DIR__.'/../../web/includes/Event.php';

$dir = $stubdir.'/storage/1/77';
mkdir($dir, 0700, true);
file_put_contents($dir.'/Event-77.mp4', 'video');

$event = new Event(array('Id'=>77, 'Name'=>'Event-77', 'MonitorId'=>1, 'StorageId'=>3, 'Scheme'=>'Shallow'));
try {
  $got = $event->GenerateVideo();
} catch (\Throwable $e) {
  $got = get_class($e);
}
check('existing generated video is returned', $got, 'Event-77.mp4');

unlink($dir.'/Event-77.mp4');

// An export gives its own directory so the video is not written to the events'
// storage; a video already there is found, and the event directory stays empty.
$outdir = $stubdir.'/out';
mkdir($outdir, 0700);
file_put_contents($outdir.'/Event-77.mp4', 'video');
try {
  $got = $event->GenerateVideo(0, 0, 0, 0, false, 'mp4', '', $outdir);
} catch (\Throwable $e) {
  $got = get_class($e);
}
check('video in the given output directory is returned', $got, 'Event-77.mp4');
check('event directory is left untouched', glob($dir.'/*'), array());
unlink($outdir.'/Event-77.mp4');
rmdir($outdir);
rmdir($dir);
rmdir($stubdir.'/storage/1');
rmdir($stubdir.'/storage');
array_map('unlink', glob($stubdir.'/*'));
@rmdir($stubdir);
exit($failures ? 1 : 0);
