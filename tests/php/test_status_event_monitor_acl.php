<?php
// ajax/status.php answered the event, frames, frame, frameimage, nearframe and
// nearevents entities for any event once the user had the Events view permission,
// so a user denied a monitor could read its events and frames. The entities now
// refuse an event whose monitor the user cannot view.
//
// event 5 is on a monitor the user can view; event 6 is on one the user cannot.
// status.php declares functions, so each scenario runs in a child php process.
//
// Run as: php tests/php/test_status_event_monitor_acl.php

namespace ZM {
  function Debug($m) {}
  function Error($m) {}
  class Event {
    // Like MySQL, a numeric-prefixed string such as '6-1' matches event 6.
    public function __construct($id) { $this->id = in_array((int)$id, array(5, 6)) ? (int)$id : null; }
    public function Id() { return $this->id; }
    public function canView() { return $this->id == 5; }
  }
}

namespace {
  $root = isset($argv[1]) ? rtrim($argv[1], '/') : __DIR__.'/../..';

  if (!isset($argv[2])) {
    $cases = array(
      'event_hidden' => 'Insufficient permissions',
      'frames_hidden' => 'Insufficient permissions',
      'frame_hidden' => 'Insufficient permissions',
      'event_visible' => null,
      'frames_visible' => null,
      'frame_visible' => null,
      'frames_noid' => 'Event id required',
      'event_missing' => 'Event not found',
      'frames_missing' => 'Event not found',
      'event_mixed' => 'Insufficient permissions',
      'frames_mixed' => 'Insufficient permissions',
    );
    $failures = 0;
    foreach ($cases as $case => $expect) {
      $cmd = escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' '
        .escapeshellarg($root).' '.escapeshellarg($case).' 2>/dev/null';
      $output = array();
      exec($cmd, $output, $rc);
      $got = implode("\n", $output);
      if ($expect === null) {
        $ok = strpos($got, 'Insufficient permissions') !== 0;
      } else {
        $ok = strpos($got, $expect) === 0;
      }
      if (!$ok) $failures++;
      echo ($ok ? 'ok' : 'FAIL').' - '.$case.($ok ? '' : ' (got '.trim($got).')')."\n";
    }
    exit($failures ? 1 : 0);
  }

  class StopRequest extends Exception {}
  define('ZM_OPT_USE_AUTH', false);
  define('ZM_AUTH_RELAY', 'none');
  define('MYSQL_FMT_DATETIME_SHORT', '%y/%m/%d %H:%i:%S');

  function canView($area) { return true; }
  function daemonCheck() { return true; }
  function getLoad() { return 0; }
  function getDiskPercent() { return 0; }
  function translate($s) { return $s; }
  function validJsStr($v) { return (is_null($v) or is_array($v)) ? '' : strip_tags(addslashes($v)); }
  function validInt($v) { return is_array($v) ? '' : preg_replace('/[^\-\d]/', '', $v); }
  function validCardinal($v) { return is_scalar($v) ? preg_replace('/\D/', '', $v) : ''; }
  function dbFetchOne($sql, $col = null, $params = array()) { return array(); }
  function dbFetchAll($sql, $col = null, $params = array()) { return array(); }
  function ajaxResponse($data = array()) { $GLOBALS['response'] = $data; throw new StopRequest('ok'); }
  function ajaxError($m) { $GLOBALS['ajax_error'] = $m; throw new StopRequest($m); }

  $case = $argv[2];
  $entity = preg_replace('/_(hidden|visible|noid|missing|mixed)$/', '', $case);
  $_REQUEST = array('entity' => $entity);
  if ($case === 'frame_hidden') {
    $_REQUEST['id'] = array('6', '3');
  } else if ($case === 'frame_visible') {
    $_REQUEST['id'] = array('5', '3');
  } else if (substr($case, -8) === '_missing') {
    $_REQUEST['id'] = '99';
  } else if (substr($case, -6) === '_mixed') {
    // validCardinal reads this as 61, validInt as '6-1' which the query matches to event 6.
    $_REQUEST['id'] = '6-1';
  } else if ($case !== 'frames_noid') {
    $_REQUEST['id'] = (substr($case, -7) === '_hidden') ? '6' : '5';
  }

  ini_set('display_errors', '0');
  error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);

  $thrown = '';
  try {
    include $root.'/web/ajax/status.php';
  } catch (StopRequest $e) {
  } catch (Throwable $e) {
    $thrown = $e->getMessage();
  }

  if (isset($GLOBALS['ajax_error'])) {
    echo $GLOBALS['ajax_error']."\n";
  } else if ($thrown !== '') {
    echo $thrown."\n";
  } else {
    echo "no error\n";
  }
  exit(0);
}
