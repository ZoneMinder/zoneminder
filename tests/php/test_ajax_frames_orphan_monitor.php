<?php
// ajax/frames.php thumbnail sizing:
//  - the monitor aspect ratio was computed for every row, even when
//    ZM_WEB_LIST_THUMB_HEIGHT supplied the height directly.
//  - for an event whose monitor row was deleted, Event::Monitor() returns an
//    empty Monitor, ViewWidth()/ViewHeight() are null and the division threw
//    DivisionByZeroError, so the Frames table never loaded (HTTP 500).
//
// The ajax file declares queryRequest(), so each scenario runs in a child php
// process. Pass an ajax/frames.php path as the first argument to test another
// copy of the file (used to show the failure against the pre-fix version).
//
// Run as: php tests/php/test_ajax_frames_orphan_monitor.php [path/to/ajax/frames.php]

namespace {
$zmFramesFile = isset($argv[1]) ? $argv[1] : __DIR__.'/../../web/ajax/frames.php';

if ( !isset($argv[2]) ) {
  $scenarios = array('orphan_with_thumb_height', 'orphan_falls_back_to_event', 'orphan_without_dimensions', 'normal_monitor');
  $failures = 0;
  foreach ( $scenarios as $scenario ) {
    echo "== $scenario ==\n";
    $out = array();
    $rc = 0;
    exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' '
        .escapeshellarg($zmFramesFile).' '.escapeshellarg($scenario).' 2>&1', $out, $rc);
    echo implode("\n", $out)."\n";
    if ( $rc ) $failures++;
  }
  echo $failures ? "\n$failures scenario(s) FAILED\n" : "\nall scenarios passed\n";
  exit($failures ? 1 : 0);
}

$scenario = $argv[2];

class Done extends Exception {}

// Scenario fixtures: monitor dimensions (empty monitor = orphaned event),
// event dimensions and thumbnail configuration.
switch ( $scenario ) {
  case 'orphan_with_thumb_height':
    $monitorWidth = 0; $monitorHeight = 480;
    $eventWidth = 1280; $eventHeight = 720;
    $thumbWidth = 48; $thumbHeight = 32;
    break;
  case 'orphan_falls_back_to_event':
    $monitorWidth = null; $monitorHeight = null;
    $eventWidth = 1280; $eventHeight = 720;
    $thumbWidth = 48; $thumbHeight = 0;
    break;
  case 'orphan_without_dimensions':
    $monitorWidth = null; $monitorHeight = null;
    $eventWidth = 0; $eventHeight = 0;
    $thumbWidth = 48; $thumbHeight = 0;
    break;
  case 'normal_monitor':
    $monitorWidth = 640; $monitorHeight = 480;
    $eventWidth = 1280; $eventHeight = 720;
    $thumbWidth = 48; $thumbHeight = 0;
    break;
  default:
    fwrite(STDERR, "unknown scenario '$scenario'\n");
    exit(2);
}

$GLOBALS['test_monitor_width'] = $monitorWidth;
$GLOBALS['test_monitor_height'] = $monitorHeight;
$GLOBALS['test_event_width'] = $eventWidth;
$GLOBALS['test_event_height'] = $eventHeight;
$GLOBALS['zm_errors'] = array();
$GLOBALS['out'] = null;

define('ZM_WEB_LIST_THUMBS', 1);
define('ZM_EVENT_IMAGE_DIGITS', 6);
define('ZM_WEB_LIST_THUMB_WIDTH', $thumbWidth);
define('ZM_WEB_LIST_THUMB_HEIGHT', $thumbHeight);

$stubdir = sys_get_temp_dir().'/zm_frames_stubs_'.getmypid();
@mkdir($stubdir.'/includes', 0700, true);
file_put_contents($stubdir.'/includes/Filter.php', "<?php\nnamespace ZM;\nclass Filter {}\n");
set_include_path($stubdir.PATH_SEPARATOR.get_include_path());
register_shutdown_function(function() use ($stubdir) {
  @unlink($stubdir.'/includes/Filter.php'); @rmdir($stubdir.'/includes'); @rmdir($stubdir);
});

