<?php
// report_event_audit.php counts events overlapping the audit window (still
// recording or ending after minTime, and starting before maxTime); the events
// link filter must select the same events.
// Run: php tests/php/test_report_event_audit_link_window.php [root]

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
  $stub = sys_get_temp_dir().'/zm_audit_link_'.getmypid();
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
  $_REQUEST = array('minTime'=>'2026-01-01 10:00:00', 'maxTime'=>'2026-01-01 12:00:00');
  $view = 'report_event_audit';

  ob_start();
  try {
    include $root.'/web/skins/classic/views/report_event_audit.php';
  } catch (\Throwable $e) {
    // the row rendering needs the real Monitor class; only the filter terms matter here
  }
  ob_end_clean();
  system('rm -rf '.escapeshellarg($stub));

  function condMatches($t, $ev) {
    if ($t['attr'] == 'StartDateTime') $x = $ev['start'];
    else if ($t['attr'] == 'EndDateTime') $x = $ev['end'];
    else return true;
    $val = $t['val'];
    switch ($t['op']) {
    case 'IS':
      return $val === 'NULL' ? $x === null : $x === $val;
    case '>=':
      return $x !== null && strcmp($x, $val) >= 0;
    case '<=':
      return $x !== null && strcmp($x, $val) <= 0;
    case '>':
      return $x !== null && strcmp($x, $val) > 0;
    case '<':
      return $x !== null && strcmp($x, $val) < 0;
    }
    return false;
  }

  function parseFactor($tokens, &$i, $ev) {
    $t = $tokens[$i];
    if ($t === '(') {
      $i++;
      $v = parseOr($tokens, $i, $ev);
      $i++;
      return $v;
    }
    $i++;
    return condMatches($t, $ev);
  }

  function parseAnd($tokens, &$i, $ev) {
    $v = parseFactor($tokens, $i, $ev);
    while ($i < count($tokens) && $tokens[$i] === 'and') {
      $i++;
      $v = parseFactor($tokens, $i, $ev) && $v;
    }
    return $v;
  }

  function parseOr($tokens, &$i, $ev) {
    $v = parseAnd($tokens, $i, $ev);
    while ($i < count($tokens) && $tokens[$i] === 'or') {
      $i++;
      $v = parseAnd($tokens, $i, $ev) || $v;
    }
    return $v;
  }

  function matchesTerms($terms, $ev) {
    $tokens = array();
    $first = true;
    foreach ($terms as $t) {
      if ($t['attr'] != 'StartDateTime' && $t['attr'] != 'EndDateTime') continue;
      for ($k = 0; $k < (int)($t['obr'] ?? 0); $k++) $tokens[] = '(';
      if (!$first) $tokens[] = (isset($t['cnj']) && $t['cnj'] === 'or') ? 'or' : 'and';
      $tokens[] = $t;
      for ($k = 0; $k < (int)($t['cbr'] ?? 0); $k++) $tokens[] = ')';
      $first = false;
    }
    $i = 0;
    return parseOr($tokens, $i, $ev);
  }

  $inside = array('start'=>'2026-01-01 09:30:00', 'end'=>'2026-01-01 10:30:00');
  $before = array('start'=>'2026-01-01 08:30:00', 'end'=>'2026-01-01 09:45:00');
  $matches_inside = matchesTerms(\ZM\Filter::$terms, $inside);
  $matches_before = matchesTerms(\ZM\Filter::$terms, $before);
  $ok = ($matches_inside && !$matches_before);
  echo ($ok ? "ok - " : "NOT OK - ")."events link filter matches an event that started before minTime and ended inside the window\n";
  exit($ok ? 0 : 1);
}
