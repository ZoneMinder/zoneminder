<?php
// The Frames page links to its event. Opened in a new tab its Back button is
// disabled, and before this there was no way from the page to the event.
//
// Run as: php tests/php/test_frames_event_link.php [path/to/web]

namespace ZM {
  class Event {
    private $id;
    public function __construct($id) { $this->id = $id; }
    public function Id() { return $this->id; }
    public function Name() { return 'Event <42> & "front"'; }
    public function canView() { return true; }
  }
}

namespace {
$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../../web';
$file = $root.'/skins/classic/views/frames.php';

$stubdir = sys_get_temp_dir().'/zm_frames_link_stubs_'.getmypid();
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

$_REQUEST = array('eid' => '42');
$view = 'frames';
ob_start();
include $file;
$html = ob_get_clean();

$failures = 0;
function check($name, $ok) {
  global $failures;
  echo ($ok ? 'ok' : 'FAIL')." - $name\n";
  if (!$ok) $failures++;
}

check('the toolbar links to the event', (bool)preg_match('/<a id="eventBtn"[^>]*href="\?view=event&amp;eid=42"/', $html));
check('the link names the event, escaped',
  strpos($html, 'title="ViewEvent: Event &lt;42&gt; &amp; &quot;front&quot;"') !== false);

@unlink($stubdir.'/includes/Frame.php');
@unlink($stubdir.'/includes/Filter.php');
@rmdir($stubdir.'/includes');
@rmdir($stubdir);
echo $failures ? "\n$failures check(s) FAILED\n" : "\nall checks passed\n";
exit($failures ? 1 : 0);
}
