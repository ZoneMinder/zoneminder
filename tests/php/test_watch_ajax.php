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
      include dirname(__DIR__, 2) . '/web/ajax/watch.php';
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

  echo "Testing web/ajax/watch.php:\n";

  // Test 1: Missing limit should not trigger PHP warnings or deprecations
  $res1 = run_watch_script(array('mid' => '1'));
  check('missing limit produces no PHP warnings', empty($res1['warnings']));
  check('missing limit reports error cleanly', $res1['ajax_error'] !== null);

  // Test 2: Array limit should not trigger deprecations or Array to string conversion
  $res2 = run_watch_script(array('mid' => '1', 'limit' => array(10)));
  check('array limit produces no PHP warnings', empty($res2['warnings']));
  check('array limit reports error cleanly', $res2['ajax_error'] !== null);

  // Test 3: Array order parameter should not throw TypeError
  $res3 = run_watch_script(array('mid' => '1', 'limit' => '10', 'order' => array('asc')));
  check('array order does not throw TypeError', $res3['exception'] === null);

  // Test 4: Array sort parameter should not trigger Array to string conversion
  $res4 = run_watch_script(array('mid' => '1', 'limit' => '10', 'sort' => array('Id')));
  check('array sort produces no PHP warnings', empty($res4['warnings']));

  // Test 5: Invalid mid (e.g. 'abc' or array) should not execute malformed query
  $res5 = run_watch_script(array('mid' => 'abc', 'limit' => '10'));
  check('invalid mid does not execute query with WHERE MonitorId =',
    $res5['last_query'] === null && $res5['ajax_error'] === 'Must specify a monitor Id');

  $res5b = run_watch_script(array('mid' => array('1'), 'limit' => '10'));
  check('array mid does not execute query with WHERE MonitorId =',
    $res5b['last_query'] === null && $res5b['ajax_error'] === 'Must specify a monitor Id');

  // Test 6: Zero width event should not throw DivisionByZeroError
  $zero_width_row = array(
    'Id' => 1,
    'MonitorId' => 1,
    'StorageId' => 1,
    'Name' => 'Event-1',
    'Cause' => 'Motion',
    'StartDateTime' => '2026-01-01 00:00:00',
    'EndDateTime' => '2026-01-01 00:01:00',
    'Length' => 60,
    'Frames' => 100,
    'AlarmFrames' => 10,
    'TotScore' => 10,
    'AvgScore' => 10,
    'MaxScore' => 10,
    'Archived' => 0,
    'Emailed' => 0,
    'Notes' => '',
    'DiskSpace' => 1000,
    'Tags' => null,
    'Width' => 0,
    'Height' => 0,
  );
  $res6 = run_watch_script(array('mid' => '1', 'limit' => '10'), array($zero_width_row));
  check('zero width event does not throw DivisionByZeroError', $res6['exception'] === null);
  check('zero width event returns processed row',
    isset($res6['ajax_response']['rows']) && count($res6['ajax_response']['rows']) === 1);

  // Test 7: events.php handling of non-array filter request
  echo "\nTesting web/skins/classic/views/events.php:\n";
  $src_events = file_get_contents(dirname(__DIR__, 2) . '/web/skins/classic/views/events.php');
  check('events.php guards filter with is_array before calling set()',
    preg_match('/isset\(\$_REQUEST\[.filter.\]\)\s*&&\s*is_array\(\$_REQUEST\[.filter.\]\)/', $src_events) === 1);

  echo "\n$tests checks, $failures failed\n";
  exit($failures === 0 ? 0 : 1);
}
