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
  function dbQuery($sql, $params = null) {
    global $updates, $failNext;
    if ($failNext) throw new \RuntimeException('update failed');
    $updates[] = $params;
    return true;
  }
  function editableMonitor($mid) { return true; }

  $path = __DIR__.'/../../web/ajax/console.php';
  $src = file_get_contents($path);
  if (!preg_match('/^function saveMonitorSequence\(.*?\n\}/ms', $src, $m)) {
    echo "Could not find saveMonitorSequence() in $path\n";
    exit(1);
  }
  eval($m[0]);

  echo "ordered ids\n";
  $sequenceOf = array('2' => 0, '3' => 1, '4' => 2);
  saveMonitorSequence(array('4', 'monitor_id-3', '2'));
  check('sequence follows position', $updates, array(array(0, '4'), array(1, '3'), array(2, '2')));
  check('table locked then unlocked', $conn->log, array('LOCK TABLES Monitors WRITE', 'UNLOCK TABLES'));

  echo "\nsecond page keeps its own slots\n";
  $updates = array();
  $conn->log = array();
  $sequenceOf = array('11' => 10, '12' => 11, '13' => 12);
  saveMonitorSequence(array('13', '11', '12'));
  check('reordered within the page slots', $updates, array(array(10, '13'), array(11, '11'), array(12, '12')));

  echo "\nduplicate sequences fall back to a run\n";
  $updates = array();
  $sequenceOf = array('5' => 3, '6' => 3);
  saveMonitorSequence(array('6', '5'));
  check('consecutive from the lowest', $updates, array(array(3, '6'), array(4, '5')));

  echo "\nbad ids are skipped\n";
  $updates = array();
  $conn->log = array();
  $sequenceOf = array('7' => 2);
  saveMonitorSequence(array('', 'x', '7'));
  check('only the valid id is written', $updates, array(array(2, '7')));
  check('still unlocked', end($conn->log), 'UNLOCK TABLES');

  echo "\nunlocks when a write throws\n";
  $conn->log = array();
  $failNext = true;
  $sequenceOf = array('1' => 0);
  try {
    saveMonitorSequence(array('1'));
  } catch (\RuntimeException $e) {
  }
  check('unlocked after the exception', end($conn->log), 'UNLOCK TABLES');

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
