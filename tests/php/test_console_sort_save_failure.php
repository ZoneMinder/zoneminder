<?php

namespace ZM {
  function Warning($m) {}
}

namespace {
  $failures = 0;
  $passes = 0;

  function check($name, $got, $want) {
    global $failures, $passes;
    if ($got === $want) {
      $passes++;
      echo "  ok $name\n";
    } else {
      $failures++;
      echo "  FAIL $name\n";
      echo '    got:  '.var_export($got, true)."\n";
      echo '    want: '.var_export($want, true)."\n";
    }
  }

  class FakeConn {
    public $log = array();
    public $inTransaction = false;
    public function beginTransaction() { $this->inTransaction = true; return true; }
    public function commit() {
      if (!$this->inTransaction) throw new \PDOException('There is no active transaction');
      $this->inTransaction = false;
      return true;
    }
    public function exec($sql) {
      $this->log[] = $sql;
      if (strpos($sql, 'LOCK TABLES') === 0) $this->inTransaction = false;
      return 0;
    }
  }

  $conn = new FakeConn();
  $updates = array();
  function zmDbConn() { global $conn; return $conn; }
  $failNext = false;
  $sequenceOf = array();
  function dbFetchAll($sql, $col = null, $params = array()) {
    global $sequenceOf;
    $seqs = array();
    foreach ($params as $id) if (isset($sequenceOf[$id])) $seqs[] = $sequenceOf[$id];
    sort($seqs);
    return $seqs;
  }
  function editableMonitor($mid) { return true; }
  function dbQuery($sql, $params = null) {
    global $updates, $failNext;
    $updates[] = $params;
    if ($failNext) return null;
    return true;
  }

  $root = isset($argv[1]) ? $argv[1] : dirname(dirname(__DIR__));
  $path = $root.'/web/ajax/console.php';
  $src = file_get_contents($path);
  if (!preg_match('/^function saveMonitorSequence\(.*?\n\}/ms', $src, $m)) {
    echo "Could not find saveMonitorSequence() in $path\n";
    exit(1);
  }
  eval($m[0]);

  echo "all updates succeed\n";
  $sequenceOf = array('2' => 0, '3' => 1, '4' => 2);
  $result = saveMonitorSequence(array('4', 'monitor_id-3', '2'));
  check('returns true', $result, true);
  check('every row updated', $updates, array(array(0, '4'), array(1, '3'), array(2, '2')));
  check('table unlocked', end($conn->log), 'UNLOCK TABLES');

  echo "\nan update returns NULL\n";
  $updates = array();
  $conn->log = array();
  $failNext = true;
  $sequenceOf = array('2' => 0, '3' => 1, '4' => 2);
  $result = saveMonitorSequence(array('4', '3', '2'));
  check('returns false', $result, false);
  check('stops at the failed update', count($updates), 1);
  check('table still unlocked', end($conn->log), 'UNLOCK TABLES');

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
