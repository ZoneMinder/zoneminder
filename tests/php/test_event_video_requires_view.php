<?php
// Security regression test: ajax/event.php action=video makes a video only of
// an event the user can view. The per-monitor limit (monitorLimitSql()) used to
// follow GROUP BY, where "GROUP BY E.Id AND MonitorId IN (...)" only changes
// the grouping and filters nothing, so a user denied a monitor could have
// zmvideo.pl encode that monitor's events and learn the file's path.
//
// Includes the real file with the web helpers stubbed, records the SQL it
// sends, and checks whether Event::createVideo() ran.
//
// Run as: php tests/php/test_event_video_requires_view.php

namespace ZM {
  class Event {
    public function __construct($row) {}
    public function createVideo($f, $r, $s, $t, $o) {
      $GLOBALS['made_video'] = true;
      return '/x/Event-7-r1-s1.mp4';
    }
    public function canView() { return $GLOBALS['monitor_visible'] and \canView('Events'); }
  }
}

namespace {
  class StopRequest extends Exception {}

  // includes/Event.php is required by relative path; point the include path at
  // an empty stand-in (the class is defined above).
  $stubdir = sys_get_temp_dir().'/zm_event_video_stubs_'.getmypid();
  @mkdir($stubdir.'/includes', 0700, true);
  file_put_contents($stubdir.'/includes/Event.php', "<?php\n");
  set_include_path($stubdir.PATH_SEPARATOR.get_include_path());

  $GLOBALS['perms'] = array();
  $GLOBALS['event_exists'] = true;
  $GLOBALS['monitor_visible'] = true;
  $GLOBALS['queries'] = array();
  $GLOBALS['made_video'] = false;

  function canView($area, $mid = false) {
    return in_array($GLOBALS['perms'][$area] ?? 'None', array('View', 'Edit'), true);
  }
  function canEdit($area, $mid = false) { return ($GLOBALS['perms'][$area] ?? 'None') === 'Edit'; }
  // The real monitorLimitSql() returns ' AND MonitorId IN (<viewable ids>)'
  // for a user with a per-monitor limit; the database would then return no row
  // when the clause sits in WHERE. The stub returns a row unless the clause is
  // in WHERE, so it fails the way the old query did.
  function monitorLimitSql() { return $GLOBALS['monitor_visible'] ? '' : ' AND MonitorId IN (2)'; }
  function dbFetchOne($sql, $col = null, $params = array()) {
    $GLOBALS['queries'][] = trim($sql);
    if (!$GLOBALS['event_exists']) return false;
    $limit = strpos($sql, 'MonitorId IN');
    if ($limit !== false and $limit < strpos($sql, 'GROUP BY')) return false;
    return array('Id' => 7, 'MonitorId' => 1);
  }
  function dbFetchAll($sql, $col = null, $params = array()) { $GLOBALS['queries'][] = trim($sql); return array(); }
  function dbQuery($sql, $params = array()) { $GLOBALS['queries'][] = trim($sql); return true; }
  function ajaxResponse($data = array()) { throw new StopRequest('response'); }
  function ajaxError($message, $code = 200) { throw new StopRequest('error'); }
  function validHtmlStr($s) { return htmlspecialchars((string)$s); }
  class StubUser { public function Username() { return 'tester'; } }
  $user = new StubUser();

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

  function run_video(array $perms) {
    global $user;
    $GLOBALS['perms'] = $perms;
    $GLOBALS['queries'] = array();
    $GLOBALS['made_video'] = false;
    $_REQUEST = array('request' => 'event', 'action' => 'video', 'id' => '7', 'videoFormat' => 'mp4',
                      'rate' => '100', 'scale' => '100', 'transform' => '');
    $outcome = 'fell through';
    ob_start();
    try {
      include __DIR__.'/../../web/ajax/event.php';
    } catch (StopRequest $e) {
      $outcome = $e->getMessage();
    }
    ob_end_clean();
    return $outcome;
  }

  $view = array('Events' => 'View', 'Monitors' => 'View');

  check('Events=View video is answered', run_video($view), 'response');
  check('Events=View video makes the video', $GLOBALS['made_video'], true);

  $GLOBALS['monitor_visible'] = false;
  check("Events=View video of a denied monitor's event is refused", run_video($view), 'error');
  check("Events=View video of a denied monitor's event makes no video", $GLOBALS['made_video'], false);
  $sql = $GLOBALS['queries'][0] ?? '';
  check('video query applies the monitor limit in WHERE, before GROUP BY',
    strpos($sql, 'MonitorId IN') !== false and strpos($sql, 'MonitorId IN') < strpos($sql, 'GROUP BY'), true);
  $GLOBALS['monitor_visible'] = true;

  $GLOBALS['event_exists'] = false;
  check('Events=View video of a missing event is refused', run_video($view), 'error');
  check('Events=View video of a missing event makes no video', $GLOBALS['made_video'], false);
  $GLOBALS['event_exists'] = true;

  // A Snapshots-only user reaches the action (canView('Snapshots')); the event
  // itself must pass Event::canView(), which needs Events view or the event
  // being in a snapshot (the stub models the Events part).
  check('Snapshots-only video of an event outside their snapshots is refused',
    run_video(array('Snapshots' => 'View', 'Monitors' => 'View')), 'error');
  check('Snapshots-only video makes no video', $GLOBALS['made_video'], false);

  check('no Events or Snapshots view is refused', run_video(array('Monitors' => 'View')), 'error');
  check('no Events or Snapshots view makes no video', $GLOBALS['made_video'], false);

  @unlink($stubdir.'/includes/Event.php');
  @rmdir($stubdir.'/includes');
  @rmdir($stubdir);

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
