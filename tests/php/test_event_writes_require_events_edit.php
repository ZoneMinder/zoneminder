<?php
// Security regression test: an Events=View user may watch events but must not
// change them. Covers the write paths that used to sit behind Events view only:
//   * ajax/event.php addtag / removetag (Events_Tags rows, and removetag deletes
//     the Tag itself once it is unused)
//   * ajax/tags.php createtag (had no permission check at all)
//   * the Edit-level tag actions only touch events on monitors the user can
//     view, and answer a missing and a hidden event alike
//   * ajax/tags.php lists tags for the users the event view serves (Events or
//     Snapshots view)
//   * views/video.php lists its files with links whose index is the file's key
//     (empty files used to shift every later link onto the next file)
//   * views/video.php deleteIndex (unlink() of a file in the event folder; the
//     list includes the event's own recording since ZM_FFMPEG_FORMATS has mp4):
//     the recording needs Events edit, a generated video Events view
//   * the other Edit-level ajax/event.php actions (rename, eventdetail, archive,
//     unarchive, delete) and includes/actions/eventdetail.php take only events on
//     monitors the user can view, like the tag actions; getselectedtags lists no
//     tags of a hidden event
//
// Includes the real files with the web helpers stubbed, records the SQL they
// send, and checks whether the event's video file survives.
//
// Run as: php tests/php/test_event_writes_require_events_edit.php

namespace ZM {
  function Warning($s) { $GLOBALS['warnings'][] = $s; }
  function Debug($s) {}
  class TagOrder {
    public static function recordUsage($uid, $tid) { $GLOBALS['queries'][] = 'TagOrder::recordUsage'; }
    public static function load($uid) { return array(); }
  }
  class Monitor {
    public function DefaultRate() { return 100; }
    public function DefaultScale() { return 100; }
  }
  class Event {
    // Object::__construct() takes an array as the row itself, without loading it.
    private $row = null;
    public function __construct($id) { if (is_array($id)) $this->row = $id; }
    public function Id() {
      if ($this->row !== null) return $this->row['Id'] ?? null;
      return $GLOBALS['event_exists'] ? 7 : null;
    }
    // Event::canEdit(): Events=Edit and the event's monitor visible. A row built
    // from the request names whatever monitor the caller chose.
    public function canEdit() {
      return visibleMonitor($this->MonitorId()) and level('Events') === 'Edit';
    }
    public function MonitorId() { return ($this->row !== null) ? ($this->row['MonitorId'] ?? null) : 1; }
    public function Monitor() { return new Monitor(); }
    public function Path() { return $GLOBALS['event_path']; }
    public function DefaultVideo() { return $GLOBALS['default_video'] ?? ''; }
    public function delete() { $GLOBALS['queries'][] = 'Event::delete'; }
  }
}

namespace {
  class StopRequest extends Exception {}
  class StubUser {
    public function Id() { return 2; }
    public function Username() { return 'tester'; }
  }

  // includes/TagOrder.php and includes/Event.php are required by relative path;
  // point the include path at empty stand-ins (the classes are defined above).
  $stubdir = sys_get_temp_dir().'/zm_event_writes_stubs_'.getmypid();
  @mkdir($stubdir.'/includes', 0700, true);
  file_put_contents($stubdir.'/includes/TagOrder.php', "<?php\n");
  file_put_contents($stubdir.'/includes/Event.php', "<?php\n");
  set_include_path($stubdir.PATH_SEPARATOR.get_include_path());

  define('RATE_BASE', 100);
  define('SCALE_BASE', 100);
  define('ZM_WEB_DEFAULT_RATE', 100);
  define('ZM_WEB_DEFAULT_SCALE', 100);
  define('ZM_FFMPEG_FORMATS', 'mp4* mpg mpeg wmv asf avi mov swf 3gp**');

  $GLOBALS['perms'] = array();
  $GLOBALS['event_exists'] = true;
  $GLOBALS['monitor_visible'] = true;
  $GLOBALS['queries'] = array();
  $GLOBALS['warnings'] = array();
  $user = new StubUser();

