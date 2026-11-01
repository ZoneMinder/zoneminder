<?php
// Regression test for #5417: the Events list leaves out events whose monitor is
// soft-deleted (Monitors.Deleted). That has to happen in the SQL, before the filter
// limit, the paging and the totals; when it happened while building the rows, pages
// came up short and the footer counted events that were never listed.
//
// Drives queryRequest() from web/ajax/events.php against the real database, inside a
// transaction that is rolled back, with the models it builds rows from stubbed.
// The connection and config come from an installed config.php (ZM_WEB_INCLUDES,
// default /usr/share/zoneminder/www/includes), so run it as a user that can read
// /etc/zm/zm.conf:
//
//   sudo -u www-data php tests/php/test_events_deleted_monitor.php

namespace ZM {
  class Filter {
    public $limit = 0;
    public $monitor_ids = array();
    // The search filter is built in queryRequest() from the base query's event ids;
    // its LIKE terms are left out, every fixture event matches the search.
    public $event_ids = null;
    public static function parse($f) { return $GLOBALS['test_filter']; }
    public static function buildSortSql($sort, $order, $resolve) { return $resolve($sort).' '.$order; }
    public function addTerm($term) {
      if ($term['attr'] == 'Id') $this->event_ids = $term['val'];
      return $this;
    }
    public function addTerms($terms, $options = null) { return $this; }
    public function sql() {
      if ($this->event_ids !== null) return 'E.Id IN ('.implode(',', $this->event_ids ?: array(0)).')';
      return 'E.MonitorId IN ('.implode(',', $this->monitor_ids).')';
    }
    public function post_sql_conditions() { return array(); }
    public function test_pre_sql_conditions() { return true; }
    public function limit() { return $this->limit; }
    public function sort_asc() { return true; }
    public function sort_field() { return 'StartDateTime'; }
  }
  class Event {
    private $row;
    public function __construct($row) { $this->row = $row; }
    public function remove_from_cache() {}
    public function canView() { return true; }
    public function Id() { return $this->row['Id']; }
    public function Monitor() { return new Monitor($this->row['MonitorId']); }
    public function Width() { return 0; }
    public function Height() { return 0; }
    public function ThumbnailWidth() { return 0; }
    public function ThumbnailHeight() { return 0; }
    public function DefaultVideo() { return ''; }
    public function DiskSpace() { return 0; }
    public function getThumbnailSrc($args, $sep) { return ''; }
    public function getStreamSrc($args, $sep) { return ''; }
  }
  class Monitor {
    private $id;
    public function __construct($id) { $this->id = $id; }
    public function Deleted() { return (bool)dbFetchOne('SELECT Deleted FROM Monitors WHERE Id=?', 'Deleted', array($this->id)); }
  }
  class Storage {
    public static function find() { return array(); }
  }
}

namespace {
  class TestUser {
    public function Username() { return 'tester'; }
    public function unviewableMonitorIds() { return array(); }
  }

  $includes = getenv('ZM_WEB_INCLUDES') ?: '/usr/share/zoneminder/www/includes';
  if (!is_readable($includes.'/config.php')) {
    echo "skip - no readable config.php in $includes\n";
    exit(0);
  }

  // events.php requires includes/Filter.php and the skin's _monitor_filters.php;
  // the classes and getFilteredMonitorIds() they provide are defined here.
  $stubdir = sys_get_temp_dir().'/zm_events_deleted_monitor_stubs_'.getmypid();
  @mkdir($stubdir.'/includes', 0700, true);
  file_put_contents($stubdir.'/includes/Filter.php', "<?php\n");
  file_put_contents($stubdir.'/_monitor_filters.php', "<?php\n");
  set_include_path($stubdir.PATH_SEPARATOR.$includes.PATH_SEPARATOR.get_include_path());
  require_once($includes.'/config.php');
  // web/index.php sets this from the bandwidth in use
  if (!defined('ZM_WEB_VIDEO_MAXFPS')) define('ZM_WEB_VIDEO_MAXFPS', 30);

  function canView($area, $mid = false) { return true; }
  function canEdit($area, $mid = false) { return true; }
  function getSkinFile($file) { return $GLOBALS['stubdir'].'/_monitor_filters.php'; }
  function getFilteredMonitorIds() { return null; }
  function validHtmlStr($s) { return htmlspecialchars((string)$s); }
  function validInt($v) { return preg_replace('/[^\-\d]/', '', $v); }
  function translate($s) { return $s; }
  function human_filesize($n) { return $n.'B'; }
  function ajaxResponse($data = array()) { throw new Exception('ajaxResponse'); }
  function ajaxError($message, $code = 200) { throw new Exception('ajaxError: '.$message); }

