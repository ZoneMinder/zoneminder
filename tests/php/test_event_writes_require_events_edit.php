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
//   * views/video.php deleteIndex (unlink() of a file in the event folder; the
//     list includes the event's own recording since ZM_FFMPEG_FORMATS has mp4)
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
    public function __construct($id) {}
    public function Id() { return $GLOBALS['event_exists'] ? 7 : null; }
    public function MonitorId() { return 1; }
    public function Monitor() { return new Monitor(); }
    public function Path() { return $GLOBALS['event_path']; }
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
  function visibleMonitor($mid) { return $GLOBALS['monitor_visible']; }
  function validCardinal($v) { return preg_match('/^\d+$/', (string)$v) ? (int)$v : ''; }
  function validInt($v) { return (int)$v; }
  function reScale($a, $b, $c) { return $a; }
  function validHtmlStr($s) { return htmlspecialchars((string)$s); }
  function translate($s) { return $s; }
  function dbFetchAll($sql, $col = null, $params = array()) { $GLOBALS['queries'][] = trim($sql); return array(); }
  function dbQuery($sql, $params = array()) { $GLOBALS['queries'][] = trim($sql); return true; }
  function dbNumRows($sql, $params = array()) { $GLOBALS['queries'][] = trim($sql); return 0; }
  function dbInsertId() { return 1; }
  function ajaxResponse($data = array()) { throw new StopRequest('response'); }
  function ajaxError($message, $code = 200) { throw new StopRequest('error'); }
  function xhtmlHeaders($file, $title) { throw new StopRequest('render'); }

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
    ob_start();
    try {
      include __DIR__.'/../../web/'.$file;
    } catch (StopRequest $e) {
      $outcome = $e->getMessage();
    }
    ob_end_clean();
    return $outcome;
  }

  function writes() {
    return array_values(array_filter($GLOBALS['queries'], function($q) {
      return preg_match('/^(INSERT|UPDATE|DELETE)\b/', $q) or $q === 'TagOrder::recordUsage';
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

  @unlink($recording);
  @rmdir($GLOBALS['event_path']);
  @unlink($stubdir.'/includes/TagOrder.php');
  @unlink($stubdir.'/includes/Event.php');
  @rmdir($stubdir.'/includes');
  @rmdir($stubdir);

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
