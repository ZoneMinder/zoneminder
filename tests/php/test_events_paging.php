<?php
// The Events list is paged by the database. Events that sort the same must not show
// up on two pages or on none, and tag filters and sorts must give what they did when
// every event was fetched and the page was cut out in PHP.
//
// Drives queryRequest() from web/ajax/events.php against the real database, inside a
// transaction that is rolled back, with the models it builds rows from stubbed.
// The connection and config come from an installed config.php (ZM_WEB_INCLUDES,
// default /usr/share/zoneminder/www/includes), so run it as a user that can read
// /etc/zm/zm.conf:
//
//   sudo -u www-data php tests/php/test_events_paging.php

namespace ZM {
  class Filter {
    public $limit = 0;
    public $monitor_ids = array();
    public $extra_sql = '';
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
      return 'E.MonitorId IN ('.implode(',', $this->monitor_ids).')'.$this->extra_sql;
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
  $stubdir = sys_get_temp_dir().'/zm_events_paging_stubs_'.getmypid();
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
    $r = array_merge(array('search' => '', 'offset' => 0, 'limit' => 0, 'sort' => 'StartDateTime', 'order' => 'ASC'), $request);
    return queryRequest($GLOBALS['test_filter'], $r['search'], array(), $r['sort'], $r['offset'], $r['order'], $r['limit']);
  }

  function ids($data) {
    return array_map(function($row) { return (int)$row['Id']; }, $data['rows']);
  }

  $db = zmDbConn();
  $db->beginTransaction();
  try {
    // 7 events that all start at the same time and are 10 or 20 seconds long
    dbQuery("INSERT INTO Monitors (Name, Deleted) VALUES ('testpaging', false)");
    $mid = (int)dbInsertId();
    $ids = array();
    for ($i = 0; $i < 7; $i++) {
      dbQuery('INSERT INTO Events (MonitorId, StateId, Name, StartDateTime, EndDateTime, Length)
        VALUES (?, 1, ?, ?, ?, ?)', array($mid, 'testpaging '.$i, '2001-01-01 00:00:00', '2001-01-01 00:00:10', $i % 2 ? 20 : 10));
      $ids[] = (int)dbInsertId();
    }
    dbQuery("INSERT INTO Tags (Name) VALUES ('testpaging b')");
    $tag_b = (int)dbInsertId();
    dbQuery("INSERT INTO Tags (Name) VALUES ('testpaging a')");
    $tag_a = (int)dbInsertId();
    foreach (array(array($tag_b, $ids[1]), array($tag_a, $ids[1]), array($tag_a, $ids[4])) as $et)
      dbQuery('INSERT INTO Events_Tags (TagId, EventId) VALUES (?, ?)', $et);

    $GLOBALS['test_filter'] = new ZM\Filter();
    $GLOBALS['test_filter']->monitor_ids = array($mid);

    foreach (array('StartDateTime' => $ids, 'Length' => array($ids[1], $ids[3], $ids[5], $ids[0], $ids[2], $ids[4], $ids[6])) as $sort => $want) {
      $pages = array();
      for ($offset = 0; $offset < 7; $offset += 2) {
        $page = query(array('sort' => $sort, 'order' => 'DESC', 'limit' => '2', 'offset' => (string)$offset));
        $pages = array_merge($pages, ids($page));
        check("$sort page at offset $offset reports the full total", $page['total'], 7);
      }
      check("$sort pages list every event once, ties by Id", $pages, $want);
    }

    $tags = query(array('sort' => 'Tags', 'order' => 'DESC', 'limit' => '2', 'offset' => '0'));
    check('tag sort puts the tagged events first', ids($tags), array($ids[1], $ids[4]));
    check('tags are listed by name', $tags['rows'][0]['Tags'], 'testpaging a, testpaging b');

    $GLOBALS['test_filter']->extra_sql = ' AND T.Id = '.$tag_a;
    $tagged = query(array('limit' => '1', 'offset' => '1'));
    check('tag filter counts the tagged events', $tagged['total'], 2);
    check('tag filter pages the tagged events', ids($tagged), array($ids[4]));
    check('tag filter shows the matching tag', $tagged['rows'][0]['Tags'], 'testpaging a');

    $GLOBALS['test_filter']->extra_sql = ' AND E.NoSuchColumn = 1';
    $error = '';
    try {
      query(array('limit' => '2'));
    } catch (Exception $e) {
      $error = $e->getMessage();
    }
    check('a failing query is reported', $error, 'ajaxError: Database query failed');
  } finally {
    $db->rollBack();
  }

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
