<?php
// ajax/events.php request handling:
//  - eids=5 (a string, not eids[]=5) was passed to foreach, which only warns, so
//    archive/unarchive/delete answered success and did nothing.
//  - advsearch that is not a JSON object (garbage, empty, or advsearch[]=x) reached
//    count() as null and was a TypeError on PHP 8; sort, order and search given as
//    arrays were TypeErrors or "Array to string" notices too.
//  - a page whose limit was not smaller than the number of matching rows was not cut
//    at the offset, so the last page repeated rows from earlier pages.
//
// events.php declares functions, so each scenario runs in a child php process.
//
// Run as: php tests/php/test_events_ajax_request.php

namespace ZM {
  function Debug($m) {}
  function Warning($m) {}
  function Error($m) {}
  class Filter {
    public $terms = array();
    public static function parse($f) { return new Filter(); }
    public static function buildSortSql($sort, $order, $resolve) { return $resolve($sort).' '.$order; }
    public function addTerm($t) { return $this; }
    public function addTerms($t, $o = null) { return $this; }
    public function sql() { return 'E.Id > 0'; }
    public function post_sql_conditions() { return array('x'); }
    public function test_pre_sql_conditions() { return true; }
    public function test_post_sql_conditions($e) { return true; }
    public function limit() { return 0; }
    public function sort_asc() { return true; }
    public function sort_field() { return 'StartDateTime'; }
  }
  class Event {
    private $row;
    public function __construct($row) { $this->row = is_array($row) ? $row : array('Id' => $row); }
    public function remove_from_cache() {}
    public function Id() { return $this->row['Id']; }
    public function canView() { return true; }
    public function canEdit() { return true; }
    public function Archived() { return false; }
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

  if (!isset($argv[1])) {
    $failures = 0;
    foreach (array('eids_scalar', 'advsearch_garbage', 'advsearch_array', 'sort_array', 'search_array', 'last_page') as $scenario) {
      passthru(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' '.$scenario, $rc);
      if ($rc) $failures++;
    }
    exit($failures ? 1 : 0);
  }

  $stubdir = sys_get_temp_dir().'/zm_events_req_stubs_'.getmypid();
  @mkdir($stubdir.'/includes', 0700, true);
  file_put_contents($stubdir.'/includes/Filter.php', "<?php\n");
  file_put_contents($stubdir.'/mf.php', "<?php\n");
  set_include_path($stubdir.PATH_SEPARATOR.get_include_path());
  register_shutdown_function(function() use ($stubdir) {
    @unlink($stubdir.'/includes/Filter.php'); @unlink($stubdir.'/mf.php');
    @rmdir($stubdir.'/includes'); @rmdir($stubdir);
  });

  define('ZM_WEB_LIST_THUMB_WIDTH', 100);
  define('ZM_WEB_VIDEO_MAXFPS', 30);
  class TestUser {
    public function Username() { return 'tester'; }
    public function unviewableMonitorIds() { return array(); }
    public function viewableMonitorIds() { return array(); }
  }
  class Fmt { public function format($t) { return ''; } }
  $user = new TestUser();
  $dateTimeFormatter = new Fmt();

  function canView($a, $m = false) { return true; }
  function canEdit($a, $m = false) { return true; }
  function getSkinFile($f) { return $GLOBALS['stubdir'].'/mf.php'; }
  function getFilteredMonitorIds() { return null; }
  function validHtmlStr($s) { return htmlspecialchars((string)$s); }
  function validInt($v) { return (int)$v; }
  function validCardinal($v) { return is_scalar($v) ? preg_replace('/\D/', '', $v) : ''; }
  function translate($s) { return $s; }
  function human_filesize($n) { return $n.'B'; }
  function ajaxError($m) { $GLOBALS['out'] = 'error: '.$m; throw new Done(); }
  function ajaxResponse($d) { $GLOBALS['out'] = $d; throw new Done(); }
  $updates = array();
  function dbQuery($sql, $params = array()) {
    if (stripos($sql, 'UPDATE') === 0) { $GLOBALS['updates'][] = $params; return true; }
    $GLOBALS['pending'] = $GLOBALS['test_rows'];
    return true;
  }
  function dbFetchNext($q) { return array_shift($GLOBALS['pending']); }
  function dbFetchAll($sql, $col = false, $params = null) { return array(); }
  function dbFetchOne($sql, $col = false, $params = null) { return 0; }
  function dbError($sql) { return ''; }

  $notices = array();
  set_error_handler(function($no, $str) use (&$notices) { $notices[] = $str; return true; });

  $test_rows = array();
  for ($i = 1; $i <= 25; $i++) {
    $test_rows[] = array('Id' => $i, 'MonitorId' => 1, 'StorageId' => 0, 'Name' => 'e'.$i, 'Monitor' => 'Front', 'Cause' => '', 'Tags' => '',
      'Archived' => 0, 'Emailed' => 0, 'Notes' => '', 'Length' => 1, 'DiskSpace' => 0);
  }

  $scenario = $argv[1];
  $_REQUEST = array('task' => 'query', 'sort' => 'StartDateTime', 'order' => 'asc');
  switch ($scenario) {
  case 'eids_scalar':
    $_REQUEST = array('task' => 'archive', 'eids' => '5');
    break;
  case 'advsearch_garbage':
    $_REQUEST['advsearch'] = 'not json';
    break;
  case 'advsearch_array':
    $_REQUEST['advsearch'] = array('x');
    break;
  case 'sort_array':
    $_REQUEST['sort'] = array('Id');
    $_REQUEST['order'] = array('asc');
    break;
  case 'search_array':
    $_REQUEST['search'] = array('a');
    break;
  case 'last_page':
    $_REQUEST['offset'] = '20';
    $_REQUEST['limit'] = '30';
    break;
  }

  $out = null;
  try {
    include __DIR__.'/../../web/ajax/events.php';
  } catch (Done $e) {
  } catch (\Throwable $e) {
    $out = 'threw: '.$e->getMessage();
  }

  $ok = true;
  switch ($scenario) {
  case 'eids_scalar':
    $ok = (count($updates) == 1 && $updates[0][1] == 5);
    break;
  case 'last_page':
    $ok = is_array($out) && count($out['rows']) == 5 && $out['rows'][0]['Id'] == 21;
    break;
  default:
    $ok = is_array($out) && isset($out['rows']);
  }
  if ($ok and $scenario != 'eids_scalar' and $notices) $ok = false;
  echo ($ok ? 'ok - ' : 'FAIL - ').$scenario.($ok ? '' : ' ('.(is_array($out) ? (isset($out['rows']) ? count($out['rows']).' rows' : 'no rows') : var_export($out, true)).' '.implode('; ', $notices).')')."\n";
  exit($ok ? 0 : 1);
}
