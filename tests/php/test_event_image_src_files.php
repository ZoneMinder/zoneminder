<?php
// ZM\Event::getImageSrc() must find the JPEGs the daemons actually write.
// The analysis file is named "%05d-analyse.jpg" (src/zm_config.cpp), and an
// event whose frames were saved with analysis JPEGs only must return that
// file instead of falling back to a missing "-capture.jpg" and logging
// "Capture file does not exist".
//
// It must also keep the requested frame id when Frame::find_one() and
// find_virtual_frame() both come up empty: the fallback row used to be built
// from the already-nulled $frame, so FrameId came back as ''.
//
// Run as: php tests/php/test_event_image_src_files.php [path/to/Event.php]
//   php tests/php/test_event_image_src_files.php /tmp/hms-tests/Event-orig.php  # against another copy

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

$errors = array();
function Error($s) { global $errors; $errors[] = $s; }
function Warning($s) {}
function Debug($s) {}
function Info($s) {}

$frameRows = array();
function dbFetchOne($sql, $col = null, $values = null) { return null; }

$stubdir = sys_get_temp_dir().'/zm_event_image_src_stubs_'.getmypid();
@mkdir($stubdir, 0700, true);
foreach (array('database.php', 'Storage.php', 'functions.php', 'Event_Tag.php', 'Tag.php') as $stub) {
  file_put_contents($stubdir.'/'.$stub, "<?php\n");
}

$eventFile = isset($argv[1]) ? $argv[1] : __DIR__.'/../../web/includes/Event.php';
if (!is_file($eventFile)) {
  echo "FAIL - cannot read $eventFile\n";
  exit(1);
}
$eventFile = realpath($eventFile);
set_include_path(
  $stubdir.PATH_SEPARATOR.
  dirname($eventFile).PATH_SEPARATOR.
  __DIR__.'/../../web/includes'.PATH_SEPARATOR.
  get_include_path()
);

if (!defined('ZM_EVENT_IMAGE_DIGITS')) define('ZM_EVENT_IMAGE_DIGITS', 5);
if (!defined('SCALE_BASE')) define('SCALE_BASE', 100);
if (!defined('ZM_WEB_SCALE_THUMBS')) define('ZM_WEB_SCALE_THUMBS', false);

// Frame::find_one() looks in $frameRows; a row object casts to the array
// getImageSrc() expects.
class Frame {
  public $EventId = 0;
  public $FrameId = 0;
  public $Type = 'Normal';
  public $Delta = 0;
  public function __construct($row = array()) {
    foreach ($row as $k => $v) $this->$k = $v;
  }
  public static function find_one($params = array(), $options = array()) {
    global $frameRows;
    foreach ($frameRows as $row) {
      $ok = true;
      foreach ($params as $k => $v) {
        if (!isset($row[$k]) or $row[$k] != $v) $ok = false;
      }
      if ($ok) return new Frame($row);
    }
    return null;
  }
}

class Storage {
  public function __construct($id = null) {}
  public static function find_one($params = array()) { return new Storage(); }
  public function Path() { return ''; }
  public function Scheme($s = null) { return 'Medium'; }
}

require_once $eventFile;

class TestEvent extends Event {
  public $dir = '';
  public function Path() { return $this->dir; }
  public function Storage($new = null) { return new Storage(); }
  public function DefaultVideo() { return ''; }
  public function find_virtual_frame($fid) { return null; }
}

$dir = sys_get_temp_dir().'/zm_event_image_src_'.getmypid();
@mkdir($dir, 0700, true);
// The bytes do not matter: getImageSrc() only checks existence and size.
file_put_contents($dir.'/00005-analyse.jpg', 'not-really-a-jpeg');
file_put_contents($dir.'/00009-analyse.jpg', 'not-really-a-jpeg');

function run_get_image_src($dir, $frameId, $row, $captureOnly = false) {
  global $frameRows, $errors;
  $frameRows = $row === null ? array() : array($row);
  $errors = array();
  $event = new TestEvent();
  $event->dir = $dir;
  $imageData = $event->getImageSrc($frameId, SCALE_BASE, $captureOnly);
  return array('data' => $imageData, 'errors' => $errors);
}

function errors_mentioning($errors, $needle) {
  $found = array();
  foreach ($errors as $e) {
    if (stripos($e, $needle) !== false) $found[] = $e;
  }
  return $found;
}

echo "case A: alarm frame saved with an analysis JPEG only\n";
$r = run_get_image_src($dir, 5, array('Id' => 5, 'EventId' => 7, 'FrameId' => 5, 'Type' => 'Alarm', 'Delta' => 0.5));
check('image path is the analysis jpeg', $r['data']['imagePath'], $dir.'/00005-analyse.jpg');
check('image file is the analysis jpeg', $r['data']['imageFile'], $dir.'/00005-analyse.jpg');
check('analysis jpeg is detected', $r['data']['hasAnalysisImage'], true);
check('reported as an analysis image', $r['data']['isAnalysisImage'], true);
check('no "Capture file does not exist" error',
  errors_mentioning($r['errors'], 'Capture file does not exist'), array());
check('no errors at all', $r['errors'], array());

echo "case A2: normal frame saved with an analysis JPEG only\n";
$r = run_get_image_src($dir, 5, array('Id' => 5, 'EventId' => 7, 'FrameId' => 5, 'Type' => 'Normal', 'Delta' => 0.5));
check('capture fallback is the analysis jpeg', $r['data']['imagePath'], $dir.'/00005-analyse.jpg');
check('no "Capture file does not exist" error',
  errors_mentioning($r['errors'], 'Capture file does not exist'), array());

echo "case B: no Frame row and no virtual frame\n";
$r = run_get_image_src($dir, 9, null);
check('requested FrameId is kept', $r['data']['FrameId'], 9);
check('image path uses the requested FrameId', $r['data']['imagePath'], $dir.'/00009-analyse.jpg');

echo "\n$passes passed, $failures failed\n";
array_map('unlink', glob($dir.'/*'));
@rmdir($dir);
array_map('unlink', glob($stubdir.'/*'));
@rmdir($stubdir);
exit($failures ? 1 : 0);