  function level($area) { return $GLOBALS['perms'][$area] ?? 'None'; }
  function canView($area, $mid = false) { return in_array(level($area), array('View', 'Edit'), true); }
  function canEdit($area, $mid = false) { return level($area) === 'Edit'; }
  // monitor_visible is the event's monitor; monitor 2 stands for another one the user can view
  function visibleMonitor($mid) { return ((string)$mid === '2') or $GLOBALS['monitor_visible']; }
  function validCardinal($v) { return preg_match('/^\d+$/', (string)$v) ? (int)$v : ''; }
  // as includes/functions.php: an array goes through element by element
  function validInt($v) { return preg_replace('/[^\-\d]/', '', $v); }
  function localRedirectUrl($url, $fallback) { return $fallback; }
  function reScale($a, $b, $c) { return $a; }
  function validHtmlStr($s) { return htmlspecialchars((string)$s); }
  function translate($s) { return $s; }
  function dbFetchAll($sql, $col = null, $params = array()) { $GLOBALS['queries'][] = trim($sql); return array(); }
  function dbQuery($sql, $params = array()) { $GLOBALS['queries'][] = trim($sql); return true; }
  function dbNumRows($sql, $params = array()) { $GLOBALS['queries'][] = trim($sql); return 0; }
  function dbInsertId() { return 1; }
  function ajaxResponse($data = array()) { throw new StopRequest('response'); }
  function ajaxError($message, $code = 200) { throw new StopRequest('error'); }
  function xhtmlHeaders($file, $title) { if (empty($GLOBALS['render'])) throw new StopRequest('render'); }
  function getBodyTopHTML() {}
  function getNavBarHTML() { return ''; }
  function buildSelect($name, $options) { return ''; }
  function human_filesize($n) { return $n.'B'; }
  function xhtmlFooter() {}

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

  function run_request($file, $eventsPerm, array $request, array $otherPerms = array()) {
    global $user;
    $GLOBALS['perms'] = array_merge(array('Events' => $eventsPerm, 'Monitors' => 'View'), $otherPerms);
    $GLOBALS['queries'] = array();
    $GLOBALS['warnings'] = array();
    $_REQUEST = $request;
    $outcome = 'fell through';
    $rates = $scales = array();  // set by the skin around a real view
    $skin = 'classic';
    ob_start();
    try {
      include __DIR__.'/../../web/'.$file;
    } catch (StopRequest $e) {
      $outcome = $e->getMessage();
    }
    $GLOBALS['output'] = ob_get_clean();
    return $outcome;
  }

  function writes() {
    return array_values(array_filter($GLOBALS['queries'], function($q) {
      return preg_match('/^(INSERT|UPDATE|DELETE)\b/', $q) or $q === 'TagOrder::recordUsage' or $q === 'Event::delete';
    }));
  }

  // ---- ajax/event.php addtag / removetag -----------------------------------
  foreach (array('addtag', 'removetag') as $action) {
    $req = array('request' => 'event', 'action' => $action, 'tid' => '3', 'id' => '7');
    check("Events=View $action is refused", run_request('ajax/event.php', 'View', $req), 'error');
    check("Events=View $action sends no write", writes(), array());
    check("Events=Edit $action is answered", run_request('ajax/event.php', 'Edit', $req), 'response');
    check("Events=Edit $action writes", count(writes()) > 0, true);

    $GLOBALS['monitor_visible'] = false;
    check("Events=Edit $action on a hidden monitor's event is refused", run_request('ajax/event.php', 'Edit', $req), 'error');
    check("Events=Edit $action on a hidden monitor's event sends no write", writes(), array());
    $GLOBALS['monitor_visible'] = true;
    $GLOBALS['event_exists'] = false;
    check("Events=Edit $action on a missing event is refused", run_request('ajax/event.php', 'Edit', $req), 'error');
    check("Events=Edit $action on a missing event sends no write", writes(), array());
    $GLOBALS['event_exists'] = true;
    check("Events=Edit $action with an array id is refused",
      run_request('ajax/event.php', 'Edit', array_merge($req, array('id' => array('7')))), 'error');
  }

  // ---- ajax/event.php: the other Edit-level actions -------------------------
  $edits = array(
    'rename' => array('eventName' => 'n'),
    'eventdetail' => array('newEvent' => array('Cause' => 'c', 'Notes' => 'n')),
    'archive' => array(),
    'unarchive' => array(),
    'delete' => array(),
  );
  foreach ($edits as $action => $extra) {
    $req = array_merge(array('request' => 'event', 'action' => $action, 'id' => '7'), $extra);
    check("Events=View $action is refused", run_request('ajax/event.php', 'View', $req), 'error');
    check("Events=View $action sends no write", writes(), array());
    check("Events=Edit $action is answered", run_request('ajax/event.php', 'Edit', $req), 'response');
    check("Events=Edit $action writes", count(writes()), 1);
    // a hidden and a missing event: no write, and the same answer
    $GLOBALS['monitor_visible'] = false;
    $hidden = run_request('ajax/event.php', 'Edit', $req);
    check("Events=Edit $action on a hidden monitor's event sends no write", writes(), array());
    $GLOBALS['monitor_visible'] = true;
    $GLOBALS['event_exists'] = false;
    $missing = run_request('ajax/event.php', 'Edit', $req);
    check("Events=Edit $action on a missing event sends no write", writes(), array());
    $GLOBALS['event_exists'] = true;
    check("Events=Edit $action answers a hidden event as a missing one", $hidden, $missing);
    check("Events=Edit $action with an array id sends no write",
      array(run_request('ajax/event.php', 'Edit', array_merge($req, array('id' => array('7')))), writes()),
      array($action == 'delete' ? 'response' : 'error', array()));
  }

