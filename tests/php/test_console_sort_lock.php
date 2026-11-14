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
  function dbQuery($sql, $params = null) {
    global $updates, $failNext;
    if ($failNext) throw new \RuntimeException('update failed');
    $updates[] = $params;
  }

  $path = __DIR__.'/../../web/ajax/console.php';
  $src = file_get_contents($path);
  if (!preg_match('/^function saveMonitorSequence\(.*?\n\}/ms', $src, $m)) {
    echo "Could not find saveMonitorSequence() in $path\n";
    exit(1);
  }
  eval($m[0]);

  echo "ordered ids\n";
  saveMonitorSequence(array('4', 'monitor_id-3', '2'));
  check('sequence follows position', $updates, array(array(0, '4'), array(1, '3'), array(2, '2')));
  check('table locked then unlocked', $conn->log, array('LOCK TABLES Monitors WRITE', 'UNLOCK TABLES'));

  echo "\nbad ids are skipped\n";
  $updates = array();
  $conn->log = array();
  saveMonitorSequence(array('', 'x', '7'));
  check('only the valid id is written', $updates, array(array(2, '7')));
  check('still unlocked', end($conn->log), 'UNLOCK TABLES');

  echo "\nunlocks when a write throws\n";
  $conn->log = array();
  $failNext = true;
  try {
    saveMonitorSequence(array('1'));
  } catch (\RuntimeException $e) {
  }
  check('unlocked after the exception', end($conn->log), 'UNLOCK TABLES');

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
