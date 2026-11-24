<?php
// views/watch.php with a mid that is not a monitor (a bookmark to a monitor that
// was purged) went on to build the page around an empty monitor object: no stream,
// a blank name and a script that raises "No monitor found" on load. It shows the
// error view now, as the zone and event views do for a missing zone or event, and
// still does not take the error view when there is no mid and no monitor.
//
// Run: php tests/php/test_watch_view_missing_monitor.php   (from the repo root)

namespace ZM {
  function Error($m) {}
  function Debug($m) {}
  class Monitor {
    public static $known = array();
    public $row = array();
    public function __construct($idOrRow = 0) {
      if (is_array($idOrRow)) $this->row = $idOrRow;
      else if (isset(self::$known[$idOrRow])) $this->row = self::$known[$idOrRow];
    }
    public static function find_one($params = array()) {
      return isset(self::$known[$params['Id']]) ? new Monitor($params['Id']) : null;
    }
    public function Id() { return isset($this->row['Id']) ? $this->row['Id'] : null; }
  }
}

namespace {
  $passed = 0;
  $failed = 0;
  function check($label, $got, $want) {
    global $passed, $failed;
    if ($got === $want) {
      $passed++;
      echo "  ok $label\n";
    } else {
      $failed++;
      echo "  FAIL $label\n";
      echo "       got:  ".var_export($got, true)."\n";
      echo "       want: ".var_export($want, true)."\n";
    }
  }

  function canView($a, $id = null) { return true; }
  function visibleMonitor($mid) { return true; }
  function translate($s) { return $s; }
  function buildMonitorsFilters() {
    return array('filterBar' => '', 'displayMonitors' => $GLOBALS['displayMonitors']);
  }

  // The view includes the monitor filter bar and requires includes/Monitor.php and
  // includes/MonitorAction.php, which
  // pull in the real database layer; give it stubs from a temporary include path.
  $stubDir = sys_get_temp_dir().'/zm-watch-view-'.getmypid();
  @mkdir($stubDir.'/includes', 0700, true);
  file_put_contents($stubDir.'/includes/Monitor.php', "<?php\n");
  file_put_contents($stubDir.'/includes/MonitorAction.php', "<?php\n");
  file_put_contents($stubDir.'/_monitor_filters.php', "<?php\n");
  set_include_path($stubDir.PATH_SEPARATOR.get_include_path());
  register_shutdown_function(function() use ($stubDir) {
    @unlink($stubDir.'/includes/Monitor.php');
    @unlink($stubDir.'/includes/MonitorAction.php');
    @unlink($stubDir.'/_monitor_filters.php');
    @rmdir($stubDir.'/includes');
    @rmdir($stubDir);
  });

  function run_view($mid) {
    $_REQUEST = $mid === null ? array() : array('mid' => $mid);
    $view = 'watch';
    try {
      require __DIR__.'/../../web/skins/classic/views/watch.php';
    } catch (Throwable $e) {
      // Past the guard the view needs the whole web layer; reaching it is the failure.
    }
    return $view;
  }

  $displayMonitors = array(array('Id' => 3, 'Capturing' => 'Always', 'Width' => 640, 'Height' => 480));
  ZM\Monitor::$known = array(3 => array('Id' => 3));

  check('a mid that is not a monitor shows the error view', run_view('99'), 'error');

  $displayMonitors = array();
  ZM\Monitor::$known = array();
  check('a mid that is not a monitor, with no monitors, shows the error view', run_view('99'), 'error');

  // The empty install keeps its own page: the guard only applies when a mid was asked for.
  check('no mid and no monitor does not show the error view', run_view(null), 'watch');

  echo "\n$passed passed, $failed failed\n";
  exit($failed ? 1 : 0);
}
