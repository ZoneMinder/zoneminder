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
  $sequenceOf = array();
  function dbFetchAll($sql, $col = null, $params = array()) {
    global $sequenceOf;
    $seqs = array();
    foreach ($params as $id) if (isset($sequenceOf[$id])) $seqs[] = $sequenceOf[$id];
    sort($seqs);
    return $seqs;
  }
  function dbQuery($sql, $params = null) {
    global $updates;
    $updates[] = $params;
    return true;
  }
  function editableMonitor($mid) { return $mid != 2; }

  $root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
  $path = rtrim($root, '/').'/web/ajax/console.php';
  $src = file_get_contents($path);
  if (!preg_match('/^function saveMonitorSequence\(.*?\n\}/ms', $src, $m)) {
    echo "Could not find saveMonitorSequence() in $path\n";
    exit(1);
  }
  eval($m[0]);

  function updatedIds() {
    global $updates;
    $ids = array();
    foreach ($updates as $params) $ids[] = (string)$params[1];
    return $ids;
  }

  echo "view-only monitors are skipped\n";
  $updates = array();
  $conn->log = array();
  $sequenceOf = array('1' => 10, '2' => 20, '3' => 30);
  saveMonitorSequence(array('monitor_id-1', 'monitor_id-2', 'monitor_id-3'));
  check('monitor 2 is never written', in_array('2', updatedIds(), true), false);
  check('editable monitors 1 and 3 are still written', updatedIds(), array('1', '3'));

  echo "\nnothing is written when no monitor is editable\n";
  $updates = array();
  $conn->log = array();
  $sequenceOf = array('2' => 20);
  saveMonitorSequence(array('monitor_id-2', '2'));
  check('no update runs', $updates, array());

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