  // ---- ajax/event.php getselectedtags (read) --------------------------------
  function tag_reads() {
    return count(array_filter($GLOBALS['queries'], function($q) { return strpos($q, 'Events_Tags') !== false; }));
  }
  $req = array('request' => 'event', 'action' => 'getselectedtags', 'id' => '7');
  check('Events=View getselectedtags is answered', run_request('ajax/event.php', 'View', $req), 'response');
  check('Events=View getselectedtags reads the tags', tag_reads(), 1);
  $GLOBALS['monitor_visible'] = false;
  check("getselectedtags on a hidden monitor's event is answered", run_request('ajax/event.php', 'View', $req), 'response');
  check("getselectedtags on a hidden monitor's event reads no tags", tag_reads(), 0);
  $GLOBALS['monitor_visible'] = true;

  // ---- includes/actions/eventdetail.php (the eventdetail modal's form) ------
  class StubConn { public function beginTransaction() {} public function commit() {} }
  function run_eventdetail_action($eventsPerm, $markEids) {
    $GLOBALS['perms'] = array('Events' => $eventsPerm, 'Monitors' => 'View');
    $GLOBALS['queries'] = array();
    $GLOBALS['warnings'] = array();
    $_REQUEST = array('view' => 'eventdetail', 'action' => 'eventdetail', 'markEids' => $markEids,
      'newEvent' => array('Name' => 'n', 'Cause' => 'c', 'Notes' => 'x'));
    $_SERVER['HTTP_REFERER'] = '';
    $action = 'eventdetail';
    $dbConn = new StubConn();
    include __DIR__.'/../../web/includes/actions/eventdetail.php';
    return writes();
  }
  $upd = 'UPDATE Events SET Name=?, Cause=?, Notes=? WHERE Id=?';
  check('actions/eventdetail.php Events=View writes nothing', run_eventdetail_action('View', array('7')), array());
  check('actions/eventdetail.php Events=Edit updates the event', run_eventdetail_action('Edit', array('7', '7')), array($upd, $upd));
  $GLOBALS['monitor_visible'] = false;
  check("actions/eventdetail.php skips a hidden monitor's event", run_eventdetail_action('Edit', array('7')), array());
  $GLOBALS['monitor_visible'] = true;
  $GLOBALS['event_exists'] = false;
  check('actions/eventdetail.php skips a missing event', run_eventdetail_action('Edit', array('7')), array());
  $GLOBALS['event_exists'] = true;
  check('actions/eventdetail.php skips an array id', run_eventdetail_action('Edit', array(array('7'))), array());
  $GLOBALS['monitor_visible'] = false;
  check('actions/eventdetail.php skips an id given as an event row', run_eventdetail_action('Edit', array(array('Id' => '7', 'MonitorId' => '2'))), array());
  $GLOBALS['monitor_visible'] = true;

  // ---- ajax/tags.php -------------------------------------------------------
  $create = array('request' => 'tags', 'action' => 'createtag', 'tname' => 'x');
  $list = array('request' => 'tags', 'action' => 'getavailabletags');
  check('Events=None createtag is refused', run_request('ajax/tags.php', 'None', $create), 'error');
  check('Events=None getavailabletags is refused', run_request('ajax/tags.php', 'None', $list), 'error');
  check('Events=View createtag is refused', run_request('ajax/tags.php', 'View', $create), 'error');
  check('Events=View createtag sends no write', writes(), array());
  check('Events=View getavailabletags is answered', run_request('ajax/tags.php', 'View', $list), 'response');
  check('Snapshots=View getavailabletags is answered (the event view serves them too)',
    run_request('ajax/tags.php', 'None', $list, array('Snapshots' => 'View')), 'response');
  check('Snapshots=View createtag is refused', run_request('ajax/tags.php', 'None', $create, array('Snapshots' => 'View')), 'error');
  check('Events=Edit createtag is answered', run_request('ajax/tags.php', 'Edit', $create), 'response');
  check('Events=Edit createtag inserts', writes(), array('INSERT INTO Tags (Name, CreatedBy) VALUES (?, ?)'));

  // ---- views/video.php deleteIndex -----------------------------------------
  $GLOBALS['event_path'] = sys_get_temp_dir().'/zm_event_writes_event_'.getmypid();
  @mkdir($GLOBALS['event_path'], 0700, true);
  $recording = $GLOBALS['event_path'].'/7-video.h264.mp4';
  $del = array('view' => 'video', 'eid' => '7', 'deleteIndex' => '0');

