<?php
// views/frames.php must answer an event that does not exist, or that the user
// may not view, with the error view (as views/frame.php does) instead of an
// empty Frames page that only fails later in its ajax request, and must not
// raise an undefined index warning when eid is missing.
//
// Run as: php tests/php/test_frames_view_event_access.php [path/to/web]

namespace ZM {
  class Event {
    public static $viewable = array();
    private $id;
    public function __construct($id) { $this->id = $id; }
    public function Id() { return $this->id; }
    public function Name() { return 'Event '.$this->id; }
    public function canView() { return in_array($this->id, self::$viewable); }
  }
}

namespace {
$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../../web';
$file = $root.'/skins/classic/views/frames.php';

$stubdir = sys_get_temp_dir().'/zm_frames_view_stubs_'.getmypid();
@mkdir($stubdir.'/includes', 0700, true);
file_put_contents($stubdir.'/includes/Frame.php', "<?php\n");
file_put_contents($stubdir.'/includes/Filter.php', "<?php\n");
set_include_path($stubdir.PATH_SEPARATOR.get_include_path());

function canView($what) { return true; }
function validInt($v) { return is_scalar($v) ? preg_replace('/[^\-\d]/', '', $v) : ''; }
function validHtmlStr($s) { return htmlspecialchars($s, ENT_QUOTES); }
function xhtmlHeaders($f, $t) {}
function getBodyTopHTML() {}
function getNavBarHTML() {}
function translate($s) { return $s; }
function i18n() { return 'en'; }
function xhtmlFooter() {}

$warnings = 0;
set_error_handler(function() use (&$warnings) { $warnings++; return true; });

function run($file, $request) {
  $_REQUEST = $request;
  $inner = function() use ($file) {
    $view = 'frames';
    ob_start();
    include $file;
    ob_end_clean();
    return $view;
  };
  return $inner();
}

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

ZM\Event::$viewable = array('5');
check('viewable event keeps the frames view', run($file, array('eid' => '5')), 'frames');
check('missing event gets the error view', run($file, array('eid' => '999')), 'error');
$warnings = 0;
check('missing eid gets the error view', run($file, array()), 'error');
check('missing eid raises no warning', $warnings, 0);

echo $failures ? "\n$failures check(s) FAILED\n" : "\nall checks passed\n";
exit($failures ? 1 : 0);
}
