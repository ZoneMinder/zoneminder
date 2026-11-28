<?php
// ajax/status.php: a list entity (events, frames) asked for count=1 took the
// single-row path because the effective limit was 1, and answered with one
// bare row instead of a list holding that row. Callers reading
// response.events[0] got undefined while count=2 worked.
//
// Run as: php tests/php/test_status_count_one_list.php [repo root]

namespace ZM {
  function Debug($m) {}
  function Error($m) {}
  class Event {
    public function __construct($id = null) { $this->id = $id; }
    public function Id() { return $this->id ?: 7; }
    public function canView() { return true; }
  }
}

namespace {
  if (!isset($argv[2])) {
    $root = isset($argv[1]) ? $argv[1] : dirname(__DIR__, 2);
    $failures = 0;
    foreach (array(array('events', 1), array('events', 2), array('frames', 1), array('event', 0)) as $c) {
      passthru(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' '.escapeshellarg($root).' '.$c[0].' '.$c[1], $rc);
      if ($rc) $failures++;
    }
    exit($failures ? 1 : 0);
  }

  class StopRequest extends Exception {}
  define('ZM_OPT_USE_AUTH', false);
  define('ZM_AUTH_RELAY', 'none');
  define('MYSQL_FMT_DATETIME_SHORT', '%y/%m/%d %H:%i:%S');
  class FormatterStub { public function format($ts) { return 'x'; } }
  $dateTimeFormatter = new FormatterStub();
  function canView($area) { return true; }
  function validJsStr($v) { return (is_null($v) or is_array($v)) ? '' : strip_tags(addslashes($v)); }
  function validInt($v) { return preg_replace('/[^\-\d]/', '', $v); }
  function fixtureRows() {
    return array(
      array('Id' => 7, 'FrameId' => 1, 'MonitorId' => 1, 'EventId' => 7, 'StartDateTime' => '2026-11-16 10:00:00', 'EndDateTime' => null),
      array('Id' => 8, 'FrameId' => 2, 'MonitorId' => 1, 'EventId' => 7, 'StartDateTime' => '2026-11-16 11:00:00', 'EndDateTime' => null),
    );
  }
  function dbFetchOne($sql, $col = null, $params = array()) { return fixtureRows()[0]; }
  function dbFetchAll($sql, $col = null, $params = array()) { return fixtureRows(); }
  function ajaxResponse($data = array()) { $GLOBALS['response'] = $data; throw new StopRequest('ok'); }
  function ajaxError($m) { throw new StopRequest($m); }

  $root = $argv[1];
  $entity = $argv[2];
  $count = (int)$argv[3];
  $_REQUEST = array('entity' => $entity, 'id' => '7', 'element' => array($entity == 'frames' ? 'FrameId' : 'Id'));
  if ($count) $_REQUEST['count'] = (string)$count;
  try {
    // status.php requires includes/log_redact.php relative to web/, as under the web server.
    set_include_path($root.'/web'.PATH_SEPARATOR.get_include_path());
    include $root.'/web/ajax/status.php';
  } catch (StopRequest $e) {
  }
  $got = $GLOBALS['response'][$entity];
  $isList = is_array($got) && array_is_list($got);
  $want = ($entity == 'event') ? false : true;
  if ($isList === $want) {
    echo "ok - $entity count=$count: ".($want ? 'list' : 'single row')."\n";
    exit(0);
  }
  echo "FAIL - $entity count=$count: got ".json_encode($got)."\n";
  exit(1);
}
