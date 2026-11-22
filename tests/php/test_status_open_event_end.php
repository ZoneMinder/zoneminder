<?php
// ajax/status.php entity=event (polled by the event view when zms moves to
// another event) formatted EndDateTime without checking it. An event still
// recording has EndDateTime NULL, so strtotime(null) raised a PHP 8.1
// deprecation and the formatter was handed false. The event view itself prints
// an empty End Time for such an event; the ajax answer must match.
//
// The formatter stub stands in for IntlDateFormatter, which rejects false.
// status.php declares functions, so each entity runs in a child php process.
//
// Run as: php tests/php/test_status_open_event_end.php

namespace ZM {
  function Debug($m) {}
  function Error($m) {}

  // The event reply now also carries DownloadUrl, built from a ZM\Event. This
  // test is about EndDateTime, so the stub only has to answer the two calls.
  class Event {
    public function __construct($idOrRow = null) {}
    public function Id() { return 7; }
    public function canView() { return true; }
    public function DefaultVideo() { return ''; }
    public function getStreamSrc($args = array(), $querySep = '&') { return ''; }
  }
}

namespace {
  if (!isset($argv[1])) {
    $failures = 0;
    foreach (array('event', 'events') as $entity) {
      passthru(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' '.$entity, $rc);
      if ($rc) $failures++;
    }
    exit($failures ? 1 : 0);
  }

  class StopRequest extends Exception {}

  define('ZM_OPT_USE_AUTH', false);
  define('ZM_AUTH_RELAY', 'none');
  define('MYSQL_FMT_DATETIME_SHORT', '%y/%m/%d %H:%i:%S');

  class FormatterStub {
    public function format($ts) {
      return is_int($ts) ? gmdate('Y-m-d H:i:s', $ts) : false;
    }
  }
  $dateTimeFormatter = new FormatterStub();

  $failures = 0;
  $deprecations = array();
  set_error_handler(function($no, $str) use (&$deprecations) {
    $deprecations[] = $str;
    return true;
  });

  function canView($area) { return true; }
  function validJsStr($v) { return (is_null($v) or is_array($v)) ? '' : strip_tags(addslashes($v)); }
  function validInt($v) { return preg_replace('/[^\-\d]/', '', $v); }
  function validCardinal($v) { return is_scalar($v) ? preg_replace('/\D/', '', $v) : ''; }
  function dbFetchOne($sql, $col = null, $params = array()) {
    return array('Id' => 7, 'MonitorId' => 1, 'StartDateTime' => '2026-11-16 10:00:00', 'EndDateTime' => null);
  }
  function dbFetchAll($sql, $col = null, $params = array()) { return array(dbFetchOne($sql)); }
  function ajaxResponse($data = array()) { $GLOBALS['response'] = $data; throw new StopRequest('ok'); }
  function ajaxError($m) { throw new StopRequest($m); }

  $entity = $argv[1];
  $_REQUEST = array('entity' => $entity, 'id' => '7');
  try {
    include __DIR__.'/../../web/ajax/status.php';
  } catch (StopRequest $e) {
  }
  $row = ($entity == 'event') ? $GLOBALS['response']['event'] : $GLOBALS['response']['events'][0];
  $got = $row['EndDateTimeFormatted'];
  if ($got === '' and !$deprecations) {
    echo "ok - $entity: open event has an empty formatted end time\n";
  } else {
    $failures++;
    echo "FAIL - $entity: got ".var_export($got, true).' '.implode('; ', $deprecations)."\n";
  }
  if ($row['StartDateTimeFormatted'] !== '2026-11-16 10:00:00') {
    $failures++;
    echo "FAIL - $entity: start time ".var_export($row['StartDateTimeFormatted'], true)."\n";
  }
  exit($failures ? 1 : 0);
}
