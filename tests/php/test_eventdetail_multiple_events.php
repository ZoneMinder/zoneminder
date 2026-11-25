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
  class StubConn { public function beginTransaction() {} public function commit() {} }
  function canEdit($area) { return true; }
  function validCardinal($v) { return preg_match('/^\d+$/', (string)$v) ? (int)$v : ''; }
  function validHtmlStr($s) { return htmlspecialchars((string)$s); }
  function localRedirectUrl($url, $fallback) { return $fallback; }
  function dbQuery($sql, $params = array()) { $GLOBALS['queries'][] = array($sql, $params); return true; }

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

  function save($eids, $newEvent) {
    $GLOBALS['queries'] = array();
    $_REQUEST = array('markEids' => $eids, 'newEvent' => $newEvent);
    $_SERVER['HTTP_REFERER'] = '';
    $action = 'eventdetail';
    $dbConn = new StubConn();
    include __DIR__.'/../../web/includes/actions/eventdetail.php';
    return $GLOBALS['queries'];
  }

  check('one event: every field is written, blanks included',
    save(array('1'), array('Name' => 'a', 'Cause' => '', 'Notes' => '')),
    array(array('UPDATE Events SET Name=?, Cause=?, Notes=? WHERE Id=?', array('a', '', '', 1))));

  check('several events: a blank field is left alone',
    save(array('1', '2'), array('Name' => '', 'Cause' => '', 'Notes' => 'shared')),
    array(
      array('UPDATE Events SET Notes=? WHERE Id=?', array('shared', 1)),
      array('UPDATE Events SET Notes=? WHERE Id=?', array('shared', 2)),
    ));

  check('several events: all blank writes nothing',
    save(array('1', '2'), array('Name' => '', 'Cause' => '', 'Notes' => '')), array());

  echo "$passed passed, $failed failed\n";
  exit($failed ? 1 : 0);
}