  class TestFormatter { public function format($t) { return ''; } }
  $dateTimeFormatter = new TestFormatter();
  $user = new TestUser();

  $failures = 0;
  $passes = 0;
  function check($name, $got, $want) {
    global $failures, $passes;
    if ($got === $want) {
      $passes++;
      echo "ok - $name\n";
    } else {
      $failures++;
      echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n";
    }
  }

  // events.php ends in ajaxResponse(), and its functions can only be declared once, so
  // include it with a request it turns away (that still declares queryRequest()) and
  // call queryRequest() as its task=query does.
  $_REQUEST = array();
  try {
    include __DIR__.'/../../web/ajax/events.php';
  } catch (Exception $e) {
  }
  function query(array $request) {
    $r = array_merge(array('search' => '', 'offset' => 0, 'limit' => 0), $request);
    return queryRequest($GLOBALS['test_filter'], $r['search'], array(), 'StartDateTime', $r['offset'], 'ASC', $r['limit']);
  }

  function ids($data) {
    return array_map(function($row) { return (int)$row['Id']; }, $data['rows']);
  }

  $db = zmDbConn();
  $db->beginTransaction();
  try {
    // A live monitor with 5 events and a deleted one with 3, alternating in time
    // so that the deleted monitor's events fall inside every page.
    dbQuery("INSERT INTO Monitors (Name, Deleted) VALUES ('test5417 live', false)");
    $live = (int)dbInsertId();
    dbQuery("INSERT INTO Monitors (Name, Deleted) VALUES ('test5417 deleted', true)");
    $deleted = (int)dbInsertId();
    $live_ids = array();
    $order = array($live, $deleted, $live, $deleted, $live, $deleted, $live, $live);
    foreach ($order as $i => $mid) {
      dbQuery('INSERT INTO Events (MonitorId, StateId, Name, StartDateTime, EndDateTime, Length)
        VALUES (?, 1, ?, ?, ?, ?)', array($mid, 'test5417 '.$i,
          sprintf('2001-01-01 00:%02d:00', $i), sprintf('2001-01-01 00:%02d:10', $i), 10 + $i));
      if ($mid == $live) $live_ids[] = (int)dbInsertId();
    }
    $GLOBALS['test_filter'] = new ZM\Filter();
    $GLOBALS['test_filter']->monitor_ids = array($live, $deleted);

    $all = query(array());
    check('all rows are the live monitor\'s events', ids($all), $live_ids);
    check('total counts only the listed events', $all['total'], 5);
    check('totalNotFiltered counts only the listed events', $all['totalNotFiltered'], 5);
    check('footer length sums the listed events', (float)$all['footerData']['Length'], (float)(10 + 12 + 14 + 16 + 17));

    $pages = array();
    foreach (array(0, 2, 4) as $offset) {
      $page = query(array('limit' => '2', 'offset' => (string)$offset));
      $pages = array_merge($pages, ids($page));
      check("page at offset $offset is full", count($page['rows']), $offset < 4 ? 2 : 1);
      check("page at offset $offset reports the full total", $page['total'], 5);
    }
    check('the pages together list every event once', $pages, $live_ids);

    $first = query(array('limit' => '2', 'offset' => '0'));
    check('footer length sums the page\'s rows', (float)$first['footerData']['Length'], (float)(10 + 12));

    // The filter's own limit counts listed events only.
    $GLOBALS['test_filter']->limit = 3;
    $limited = query(array());
    check('filter limit keeps its number of listed events', ids($limited), array_slice($live_ids, 0, 3));
    $GLOBALS['test_filter']->limit = 0;

    // The search box goes through the second SELECT.
    $search = query(array('search' => 'test5417'));
    check('search lists only the live monitor\'s events', ids($search), $live_ids);
    check('search total counts only the listed events', $search['total'], 5);
    $search = query(array('search' => 'test5417', 'limit' => '2', 'offset' => '0'));
    check('search page is full', ids($search), array_slice($live_ids, 0, 2));
  } finally {
    $db->rollBack();
  }

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