  file_put_contents($recording, 'x');
  run_request('skins/classic/views/video.php', 'View', $del);
  check('Events=View deleteIndex keeps the recording', file_exists($recording), true);
  check('Events=View deleteIndex logs a warning', count($GLOBALS['warnings']), 1);

  run_request('skins/classic/views/video.php', 'Edit', $del);
  check('Events=Edit deleteIndex removes the file', file_exists($recording), false);

  file_put_contents($recording, 'x');
  run_request('skins/classic/views/video.php', 'Edit', array('view' => 'video', 'eid' => '7', 'deleteIndex' => '5'));
  check('Events=Edit deleteIndex out of range removes nothing', file_exists($recording), true);

  // ---- views/video.php: Events view deletes generated videos, not the recording
  function index_of($name) {
    $order = array_values(array_filter(scandir($GLOBALS['event_path'], SCANDIR_SORT_NONE),
      function($n) { return is_file($GLOBALS['event_path'].'/'.$n); }));
    return (string)array_search($name, $order, true);
  }
  $p = $GLOBALS['event_path'];
  $files = array('7-video.h264.mp4', 'incomplete.mp4', 'custom.mp4', 'Event-7-r1-s1.mp4');
  foreach ($files as $f) file_put_contents("$p/$f", 'x');
  $GLOBALS['default_video'] = 'custom.mp4';
  foreach (array('7-video.h264.mp4' => true, 'incomplete.mp4' => true, 'custom.mp4' => true,
                 'Event-7-r1-s1.mp4' => false) as $f => $kept) {
    run_request('skins/classic/views/video.php', 'View', array('view' => 'video', 'eid' => '7', 'deleteIndex' => index_of($f)));
    check("Events=View deleteIndex ".($kept ? 'keeps the recording' : 'removes the generated video')." $f", file_exists("$p/$f"), $kept);
  }
  file_put_contents("$p/Event-7-r1-s1.mp4", 'x');
  $GLOBALS['render'] = true;
  run_request('skins/classic/views/video.php', 'View', array('view' => 'video', 'eid' => '7'));
  preg_match_all('/showIndex=\d+">([^<]+)<\/a>(.*?)<\/tr>/s', $GLOBALS['output'], $rows, PREG_SET_ORDER);
  $deletable = array();
  foreach ($rows as $r) if (strpos($r[2], 'deleteIndex=') !== false) $deletable[] = $r[1];
  check('Events=View list offers Delete only for the generated video', $deletable, array('Event-7-r1-s1.mp4'));
  run_request('skins/classic/views/video.php', 'Edit', array('view' => 'video', 'eid' => '7'));
  check('Events=Edit list offers Delete for every file', substr_count($GLOBALS['output'], 'deleteIndex='), 4);
  $GLOBALS['render'] = false;
  $GLOBALS['default_video'] = '';
  foreach ($files as $f) @unlink("$p/$f");

  // ---- views/video.php list: each row's links point at that row's file -----
  // Empty and non-empty files interleaved, so readdir order puts some empty file
  // before a listed one whatever the filesystem's order is.
  @unlink($recording);
  $made = array();
  foreach (array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h') as $i => $n) {
    $f = $GLOBALS['event_path']."/gen-$n.mp4";
    file_put_contents($f, $i % 2 ? 'x' : '');
    $made[] = $f;
  }
  $order = array_values(array_filter(scandir($GLOBALS['event_path'], SCANDIR_SORT_NONE),
    function($n) { return is_file($GLOBALS['event_path'].'/'.$n); }));
  $GLOBALS['render'] = true;
  run_request('skins/classic/views/video.php', 'Edit', array('view' => 'video', 'eid' => '7'));
  $GLOBALS['render'] = false;
  preg_match_all('/showIndex=(\d+)">([^<]+)<\/a>.*?downloadIndex=(\d+).*?deleteIndex=(\d+)/s', $GLOBALS['output'], $rows, PREG_SET_ORDER);
  check('video list shows the 4 non-empty files', count($rows), 4);
  $aligned = 0;
  foreach ($rows as $r) {
    if ($order[$r[1]] === $r[2] and $order[$r[3]] === $r[2] and $order[$r[4]] === $r[2]) $aligned++;
  }
  check('video list links point at the file in their row', $aligned, count($rows));
  foreach ($made as $f) @unlink($f);

  @unlink($recording);
  @rmdir($GLOBALS['event_path']);
  @unlink($stubdir.'/includes/TagOrder.php');
  @unlink($stubdir.'/includes/Event.php');
  @rmdir($stubdir.'/includes');
  @rmdir($stubdir);

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
