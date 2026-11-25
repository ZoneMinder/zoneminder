<?php
namespace ZM {
  function Warning($s) {}
  class Event {
    private $id;
    public function __construct($id) { $this->id = $id; }
    public function Id() { return $this->id; }
    public function canEdit() { return true; }
  }
}

namespace {
  $root = $argv[1] ?? __DIR__.'/../..';

  class StubConn {
    public $committed = false;
    public $rolledBack = false;
    public function beginTransaction() {}
    public function commit() { $this->committed = true; }
    public function rollBack() { $this->rolledBack = true; }
  }

  function canEdit($area) { return true; }
  function validCardinal($v) { return preg_match('/^\d+$/', (string)$v) ? (int)$v : ''; }
  function validHtmlStr($s) { return htmlspecialchars((string)$s); }
  function localRedirectUrl($url, $fallback) { return $fallback; }

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

  function run_case($fail_eids, $label_prefix) {
    global $error_message, $redirect, $dbConn, $root;
    $call_count = 0;
    $GLOBALS['fail_eids'] = $fail_eids;
    $dbConn = new StubConn();
    $_REQUEST = array('markEids' => array(5, 6), 'newEvent' => array('Name' => 'n', 'Cause' => 'c', 'Notes' => ''));
    $_SERVER['HTTP_REFERER'] = '';
    $action = 'eventdetail';
    $error_message = '';
    $redirect = null;
    include $root.'/web/includes/actions/eventdetail.php';
    $results = array(
      'error_message' => $error_message,
      'redirect' => $redirect,
      'committed' => $dbConn->committed,
      'rolledBack' => $dbConn->rolledBack,
    );
    return $results;
  }

  function dbQuery($sql, $params = array()) {
    $eid = end($params);
    return !in_array($eid, $GLOBALS['fail_eids'], true);
  }

  // Case a: dbQuery returns false for event 6, true for 5
  $r = run_case(array(6), 'partial failure');
  check('a) error_message contains failed id 6', strpos($r['error_message'], '6') !== false, true);
  check('a) redirect is not set', $r['redirect'], null);
  check('a) rollBack called', $r['rolledBack'], true);
  check('a) commit not called', $r['committed'], false);

  // Case b: dbQuery always returns true
  $r = run_case(array(), 'all success');
  check('b) error_message is empty', $r['error_message'], '');
  check('b) redirect is set', $r['redirect'], '?view=events');
  check('b) commit called', $r['committed'], true);
  check('b) rollBack not called', $r['rolledBack'], false);

  echo "$passed passed, $failed failed\n";
  exit($failed ? 1 : 0);
}