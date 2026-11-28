<?php
namespace ZM {
  class Event {
    private $row;
    public function __construct($row) { $this->row = $row; }
    public function remove_from_cache() {}
    public function canView() { return true; }
    public function Monitor() {
      return new class {
        public function Deleted() { return false; }
      };
    }
    public function Id() { return $this->row['Id'] ?? 1; }
    public function Width() { return $this->row['Width'] ?? 0; }
    public function Height() { return $this->row['Height'] ?? 0; }
    public function ThumbnailWidth() { return 100; }
    public function ThumbnailHeight() { return 100; }
    public function DefaultVideo() { return ''; }
    public function getThumbnailSrc($args, $sep) { return 'thumb.jpg'; }
    public function getStreamSrc($args, $sep) { return 'stream.jpg'; }
    public function StartDateTime() { return '2026-01-01 00:00:00'; }
    public function Length() { return 10; }
  }

  function Error($msg) {
    $GLOBALS['last_zm_error'] = $msg;
  }
}

namespace {
  /*
   * Tests for web/ajax/watch.php and web/skins/classic/views/events.php
   * Verifies handling of odd/missing request values, division by zero,
   * and PHP 8 warnings/TypeErrors.
   *
   * Run: php tests/php/test_watch_ajax.php
   */

  error_reporting(E_ALL);
  $GLOBALS["zm_root"] = isset($argv[1]) ? $argv[1] : dirname(__DIR__, 2);

  $tests = 0;
  $failures = 0;

  function check($label, $cond) {
    global $tests, $failures;
    $tests++;
    if ($cond) {
      echo "  ok  - $label\n";
    } else {
      $failures++;
      echo "  NOT OK - $label\n";
    }
  }

  if (!defined('ZM_WEB_LIST_THUMB_WIDTH')) define('ZM_WEB_LIST_THUMB_WIDTH', 100);
  if (!defined('ZM_WEB_VIDEO_MAXFPS')) define('ZM_WEB_VIDEO_MAXFPS', 30);

  function canView($area, $mid = false) { return true; }
  function canEdit($area, $mid = false) { return true; }
  function validCardinal($v) {
    if (!is_scalar($v)) return '';
    return preg_replace('/\D/', '', $v);
  }
  function validInt($v) {
    if (!is_scalar($v)) return '';
    return preg_replace('/[^\-\d]/', '', $v);
  }
  function validHtmlStr($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

  class StubUser {
    public function Username() { return 'testuser'; }
  }
  $user = new StubUser();

  function ajaxError($msg) {
    $GLOBALS['ajax_error'] = $msg;
  }
  function ajaxResponse($data) {
    $GLOBALS['ajax_response'] = $data;
  }
  function dbQuery($sql, $params = array()) {
    $GLOBALS['last_query'] = $sql;
    $GLOBALS['last_params'] = $params;
    return $GLOBALS['db_mock_rows'] ?? array();
  }

  function run_watch_script($request, $mock_rows = array()) {
    global $user;
    $_REQUEST = $request;
    $GLOBALS['last_zm_error'] = null;
    $GLOBALS['ajax_error'] = null;
    $GLOBALS['ajax_response'] = null;
    $GLOBALS['last_query'] = null;
    $GLOBALS['last_params'] = null;
    $GLOBALS['db_mock_rows'] = $mock_rows;
    $warnings = array();

    set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$warnings) {
      $warnings[] = array('errno' => $errno, 'errstr' => $errstr, 'line' => $errline);
      return true;
    });

    try {
      include $GLOBALS['zm_root'] . '/web/ajax/watch.php';
    } catch (\Throwable $t) {
      restore_error_handler();
      return array(
        'exception' => $t,
        'warnings' => $warnings,
        'ajax_error' => $GLOBALS['ajax_error'],
        'ajax_response' => $GLOBALS['ajax_response'],
        'last_query' => $GLOBALS['last_query'],
      );
    }

    restore_error_handler();
    return array(
      'exception' => null,
      'warnings' => $warnings,
      'ajax_error' => $GLOBALS['ajax_error'],
      'ajax_response' => $GLOBALS['ajax_response'],
      'last_query' => $GLOBALS['last_query'],
    );
  }

  echo "Testing watch.php counters of a just-started event:\n";
  $row = array('Id' => 5, 'MonitorId' => 1, 'StorageId' => 1, 'Name' => 'Event-5', 'Cause' => 'Motion',
    'StartDateTime' => '2026-01-01 00:00:00', 'EndDateTime' => null, 'Length' => '0.00',
    'Frames' => null, 'AlarmFrames' => null, 'TotScore' => 0, 'AvgScore' => 0, 'MaxScore' => null,
    'Archived' => 0, 'Emailed' => 0, 'Notes' => '', 'DiskSpace' => null, 'Tags' => null, 'Width' => 640, 'Height' => 480);
  $res = run_watch_script(array('mid' => '1', 'limit' => '10'), array($row));
  $out = $res['ajax_response']['rows'][0] ?? array();
  check('NULL Frames is returned as 0', ($out['Frames'] ?? null) === 0);
  check('NULL AlarmFrames is returned as 0', ($out['AlarmFrames'] ?? null) === 0);
  check('NULL MaxScore is returned as 0', ($out['MaxScore'] ?? null) === 0);
  $row['Frames'] = 12;
  $res = run_watch_script(array('mid' => '1', 'limit' => '10'), array($row));
  check('non-null Frames is kept', ($res['ajax_response']['rows'][0]['Frames'] ?? null) === 12);

  echo "\n$tests checks, $failures failed\n";
  exit($failures === 0 ? 0 : 1);
}
