<?php
// ajax/status.php:
//  - entity=system counted soft-deleted monitors in MonitorCount and
//    ActiveMonitorCount (a deleted monitor keeps its row and its Capturing value).
//  - sort[]=Id (an array) went straight to explode(), a TypeError on PHP 8.
//
// There is no sqlite to run the statement against, so the SQL the handler builds
// is checked for the Deleted restriction on both counts.
// status.php declares functions, so each scenario runs in a child php process.
//
// Run as: php tests/php/test_status_request.php

namespace ZM {
  function Debug($m) {}
  function Error($m) {}
  class Event {
    public function __construct($id = null) {}
    public function Id() { return 7; }
    public function canView() { return true; }
  }
}

namespace {
  if (!isset($argv[1])) {
    $failures = 0;
    foreach (array('system', 'sort_array', 'frame_short') as $scenario) {
      passthru(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' '.$scenario, $rc);
      if ($rc) $failures++;
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
  $sqls = array();
  function dbFetchOne($sql, $col = null, $params = array()) {
    $GLOBALS['sqls'][] = $sql;
    return array('MonitorCount' => 1, 'ActiveMonitorCount' => 1, 'Id' => 7);
  }
  function dbFetchAll($sql, $col = null, $params = array()) { $GLOBALS['sqls'][] = $sql; return array(); }
  function ajaxResponse($data = array()) { $GLOBALS['response'] = $data; throw new StopRequest('ok'); }
  function ajaxError($m) { throw new StopRequest($m); }

  $scenario = $argv[1];
  $_REQUEST = array('entity' => ($scenario == 'system') ? 'system' : 'frames');
  if ($scenario == 'sort_array') $_REQUEST += array('id' => '7', 'sort' => array('FrameId'));
  if ($scenario == 'frame_short') {
    $_REQUEST = array('entity' => 'frame', 'id' => array('7', '3'), 'element' => array('TimeStampShort'));
  }
  $thrown = '';
  try {
    // status.php requires includes/log_redact.php relative to web/, as under the web server.
    set_include_path(__DIR__.'/../../web'.PATH_SEPARATOR.get_include_path());
    include __DIR__.'/../../web/ajax/status.php';
  } catch (StopRequest $e) {
  } catch (Throwable $e) {
    $thrown = $e->getMessage();
  }

  if ($scenario == 'system') {
    $sql = $sqls ? $sqls[0] : '';
    $ok = preg_match('/count\(if\(`?Deleted`? *= *(0|false),[^)]*\)\) as MonitorCount/i', $sql)
      && preg_match('/count\(if\(`?Deleted`? *= *(0|false) *and `Capturing` != \'None\',/i', $sql);
    echo ($ok ? 'ok' : 'FAIL').' - system counts leave out deleted monitors'.($ok ? '' : " ($sql)")."\n";
  } else if ($scenario == 'frame_short') {
    // MySQL date_format() only expands %-specifiers, so the PHP style 'Y/m/d' the
    // constant used to hold came back as that literal text, and the frame's own
    // TimeStamp is what is shortened, not its event's start.
    $sql = $sqls ? $sqls[0] : '';
    $fmt = file_get_contents(__DIR__.'/../../web/includes/config.php.in');
    $ok = preg_match("/define\('MYSQL_FMT_DATETIME_SHORT', '[^']*%y[^']*'\)/", $fmt)
      && preg_match('/date_format\( Frames\.TimeStamp, /', $sql);
    echo ($ok ? 'ok' : 'FAIL').' - TimeStampShort formats the frame TimeStamp with a MySQL format'.($ok ? '' : " ($sql; threw: $thrown)")."\n";
  } else {
    $ok = ($thrown === '');
    echo ($ok ? 'ok' : 'FAIL').' - array sort is ignored'.($ok ? '' : " (threw: $thrown)")."\n";
  }
  exit($ok ? 0 : 1);
}
