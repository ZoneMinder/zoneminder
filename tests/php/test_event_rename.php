<?php
// Regression test for ajax/event.php case 'rename':
//   * empty('0') is true, so the valid name "0" used to be rejected
//   * Events.Name is varchar(64); an over-long name used to make the UPDATE
//     fail under strict SQL mode while the action still answered success
//   * dbQuery's return value is checked now, so a failed UPDATE is an error
//
// Includes the real web/ajax/event.php with the web helpers stubbed, and
// records the UPDATE it sends (or the error it returns).
//
// Set ZM_EVENT_AJAX to point at an alternate copy of ajax/event.php (used to
// show the test fails against the original file).
//
// Run as: php tests/php/test_event_rename.php

namespace ZM {
  function Warning($s) {}
  function Debug($s) {}
  class Monitor {
    public function DefaultRate() { return 100; }
    public function DefaultScale() { return 100; }
  }
  class Event {
    private $row = null;
    public function __construct($id) { if (is_array($id)) $this->row = $id; }
    public function Id() { return $GLOBALS['event_exists'] ? 7 : null; }
    public function canEdit() { return level('Events') === 'Edit' and $GLOBALS['monitor_visible']; }
    public function MonitorId() { return 1; }
    public function Monitor() { return new Monitor(); }
  }
}

namespace {
  class StopRequest extends Exception {
    public $kind;   // 'response' or 'error'
    public $data;
    public function __construct($kind, $data = null) { $this->kind = $kind; $this->data = $data; }
  }

  function level($area) { return $GLOBALS['perms'][$area] ?? 'None'; }
  function canView($area, $mid = false) { return in_array(level($area), array('View', 'Edit'), true); }
  function canEdit($area, $mid = false) { return level($area) === 'Edit'; }
  function visibleMonitor($mid) { return $GLOBALS['monitor_visible']; }
  function validCardinal($v) { return preg_match('/^\d+$/', (string)$v) ? (int)$v : ''; }
  function ajaxResponse($data = array()) { throw new StopRequest('response', $data); }
  function ajaxError($message, $code = 200) { throw new StopRequest('error', $message); }

  // Set by the test: return value of dbQuery, and whether it was called.
  function dbQuery($sql, $params = NULL, $debug = false) {
    $GLOBALS['queries'][] = array('sql' => $sql, 'params' => $params);
    return $GLOBALS['dbquery_result'];
  }

  $stubdir = sys_get_temp_dir().'/zm_event_rename_stubs_'.getmypid();
  @mkdir($stubdir.'/includes', 0700, true);
  file_put_contents($stubdir.'/includes/Event.php', "<?php\n");
  set_include_path($stubdir.PATH_SEPARATOR.get_include_path());

  $GLOBALS['perms'] = array('Events' => 'Edit', 'Monitors' => 'View');
  $GLOBALS['event_exists'] = true;
  $GLOBALS['monitor_visible'] = true;
  $GLOBALS['queries'] = array();
  $GLOBALS['dbquery_result'] = true;

  $event_file = getenv('ZM_EVENT_AJAX') ?: __DIR__.'/../../web/ajax/event.php';

  // Returns array(outcome, queries): outcome is 'response' or 'error:<msg>'.
  function run($eventName, $dbquery_result = true) {
    $GLOBALS['queries'] = array();
    $GLOBALS['dbquery_result'] = $dbquery_result;
    $_REQUEST = array('request' => 'event', 'action' => 'rename', 'id' => '7', 'eventName' => $eventName);
    $outcome = 'fell through';
    try {
      include $GLOBALS['event_file'];
    } catch (StopRequest $e) {
      $outcome = ($e->kind === 'error') ? 'error:'.$e->data : 'response';
    } catch (Throwable $e) {
      $outcome = 'threw: '.$e->getMessage();
    }
    return array($outcome, $GLOBALS['queries']);
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

  // The single UPDATE the rename case should send.
  $expected_sql = 'UPDATE Events SET Name = ? WHERE Id = ?';
  function rename_updates() {
    return array_values(array_filter($GLOBALS['queries'], function($q) {
      return strpos($q['sql'], 'UPDATE Events SET Name') !== false;
    }));
  }

  // eventName "0" -> UPDATE runs with "0", success response
  list($outcome, $q) = run('0');
  check('eventName "0" answers success', $outcome, 'response');
  check('eventName "0" runs one UPDATE', count(rename_updates()), 1);
  check('eventName "0" binds "0"',
    rename_updates() ? rename_updates()[0]['params'] : null, array('0', 7));

  // 65-character name -> ajaxError, no UPDATE
  list($outcome, $q) = run(str_repeat('a', 65));
  check('65-char name is an error', $outcome, 'error:Event name must be 64 characters or fewer');
  check('65-char name runs no UPDATE', count(rename_updates()), 0);

  // exactly 64 characters is allowed
  list($outcome, $q) = run(str_repeat('a', 64));
  check('64-char name answers success', $outcome, 'response');
  check('64-char name runs one UPDATE', count(rename_updates()), 1);

  // multibyte 64 characters (256 bytes) is allowed: varchar(64) counts characters
  list($outcome, $q) = run(str_repeat('é', 64));
  check('64 multibyte chars answer success', $outcome, 'response');
  check('64 multibyte chars run one UPDATE', count(rename_updates()), 1);
  // but 65 multibyte characters is not
  list($outcome, $q) = run(str_repeat('é', 65));
  check('65 multibyte chars are an error', $outcome, 'error:Event name must be 64 characters or fewer');
  check('65 multibyte chars run no UPDATE', count(rename_updates()), 0);

  // whitespace-only -> ajaxError, no UPDATE
  list($outcome, $q) = run('   ');
  check('whitespace-only name is an error', $outcome, 'error:No new event name supplied');
  check('whitespace-only name runs no UPDATE', count(rename_updates()), 0);

  // empty string -> ajaxError
  list($outcome, $q) = run('');
  check('empty name is an error', $outcome, 'error:No new event name supplied');
  check('empty name runs no UPDATE', count(rename_updates()), 0);

  // dbQuery returning false -> ajaxError
  list($outcome, $q) = run('n', false);
  check('failed UPDATE is an error', $outcome, 'error:Failed to rename event');

  // normal name -> UPDATE and success
  list($outcome, $q) = run('Normal Name');
  check('normal name answers success', $outcome, 'response');
  check('normal name runs one UPDATE', count(rename_updates()), 1);
  check('normal name binds the name',
    rename_updates() ? rename_updates()[0]['params'] : null, array('Normal Name', 7));

  @unlink($stubdir.'/includes/Event.php');
  @rmdir($stubdir.'/includes');
  @rmdir($stubdir);

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}