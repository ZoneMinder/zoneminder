<?php
// The events list narrows the events with id lists: the monitors left by the
// monitor attribute filters (Status, Capturing, Name...), and for a search the
// events the filter matched. FilterTerm drops a term whose list is empty, so when
// no monitor or no event matched, the list showed every event instead of none.
//
// Runs the real Filter and web/ajax/events.php against a stubbed database that
// returns the events the SQL's E.Id / E.MonitorId IN lists allow, or all of them
// when it has neither. Each scenario runs in a child php process.
//
// Run as: php tests/php/test_events_empty_match.php

namespace ZM {
  function Debug($m) {}
  function Warning($m) {}
  function Error($m) {}
  function Info($m) {}
  class Event {
    private $row;
    public function __construct($row) { $this->row = $row; }
    public function remove_from_cache() {}
    public function Id() { return $this->row['Id']; }
    public function canView() { return true; }
    public function Width() { return 0; }
    public function Height() { return 0; }
    public function ThumbnailWidth() { return 0; }
    public function ThumbnailHeight() { return 0; }
    public function DefaultVideo() { return ''; }
    public function DiskSpace() { return 0; }
    public function getThumbnailSrc($a, $s) { return ''; }
    public function getStreamSrc($a, $s) { return ''; }
  }
  class Storage {
    public static function find() { return array(); }
  }
}

namespace {
  class Done extends \Exception {}

  $scenarios = array('no_monitor_matches', 'search_with_no_events', 'search_with_events');
  if (!isset($argv[1])) {
    $failures = 0;
    foreach ($scenarios as $scenario) {
      passthru(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' '.$scenario, $rc);
      if ($rc) $failures++;
    }
    exit($failures ? 1 : 0);
  }
  $scenario = $argv[1];

  $web = __DIR__.'/../../web';
  $stubdir = sys_get_temp_dir().'/zm_events_empty_match_'.getmypid();
  @mkdir($stubdir, 0700, true);
  foreach (array('database.php', 'Monitor.php', 'mf.php') as $stub) file_put_contents($stubdir.'/'.$stub, "<?php\n");
  register_shutdown_function(function() use ($stubdir) {
    foreach (array('database.php', 'Monitor.php', 'mf.php') as $stub) @unlink($stubdir.'/'.$stub);
    @rmdir($stubdir);
  });
  set_include_path($stubdir.PATH_SEPARATOR.$web.PATH_SEPARATOR.$web.'/includes'.PATH_SEPARATOR.get_include_path());

  define('STRF_FMT_DATETIME_DB', 'Y-m-d H:i:s');
  define('ZM_WEB_EVENT_SORT_FIELD', 'StartDateTime');
  define('ZM_WEB_EVENT_SORT_ORDER', 'asc');
  define('ZM_WEB_LIST_THUMB_WIDTH', 100);
  define('ZM_WEB_VIDEO_MAXFPS', 30);
  function dbEscape($s) { return "'".addslashes($s)."'"; }
  function translate($s) { return $s; }
  function jsonEncode($v) { return json_encode($v); }
  function jsonDecode($v) { return json_decode($v, true); }
  require_once $web.'/includes/Filter.php';

  class TestUser {
    public function Username() { return 'tester'; }
    public function unviewableMonitorIds() { return array(); }
    public function viewableMonitorIds() { return array(1, 2); }
  }
  class Fmt { public function format($t) { return ''; } }
  $user = new TestUser();
  $dateTimeFormatter = new Fmt();
  function canView($a, $m = false) { return true; }
  function canEdit($a, $m = false) { return true; }
  function getSkinFile($f) { return $GLOBALS['stubdir'].'/mf.php'; }
  function getFilteredMonitorIds() { return $GLOBALS['attr_monitor_ids']; }
  function validHtmlStr($s) { return htmlspecialchars((string)$s); }
  function validInt($v) { return preg_replace('/[^\-\d]/', '', $v); }
  function validCardinal($v) { return is_scalar($v) ? preg_replace('/\D/', '', $v) : ''; }
  function human_filesize($n) { return $n.'B'; }
  function ajaxError($m) { $GLOBALS['out'] = 'error: '.$m; throw new Done(); }
  function ajaxResponse($d) { $GLOBALS['out'] = $d; throw new Done(); }

  // Events 1 and 2 are on monitor 1, event 3 on monitor 2. The base filter of the
  // search scenarios matches none, or only event 1.
  $events = array();
  foreach (array(1 => 1, 2 => 1, 3 => 2) as $id => $mid) {
    $events[] = array('Id' => $id, 'MonitorId' => $mid, 'StorageId' => 0, 'Name' => 'e'.$id, 'Cause' => '',
      'Tags' => '', 'Archived' => 0, 'Emailed' => 0, 'Notes' => '', 'Length' => 1, 'DiskSpace' => 0);
  }
  function matching($sql, $params = array()) {
    $rows = $GLOBALS['events'];
    if (isset($GLOBALS['base_ids']) and strpos($sql, 'LIKE') === false) {
      $base = $GLOBALS['base_ids'];
      $rows = array_filter($rows, function($r) use ($base) { return in_array($r['Id'], $base); });
    }
    foreach (array('Id', 'MonitorId') as $col) {
      if (preg_match_all('/E\.`?'.$col.'`?\s+IN\s*\(([^)]*)\)/i', $sql, $m)) {
        foreach ($m[1] as $list) {
          $ids = (trim($list) !== '' and trim(str_replace(array('?', ',', ' '), '', $list)) === '')
            ? array_map('intval', (array)$params)
            : array_map('intval', array_map(function($v) { return trim($v, " '\""); }, explode(',', $list)));
          $rows = array_filter($rows, function($r) use ($col, $ids) { return in_array($r[$col], $ids); });
        }
      }
    }
    return array_values($rows);
  }
  function dbQuery($sql, $params = array()) { $GLOBALS['pending'] = matching($sql, $params); return true; }
  function dbFetchNext($q) { return array_shift($GLOBALS['pending']); }
  function dbFetchAll($sql, $col = false, $params = null) {
    $rows = matching($sql, $params);
    return $col ? array_column($rows, $col) : $rows;
  }
  function dbFetchOne($sql, $col = false, $params = null) { return count(matching($sql, $params)); }
  function dbError($sql) { return ''; }

  $attr_monitor_ids = null;
  $_REQUEST = array('task' => 'query', 'sort' => 'Id', 'order' => 'asc');
  if ($scenario == 'no_monitor_matches') {
    // The Status filter matched no monitor
    $attr_monitor_ids = array();
    $want = array();
  } else if ($scenario == 'search_with_no_events') {
    $base_ids = array();
    $_REQUEST['search'] = 'e';
    $want = array();
  } else {
    $base_ids = array(1);
    $_REQUEST['search'] = 'e';
    $want = array(1);
  }

  $out = null;
  try {
    include $web.'/ajax/events.php';
  } catch (Done $e) {
  }
  $got = is_array($out) ? array_map(function($r) { return (int)$r['Id']; }, $out['rows']) : $out;
  if ($got === $want) {
    echo "ok - $scenario lists ".json_encode($want)."\n";
    exit(0);
  }
  echo "FAIL - $scenario listed ".json_encode($got).", want ".json_encode($want)."\n";
  exit(1);
}
