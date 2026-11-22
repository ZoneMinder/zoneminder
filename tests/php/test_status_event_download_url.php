<?php
// ajax/status.php entity=event, the reply the event view's getEventResponse()
// receives when the view moves to another event without a page load. It used to
// carry no download link, so the view's Download button kept the href event.php
// rendered for the page's event and saved that first event's video.
//
// The reply now carries DownloadUrl, which is Event::getStreamSrc(mode=mp4) for
// the event being answered for - built server side so a multi-server setup gets
// the right host and the auth relay comes along. This checks the field is there,
// that getStreamSrc() is asked for mode=mp4 with '&' as the query separator (the
// link goes into an href, not the page's HTML), and that an event with no
// DefaultVideo gets an empty one, as event.php hides the link for such an event.
//
// status.php declares functions, so each scenario runs in a child php process.
//
// Run as: php tests/php/test_status_event_download_url.php

namespace ZM {
  function Debug($m) {}
  function Error($m) {}

  // getStreamSrc() is what builds the link, so the stub records the arguments
  // it is called with instead of reproducing Server::Url() and the auth relay.
  class Event {
    public $id;
    public $defaultVideo;
    public function __construct($idOrRow = null) {
      if (is_array($idOrRow)) {
        $this->id = $idOrRow['Id'];
        $this->defaultVideo = $idOrRow['DefaultVideo'];
      } else {
        $this->id = $idOrRow;
        $this->defaultVideo = $GLOBALS['test_default_video'];
      }
    }
    public function Id() { return 7; }
    public function canView() { return true; }
    public function DefaultVideo() { return $this->defaultVideo; }
    public function getStreamSrc($args = array(), $querySep = '&') {
      $GLOBALS['stream_src_calls'][] = array($this->id, $args, $querySep);
      return 'https://nvr2.example/zm/index.php?mode='.$args['mode'].'&eid='.$this->id.'&view=view_video&auth=abc';
    }
  }
}

namespace {
  if (!isset($argv[1])) {
    $failures = 0;
    foreach (array('video', 'novideo') as $scenario) {
      passthru(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' '.$scenario, $rc);
      if ($rc) $failures++;
    }
    exit($failures ? 1 : 0);
  }

  class StopRequest extends Exception {}
  define('ZM_OPT_USE_AUTH', false);
  define('ZM_AUTH_RELAY', 'none');
  define('MYSQL_FMT_DATETIME_SHORT', '%y/%m/%d %H:%i:%S');

  class FormatterStub {
    public function format($ts) { return gmdate('Y-m-d H:i:s', $ts); }
  }
  $dateTimeFormatter = new FormatterStub();
  $stream_src_calls = array();

  function canView($area) { return true; }
  function validJsStr($v) { return (is_null($v) or is_array($v)) ? '' : strip_tags(addslashes($v)); }
  function validInt($v) { return is_array($v) ? '' : preg_replace('/[^\-\d]/', '', $v); }
  function validCardinal($v) { return is_scalar($v) ? preg_replace('/\D/', '', $v) : ''; }
  function translate($s) { return $s; }
  function dbFetchOne($sql, $col = null, $params = array()) {
    return array(
      'Id' => 11, 'MonitorId' => 1,
      'StartDateTime' => '2026-11-16 10:00:00', 'EndDateTime' => null,
      'DefaultVideo' => $GLOBALS['test_default_video'],
    );
  }
  function dbFetchAll($sql, $col = null, $params = array()) { return array(dbFetchOne($sql)); }
  function ajaxResponse($data = array()) { $GLOBALS['response'] = $data; throw new StopRequest('ok'); }
  function ajaxError($m) { throw new StopRequest($m); }

  $GLOBALS['test_default_video'] = ($argv[1] === 'video') ? '11-video.mp4' : '';

  $_REQUEST = array('entity' => 'event', 'id' => '11');
  try {
    include __DIR__.'/../../web/ajax/status.php';
  } catch (StopRequest $e) {
  }

  $row = isset($GLOBALS['response']['event']) ? $GLOBALS['response']['event'] : array();
  $failures = 0;

  if (!array_key_exists('DownloadUrl', $row)) {
    $failures++;
    echo "FAIL - the event reply has no DownloadUrl\n";
    exit(1);
  }

  if ($argv[1] === 'video') {
    $ok = ($row['DownloadUrl'] === 'https://nvr2.example/zm/index.php?mode=mp4&eid=11&view=view_video&auth=abc');
    if (!$ok) {
      $failures++;
      echo "FAIL - DownloadUrl is ".var_export($row['DownloadUrl'], true)."\n";
    } else {
      echo "ok - the reply carries the download link for the event\n";
    }
    $call = $stream_src_calls ? $stream_src_calls[0] : null;
    $ok = $call && ($call[1]['mode'] === 'mp4') && ($call[2] === '&');
    if (!$ok) {
      $failures++;
      echo "FAIL - getStreamSrc was called as ".var_export($call, true)."\n";
    } else {
      echo "ok - the link is Event::getStreamSrc(mode=mp4) with '&'\n";
    }
  } else {
    $ok = ($row['DownloadUrl'] === '');
    if (!$ok) {
      $failures++;
      echo "FAIL - an event with no DefaultVideo got ".var_export($row['DownloadUrl'], true)."\n";
    } else {
      echo "ok - an event with no DefaultVideo gets an empty DownloadUrl\n";
    }
  }

  exit($failures ? 1 : 0);
}
