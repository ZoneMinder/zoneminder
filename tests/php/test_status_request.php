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
}

namespace {
  if (!isset($argv[1])) {
    $failures = 0;
    foreach (array('system', 'sort_array') as $scenario) {
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
  function validInt($v) { return preg_replace('/[^\-\d]/', '', $v); }
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
  $thrown = '';
  try {
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
  } else {
    $ok = ($thrown === '');
    echo ($ok ? 'ok' : 'FAIL').' - array sort is ignored'.($ok ? '' : " (threw: $thrown)")."\n";
  }
  exit($ok ? 0 : 1);
}
