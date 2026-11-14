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

$stubdir = sys_get_temp_dir().'/zm_genvideo_stubs_'.getmypid();
@mkdir($stubdir, 0700, true);
foreach (array('database.php', 'Storage.php', 'functions.php', 'Event_Tag.php', 'Tag.php') as $stub) {
  file_put_contents($stubdir.'/'.$stub, "<?php\n");
}
set_include_path($stubdir.PATH_SEPARATOR.__DIR__.'/../../web/includes'.PATH_SEPARATOR.get_include_path());

function Warning($s) {}
function Error($s) {}
function Debug($s) {}
function Info($s) {}

$eventdir = $stubdir.'/event';
@mkdir($eventdir, 0700, true);
$fake = $stubdir.'/ffmpeg';
file_put_contents($fake, "#!/bin/sh\nfor last; do :; done\n: > \"\$last\"\n");
chmod($fake, 0755);

define('ZM_PATH_FFMPEG', $fake);
define('ZM_FFMPEG_INPUT_OPTIONS', '');
define('ZM_FFMPEG_OUTPUT_OPTIONS', '');
define('ZM_EVENT_IMAGE_DIGITS', 5);

require_once __DIR__.'/../../web/includes/Event.php';

class TestEvent extends Event {
  public function Path() { return $GLOBALS['eventdir']; }
}

$event = new TestEvent(array('Id' => 3, 'Name' => 'one frame', 'Width' => 160, 'Height' => 120, 'Frames' => 1, 'Length' => 0, 'DefaultVideo' => ''));
$file = null;
try {
  $file = $event->GenerateVideo();
} catch (\Throwable $e) {
  $file = get_class($e);
}
check('an event with no length still gets a video', $file, 'one_frame.mp4');

echo "\n$passes passed, $failures failed\n";
array_map('unlink', glob($eventdir.'/*'));
@rmdir($eventdir);
array_map('unlink', glob($stubdir.'/*'));
@rmdir($stubdir);
exit($failures ? 1 : 0);
