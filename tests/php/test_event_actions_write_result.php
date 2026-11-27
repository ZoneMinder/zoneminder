<?php
// The single-event (web/includes/actions/event.php) and multi-event
// (web/includes/actions/events.php) actions used to ignore the result of their
// writes: a failed UPDATE still audited, refreshed the parent and closed the
// popup, and a failed delete was audited as done.
//
// Includes the real action files from a repo root given as argv[1] (default
// __DIR__.'/../..') with the web helpers stubbed, so it can be pointed at a
// copy of the original files to show it fails there.
//
// Run as: php tests/php/test_event_actions_write_result.php [root]

namespace ZM {
  function Warning($s) { $GLOBALS['warnings'][] = $s; }
  function Debug($s) {}
  function AuditAction($action, $type, $id, $message) {
    $GLOBALS['audits'][] = array($action, $type, $id, $message);
  }
  class Event {
    private $id;
    public function __construct($id) { $this->id = $id; }
    public function Id() { return $GLOBALS['event_exists'] ? $this->id : null; }
    public function canEdit() { return true; }
  }
}

namespace {
  $root = $argv[1] ?? __DIR__.'/../..';

  $GLOBALS['event_exists'] = true;
  $GLOBALS['db_ok'] = true;
  $GLOBALS['delete_fail_eids'] = array();
  $GLOBALS['affected_eids'] = array();
  $GLOBALS['audits'] = array();
  $GLOBALS['warnings'] = array();

  function canEdit($area) { return true; }
  function validCardinal($v) { return preg_match('/^\d+$/', (string)$v) ? (int)$v : ''; }
  // dbQuery() returns NULL when the write failed, as tests/php/test_events_delete_audit_count.php's caller expects.
  function dbQuery($sql, $args = array()) { return $GLOBALS['db_ok'] ? true : null; }
  function dbLastError() { return 'bad <b>x</b>'; }
  function deleteEvent($eid) { return !in_array($eid, $GLOBALS['delete_fail_eids'], true); }
  function getAffectedIds($key) { return $GLOBALS['affected_eids']; }

  class StubConn {
    public $begun = 0;
    public $commits = 0;
    public $rollbacks = 0;
    public function beginTransaction() { $this->begun++; }
    public function commit() { $this->commits++; }
    public function rollBack() { $this->rollbacks++; }
  }

  $passed = 0;
  $failed = 0;
  function check($label, $got, $want) {
    global $passed, $failed;
    if ($got === $want) {
      $passed++;
      echo "  ok $label\n";
    } else {
      $failed++;
      echo "  FAIL $label\n";
      echo "       got:  ".var_export($got, true)."\n";
      echo "       want: ".var_export($want, true)."\n";
    }
  }

  function reset_state($db_ok, array $delete_fail = array(), array $affected = array()) {
    $GLOBALS['db_ok'] = $db_ok;
    $GLOBALS['event_exists'] = true;
    $GLOBALS['delete_fail_eids'] = $delete_fail;
    $GLOBALS['affected_eids'] = $affected;
    $GLOBALS['audits'] = array();
    $GLOBALS['warnings'] = array();
  }

  // Includes actions/event.php; $action is in scope for the file, as the web dispatcher has it.
  function run_event_action($action, array $extra, $db_ok, array $delete_fail = array()) {
    global $root, $error_message, $refreshParent, $closePopup;
    reset_state($db_ok, $delete_fail);
    $_REQUEST = array_merge(array('eid' => '7'), $extra, array('action' => $action));
    $error_message = '';
    $refreshParent = null;
    $closePopup = null;
    include $root.'/web/includes/actions/event.php';
    return array(
      'error' => $error_message,
      'refreshParent' => $refreshParent,
      'closePopup' => $closePopup,
      'audits' => $GLOBALS['audits'],
    );
  }

  // Includes actions/events.php; $action and $dbConn are in scope for the file.
  function run_events_action($action, $db_ok, array $delete_fail = array(), array $affected = array()) {
    global $root, $error_message, $refreshParent, $dbConn;
    reset_state($db_ok, $delete_fail, $affected);
    $_REQUEST = array('eids' => $affected, 'action' => $action);
    $error_message = '';
    $refreshParent = null;
    $dbConn = new StubConn();
    include $root.'/web/includes/actions/events.php';
    return array(
      'error' => $error_message,
      'refreshParent' => $refreshParent,
      'commits' => $dbConn->commits,
      'rollbacks' => $dbConn->rollbacks,
      'audits' => $GLOBALS['audits'],
    );
  }

  echo "actions/event.php\n";

  $single_edits = array(
    'rename' => array('eventName' => 'new name'),
    'eventdetail' => array('newEvent' => array('Cause' => 'c', 'Notes' => 'n')),
    'archive' => array(),
    'unarchive' => array(),
  );

  foreach ($single_edits as $action => $extra) {
    $r = run_event_action($action, $extra, false);
    check("$action failure reports an error", strpos($r['error'], 'Error') !== false, true);
    check("$action failure escapes dbLastError", strpos($r['error'], '&lt;b&gt;') !== false, true);
    check("$action failure audits nothing", $r['audits'], array());
    check("$action failure does not refresh the parent", $r['refreshParent'] === true, false);
    check("$action failure does not close the popup", $r['closePopup'] === true, false);
  }

  $r = run_event_action('rename', $single_edits['rename'], true);
  check('rename success leaves no error', $r['error'], '');
  check('rename success audits once', count($r['audits']), 1);
  check('rename success audit action', $r['audits'] ? $r['audits'][0][0] : null, 'rename');

  $r = run_event_action('eventdetail', $single_edits['eventdetail'], true);
  check('eventdetail success leaves no error', $r['error'], '');
  check('eventdetail success audits once', count($r['audits']), 1);
  check('eventdetail success refreshes the parent', $r['refreshParent'], true);
  check('eventdetail success closes the popup', $r['closePopup'], true);

  foreach (array('archive', 'unarchive') as $action) {
    $r = run_event_action($action, array(), true);
    check("$action success leaves no error", $r['error'], '');
    check("$action success audits nothing", $r['audits'], array());
  }

  $r = run_event_action('delete', array(), true, array(7));
  check('delete failure reports an error', strpos($r['error'], 'Error deleting event') !== false, true);
  check('delete failure audits nothing', $r['audits'], array());
  check('delete failure does not refresh the parent', $r['refreshParent'] === true, false);

  $r = run_event_action('delete', array(), true, array());
  check('delete success audits once', count($r['audits']), 1);
  check('delete success refreshes the parent', $r['refreshParent'], true);

  echo "actions/events.php\n";

  foreach (array('archive', 'unarchive') as $action) {
    $r = run_events_action($action, false, array(), array(1, 2));
    check("$action failure rolls back", $r['rollbacks'], 1);
    check("$action failure does not commit", $r['commits'], 0);
    check("$action failure reports an error", strpos($r['error'], 'Error updating events') !== false, true);
    check("$action failure does not refresh the parent", $r['refreshParent'] === true, false);

    $r = run_events_action($action, true, array(), array(1, 2));
    check("$action success commits", $r['commits'], 1);
    check("$action success leaves no error", $r['error'], '');
    check("$action success refreshes the parent", $r['refreshParent'], true);
  }

  $r = run_events_action('delete', true, array(2), array(1, 2, 3));
  check('delete partial failure reports an error', strpos($r['error'], 'Could not delete 1 of 3') !== false, true);

  echo "\n$passed passed, $failed failed\n";
  exit($failed ? 1 : 0);
}
