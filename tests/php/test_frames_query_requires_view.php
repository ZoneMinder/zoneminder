<?php
// Security regression test: ajax/frames.php (task=query) lists the frame
// records of an event only when the user may view that event. The handler
// checked the global canView('Events') but then built the Frames query from
// the event id alone, so a user with Events=View and a monitor permission of
// None on the event's monitor could read the frame rows (number, type,
// timestamp, delta, score) of any event on a denied monitor. refs
// GHSA-8fj6-vrj2-vxmr.
//
// Includes the real file with the web helpers stubbed and records every SQL
// query it sends, so the test can assert the Frames table is never read for a
// denied event. frames.php declares queryRequest() at file scope, so it can
// only be included once per process: each case runs in a child php process.
//
// Run as: php tests/php/test_frames_query_requires_view.php

namespace ZM {
  function Debug($m) {}
  function Warning($m) {}
  function Error($m) {}
  function Fatal($m) { throw new \Exception('Fatal: '.$m); }

  class Event {
    private $id;
    public function __construct($id) {
      $this->id = $id;
      $GLOBALS['events_constructed'][] = $id;
    }
    // Mirrors the real Event::canView(): needs Events view (or the event in a
    // snapshot, not modelled here) AND the event's monitor to be viewable; a
    // non-existent event is not viewable.
    public function canView() {
      return $GLOBALS['event_exists'] and $GLOBALS['monitor_visible'] and \canView('Events');
    }
    public function Monitor() { return new Monitor(); }
    public function MonitorId() { return 1; }
    public function Path() { return '/var/cache/zoneminder/events/x'; }
  }
  class Monitor {
    public function ViewHeight() { return 480; }
    public function ViewWidth() { return 640; }
  }
}

namespace {
  class StopRequest extends Exception {}

  // frames.php does require_once('includes/Filter.php') unconditionally; point
  // the include path at an empty stand-in (the class, if ever reached, is not
  // exercised because these cases use no search terms).
  $stubdir = sys_get_temp_dir().'/zm_frames_stubs_'.getmypid();
  @mkdir($stubdir.'/includes', 0700, true);
  file_put_contents($stubdir.'/includes/Filter.php', "<?php\n");
  set_include_path($stubdir.PATH_SEPARATOR.get_include_path());

  if (!defined('ZM_WEB_LIST_THUMBS')) define('ZM_WEB_LIST_THUMBS', false);

  $GLOBALS['perms'] = array();
  $GLOBALS['event_exists'] = true;
  $GLOBALS['monitor_visible'] = true;
  $GLOBALS['queries'] = array();
  $GLOBALS['events_constructed'] = array();
  $GLOBALS['last_error'] = '';

  function canView($area, $mid = false) {
    return in_array($GLOBALS['perms'][$area] ?? 'None', array('View', 'Edit'), true);
  }
  function validInt($v) { return (int)$v; }
  function dbFetchAll($sql, $col = null, $params = array()) {
    $GLOBALS['queries'][] = trim($sql);
    return array(
      array('FrameId' => 1, 'EventId' => 2, 'Type' => 'Normal', 'TimeStamp' => '2026-11-01 03:14:00', 'Delta' => 0, 'Score' => 0),
      array('FrameId' => 2, 'EventId' => 2, 'Type' => 'Alarm',  'TimeStamp' => '2026-11-01 03:14:02', 'Delta' => 2, 'Score' => 75),
    );
  }
  function ajaxResponse($data = array()) { throw new StopRequest('response'); }
  function ajaxError($message, $code = 200) {
    $GLOBALS['last_error'] = $message;
    throw new StopRequest('error');
  }

  class StubFormatter { public function format($t) { return 'now'; } }
  $dateTimeFormatter = new StubFormatter();
  class StubUser { public function Username() { return 'tester'; } }
  $user = new StubUser();

  function frames_read() {
    foreach ($GLOBALS['queries'] as $q) {
      if (stripos($q, 'FROM `Frames`') !== false) return true;
    }
    return false;
  }

  // Child mode: set up the case, include the real file once, print the result.
  if (isset($argv[1])) {
    $case = json_decode($argv[1], true);
    $GLOBALS['perms'] = $case['perms'];
    $GLOBALS['monitor_visible'] = $case['monitor_visible'];
    $GLOBALS['event_exists'] = $case['event_exists'];
    $_REQUEST = array('task' => 'query', 'eid' => '2');
    $outcome = 'fell through';
    ob_start();
    try {
      include __DIR__.'/../../web/ajax/frames.php';
    } catch (StopRequest $e) {
      $outcome = $e->getMessage();
    } catch (\Throwable $e) {
      $outcome = 'threw: '.$e->getMessage();
    }
    ob_end_clean();
    echo json_encode(array(
      'outcome' => $outcome,
      'frames_read' => frames_read(),
      'events_constructed' => $GLOBALS['events_constructed'],
    ));
    exit(0);
  }

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

  function run_query(array $perms, $monitor_visible = true, $event_exists = true) {
    $arg = json_encode(array(
      'perms' => $perms,
      'monitor_visible' => $monitor_visible,
      'event_exists' => $event_exists,
    ));
    $out = shell_exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' '.escapeshellarg($arg));
    $r = json_decode($out, true);
    if (!is_array($r)) {
      echo "child failed: $out\n";
      exit(1);
    }
    return $r;
  }

  $view = array('Events' => 'View', 'Monitors' => 'View');

  // Viewable event: answered, and the Frames table is read.
  $r = run_query($view, true, true);
  check('Events=View of a viewable event is answered', $r['outcome'], 'response');
  check('a viewable event reads the Frames table', $r['frames_read'], true);

  // Denied monitor: refused, and the Frames table is never touched.
  $r = run_query($view, false, true);
  check("Events=View of a denied monitor's event is refused", $r['outcome'], 'error');
  check("a denied monitor's event never reads the Frames table", $r['frames_read'], false);
  check('the denied event was tested through Event::canView', in_array(2, $r['events_constructed']), true);

  // A non-existent event is refused (Event::canView is false with no Id).
  $r = run_query($view, true, false);
  check('a missing event is refused', $r['outcome'], 'error');
  check('a missing event reads no Frames', $r['frames_read'], false);

  // No Events view at all: the pre-existing global gate still refuses.
  $r = run_query(array('Monitors' => 'View'), true, true);
  check('no Events view is refused', $r['outcome'], 'error');
  check('no Events view reads no Frames', $r['frames_read'], false);

  // Structural: the real file must route the event id through Event::canView()
  // before dispatching the query. Fails if the guard is reverted.
  $src = file_get_contents(__DIR__.'/../../web/ajax/frames.php');
  check('source constructs ZM\\Event and calls canView() in the sanity block',
    (bool)preg_match('/new\s+ZM\\\\Event\(\s*\$eid\s*\)\)->canView\(\)/', $src), true);
  check('the canView guard precedes the queryRequest dispatch',
    (strpos($src, '->canView()') !== false)
      and (strpos($src, '->canView()') < strpos($src, 'queryRequest(')), true);

  @unlink($stubdir.'/includes/Filter.php');
  @rmdir($stubdir.'/includes');
  @rmdir($stubdir);

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
