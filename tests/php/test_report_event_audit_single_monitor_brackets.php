<?php
// report_event_audit.php built the per-monitor filter for the events links with
// an opening bracket on the first term and a closing bracket only on the last
// one, so a single displayed monitor got "(" with no ")" and the Events link
// led to a filter with unbalanced brackets.
// Run: php tests/php/test_report_event_audit_single_monitor_brackets.php [root]

namespace ZM {
  function Debug($m) {}
  function Error($m) {}
  function Fatal($m) { throw new \Exception($m); }
  class Filter {
    public static $terms = array();
    public function addTerm($t) { self::$terms[] = $t; return $this; }
    public function querystring() { return ''; }
  }
  class Event { public function __construct($r = null) {} }
}

namespace {
  $root = isset($argv[1]) ? $argv[1] : dirname(__DIR__, 2);
  $stub = sys_get_temp_dir().'/zm_audit_br_'.getmypid();
  mkdir($stub, 0777, true);
  file_put_contents($stub.'/_monitor_filters.php', '<?php
function buildMonitorsFilters() { return array("filterBar"=>"", "displayMonitors"=>array(array("Id"=>5, "Name"=>"A")), "selected_monitor_ids"=>array()); }');
  set_include_path($stub.PATH_SEPARATOR.get_include_path());

  class StubUser {
    public function unviewableMonitorIds() { return array(); }
    public function viewableMonitorIds() { return array(5); }
  }
  $user = new StubUser();
  $_SESSION = array('GroupId'=>3);
  if (!function_exists('canView')) {
    function canView($a, $id = null) { return true; }
  }
  function getNavBarHTML() { return ''; }
  function check_datetime($s) { return true; }
  function validHtmlStr($s) { return $s; }
  class StubResult { public function fetch($m = null) { return false; } }
  function dbQuery($q, $v = null) { return new StubResult(); }
  function noCacheHeaders() {}
  function xhtmlHeaders($f, $t) {}
  function getBodyTopHTML() {}
  function xhtmlFooter() {}
  function translate($s) { return $s; }
  define('ZM_WEB_EVENTS_VIEW', 'events');
  $_REQUEST = array();
  $view = 'report_event_audit';

  ob_start();
  try {
    include $root.'/web/skins/classic/views/report_event_audit.php';
  } catch (\Throwable $e) {
    // the row rendering needs the real Monitor class; only the filter terms matter here
  }
  ob_end_clean();
  system('rm -rf '.escapeshellarg($stub));
  $open = 0; $close = 0;
  foreach (\ZM\Filter::$terms as $t) {
    if ($t['attr'] == 'MonitorId') {
      $open += (int)($t['obr'] ?? 0);
      $close += (int)($t['cbr'] ?? 0);
    }
  }
  $ok = ($open == $close && $open > 0);
  echo ($ok ? "ok - " : "NOT OK - ")."monitor filter brackets balanced for one monitor (open=$open close=$close)\n";
  exit($ok ? 0 : 1);
}
