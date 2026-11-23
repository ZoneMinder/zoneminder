<?php
// report_event_audit.php built "MonitorId IN ()" for a user who can view no
// monitor at all, which is a SQL syntax error, so the page died with a fatal
// instead of showing an empty table.
// Run: php tests/php/test_report_event_audit_no_viewable_monitors.php   (from the repo root)

namespace ZM {
  class Stop extends \Exception {}
  function Debug($m) {}
  function Error($m) {}
  function Fatal($m) { throw new Stop($m); }
  class Filter {
    public function addTerm($t) { return $this; }
    public function querystring() { return ''; }
  }
  class Event { public function __construct($r = null) {} }
}

namespace {
  $stub = sys_get_temp_dir().'/zm_audit_'.getmypid();
  mkdir($stub, 0777, true);
  file_put_contents($stub.'/_monitor_filters.php', '<?php
function buildMonitorsFilters() { return array("filterBar"=>"", "displayMonitors"=>array(), "selected_monitor_ids"=>array()); }');
  set_include_path($stub.PATH_SEPARATOR.get_include_path());

  class StubUser {
    public function unviewableMonitorIds() { return array(3); }
    public function viewableMonitorIds() { return array(); }
  }
  $user = new StubUser();
  $sql = null;
  if (!function_exists('canView')) {
    function canView($a, $id = null) { return true; }
  }
  function getNavBarHTML() { return ''; }
  function check_datetime($s) { return true; }
  function validHtmlStr($s) { return $s; }
  function dbQuery($q, $v = null) { $GLOBALS['sql'] = $q; return preg_match('/IN \(\s*\)/', $q) ? false : new StubResult(); }
  class StubResult { public function fetch($m = null) { return false; } }
  function noCacheHeaders() {}
  function xhtmlHeaders($f, $t) {}
  function getBodyTopHTML() {}
  function xhtmlFooter() {}
  function translate($s) { return $s; }
  define('ZM_WEB_EVENTS_VIEW', 'events');
  $_REQUEST = array();
  $view = 'report_event_audit';

  $msg = null;
  ob_start();
  try {
    include __DIR__.'/../../web/skins/classic/views/report_event_audit.php';
  } catch (\Throwable $e) {
    $msg = $e->getMessage();
  }
  ob_end_clean();
  system('rm -rf '.escapeshellarg($stub));
  $ok = ($msg === null);
  echo ($ok ? "ok - " : "NOT OK - ")."page renders with no viewable monitors".($msg ? " ($msg; sql: ".trim(preg_replace('/\s+/', ' ', $sql)).")" : '')."\n";
  exit($ok ? 0 : 1);
}