class Fmt { public function format($t) { return '2026-01-01 00:00:00'; } }
$dateTimeFormatter = new Fmt();
class TestUser {
  public function Username() { return 'tester'; }
}
$user = new TestUser();
}

namespace ZM {
  class Monitor {
    public function ViewWidth() { return $GLOBALS['test_monitor_width']; }
    public function ViewHeight() { return $GLOBALS['test_monitor_height']; }
  }
  class Event {
    private $id;
    public function __construct($id) { $this->id = (int)$id; }
    public function canView() { return true; }
    public function Monitor() { return new Monitor(); }
    public function MonitorId() { return 1; }
    public function Path() { return '/nonexistent-zm-frames-test'; }
    public function Width() { return $GLOBALS['test_event_width']; }
    public function Height() { return $GLOBALS['test_event_height']; }
  }
  function Error($m) { $GLOBALS['zm_errors'][] = $m; }
}

namespace {
  function canView($area) { return true; }
  function validInt($v) { return (int)$v; }
  function validHtmlStr($s) { return htmlspecialchars((string)$s); }
  function ajaxError($m) { $GLOBALS['out'] = 'error: '.$m; throw new Done(); }
  function ajaxResponse($d) { $GLOBALS['out'] = $d; throw new Done(); }
  function dbFetchAll($sql, $col = null, $args = null) {
    return array(array(
      'FrameId' => 7, 'EventId' => 5, 'Type' => 'Normal',
      'TimeStamp' => '2026-01-01 00:00:00', 'Delta' => 0, 'Score' => 0,
    ));
  }
  function dbFetchOne($sql, $col = null, $args = null) { return 0; }

  $_REQUEST = array('task' => 'query', 'eid' => '5');

  $caught = null;
  try {
    include $GLOBALS['zmFramesFile'];
  } catch ( Done $e ) {
  } catch ( Throwable $t ) {
    $caught = get_class($t).': '.$t->getMessage();
  }

  $data = is_array($GLOBALS['out']) ? $GLOBALS['out'] : array();
  $thumb = $data['rows'][0]['Thumbnail'] ?? '';

  $failures = 0;
  function check($n, $got, $want) {
    global $failures;
    if ( $got === $want ) { echo "ok - $n\n"; } else { $failures++; echo "FAIL - $n (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
  }

  check('no exception', $caught, null);
  check('one frame row returned', count($data['rows'] ?? array()), 1);
  check('thumbnail markup built', is_string($thumb) && $thumb !== '', true);

  $width = $height = null;
  if ( preg_match('/ width=(\d+)\s+height=(\d+)\s+img_src="/', $thumb, $m) ) {
    $width = (int)$m[1];
    $height = (int)$m[2];
  } else if ( preg_match('/ width=(\d+)\s+img_src="/', $thumb, $m) ) {
    $width = (int)$m[1];
  }

  switch ( $scenario ) {
    case 'orphan_with_thumb_height':
      // A configured height wins and no monitor ratio is needed.
      check('width', $width, 48);
      check('height', $height, 32);
      break;
    case 'orphan_falls_back_to_event':
      // 1280x720 event -> 48 * 720 / 1280 = 27.
      check('width', $width, 48);
      check('height', $height, 27);
      break;
    case 'orphan_without_dimensions':
      // No monitor and no event dimensions: omit the height rather than throw.
      check('width', $width, 48);
      check('no height attribute', $height, null);
      check('no height in source', strpos($thumb, 'height='), false);
      break;
    case 'normal_monitor':
      // Unchanged: monitor 640x480 with a 48px thumb -> 36.
      check('width', $width, 48);
      check('height', $height, 36);
      check('source carries both dimensions', strpos($thumb, 'width=48&amp;height=36') !== false, true);
      break;
  }

  exit($failures ? 1 : 0);
}
