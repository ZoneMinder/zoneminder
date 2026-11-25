<?php
// The event and video views used to check only the monitor, which passed for an event that
// does not exist (an empty ZM\Event has no Id, and the empty monitor passes canView()).
// They rendered a broken page instead of the error page. They now refuse the event with
// `if (!$Event->canView()) { $view = 'error'; return; }`, matching frames.php, frame.php
// and stats.php.
//
// The views are run with an eid that does not exist and an eid that does. The stub
// getNavBarHTML() throws 'STOP' so a view that does not refuse is caught as a behaviour
// failure instead of rendering the rest of the page.
//
// Run as: php tests/php/test_event_video_missing_event.php [views dir]

namespace ZM {
  function Warning($m) {}
  function Error($m) {}
  function Debug($m) {}
  function Info($m) {}

  class Event {
    public $id;
    function __construct($id = null) { $this->id = $id; }
    // Only the existing event under test (7) has an Id; any other eid is a failed lookup.
    function Id() { return $this->id == 7 ? 7 : 0; }
    function MonitorId() { return 1; }
    function Monitor() {
      static $m = null;
      if ($m === null) $m = new Monitor();
      return $m;
    }
    function Path() { return sys_get_temp_dir(); }
    function canView() { return $this->Id() > 0; }
    function __call($name, $args) { return null; }
  }

  class Monitor {
    function canView($u = null) { return true; }
    function __call($name, $args) { return null; }
  }

  class Filter {
    static function parse($q) { return new FilterStub(); }
  }

  class FilterStub {
    function has_term($a) { return false; }
    function terms($t = null) { return array(array('attr' => 'MonitorId')); }
    function addTerm($t) { return $this; }
    function querystring() { return ''; }
  }
}

namespace {
  $root = isset($argv[1]) ? $argv[1] : __DIR__.'/../../web/skins/classic/views';
  $failures = 0;
  function check($name, $got, $want) {
    global $failures;
    if ($got === $want) echo "ok - $name\n";
    else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
  }

  error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);

  // The views require these; give them empty files so the real includes are not loaded.
  $stub = sys_get_temp_dir().'/zm_event_video_missing_'.getmypid();
  mkdir($stub.'/includes', 0777, true);
  foreach (array('Event', 'Event_Data', 'Filter', 'Zone') as $f) file_put_contents($stub."/includes/$f.php", "<?php\n");
  set_include_path($stub.PATH_SEPARATOR.get_include_path());

  // Functions and constants the views use before they reach getNavBarHTML().
  function canView($area, $mid = false) { return true; }
  function canEdit($area) { return true; }
  function validInt($v) { return (int)$v; }
  function validCardinal($v) { return (int)$v; }
  function validHtmlStr($s) { return htmlspecialchars((string)$s); }
  function translate($s) { return $s; }
  function reScale($base, $scale, $def) { return 100; }
  function zm_session_start() {}
  function zm_setcookie($n, $v, $o = null) {}
  function parseSort() {}
  function generateConnKey() { return 'k'; }
  function xhtmlHeaders($f, $t) {}
  function getBodyTopHTML() {}
  function getNavBarHTML() { throw new \Exception('STOP'); }
  function xhtmlFooter() {}

  if (!defined('RATE_BASE')) define('RATE_BASE', 100);
  if (!defined('SCALE_BASE')) define('SCALE_BASE', 100);
  if (!defined('ZM_WEB_DEFAULT_RATE')) define('ZM_WEB_DEFAULT_RATE', 100);
  if (!defined('ZM_WEB_DEFAULT_SCALE')) define('ZM_WEB_DEFAULT_SCALE', 100);
  if (!defined('ZM_FFMPEG_FORMATS')) define('ZM_FFMPEG_FORMATS', 'mp4');

  class StubPreference {
    function Value() { return null; }
  }
  class StubUser {
    function Preference($name) { return new StubPreference(); }
    function Username() { return 'u'; }
  }
  $user = new StubUser();
  $rates = array();

  // Runs a view with the given eid. The include shares this file's global scope, so the
  // view sets $view here; a `return` in the view only ends the include.
  function run_view($path, $eid) {
    global $user, $rates;
    $_REQUEST = array('eid' => $eid);
    $view = 'view';
    $thrown = null;
    ob_start();
    try {
      include $path;
    } catch (\Throwable $ex) {
      $thrown = $ex->getMessage();
    }
    ob_end_clean();
    return array($view, $thrown);
  }

  // A missing event must be refused by each view.
  foreach (array('event', 'video') as $name) {
    list($view, $thrown) = run_view($root.'/'.$name.'.php', 999);
    if ($thrown !== null) {
      $failures++;
      echo "FAIL - $name.php did not refuse the missing event (threw $thrown)\n";
    } else {
      echo "ok - $name.php returned before getNavBarHTML for the missing event\n";
    }
    check("$name.php sets view to error for the missing event", $view, 'error');
  }

  // An existing event must still get past the guard. It may throw 'STOP' later.
  foreach (array('event', 'video') as $name) {
    list($view, $thrown) = run_view($root.'/'.$name.'.php', 7);
    check("$name.php keeps the view for an existing event", $view === 'error', false);
  }

  system('rm -rf '.escapeshellarg($stub));
  echo $failures ? "\n$failures failed\n" : "\nall passed\n";
  exit($failures ? 1 : 0);
}
