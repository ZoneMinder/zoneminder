<?php
// The console drag-sort posts monitor_ids[] and saveMonitorSequence() counted and indexed it. A request
// with monitor_ids missing or given as a plain string was a TypeError in count() on PHP 8, and an
// entry that is itself an array reached ctype_digit() with an array. Those requests must be refused or
// skipped without a PHP error, and a normal list must still be saved.
//
// Run as: php tests/php/test_console_sort_nonlist.php [repo_root]

namespace ZM {
  function Warning($m) {}
}

namespace {
  $failures = 0;
  function check($name, $got, $want) {
    global $failures;
    if ($got === $want) {
      echo "ok - $name\n";
    } else {
      $failures++;
      echo "FAIL - $name\n  got:  ".var_export($got, true)."\n  want: ".var_export($want, true)."\n";
    }
  }

  $log = array();
  $updates = array();
  $phpErrors = array();
  set_error_handler(function($no, $str) use (&$phpErrors) { $phpErrors[] = $str; return true; });

  class FakeConn {
    public function exec($sql) { $GLOBALS['log'][] = $sql; return 0; }
  }
  function zmDbConn() { return new FakeConn(); }
  function dbFetchAll($sql, $col = null, $params = array()) { return array(); }
  function dbQuery($sql, $params = null) { $GLOBALS['updates'][] = $params; return true; }
  function editableMonitor($mid) { return true; }

  $root = $argv[1] ?? __DIR__.'/../..';
  $path = $root.'/web/ajax/console.php';
  $src = file_get_contents($path);
  if (!preg_match('/^function saveMonitorSequence\(.*?\n\}/ms', $src, $m)) {
    echo "Could not find saveMonitorSequence() in $path\n";
    exit(1);
  }
  eval($m[0]);

  function attempt($ids) {
    $GLOBALS['log'] = array();
    $GLOBALS['updates'] = array();
    $GLOBALS['phpErrors'] = array();
    try {
      return saveMonitorSequence($ids);
    } catch (Throwable $e) {
      return get_class($e).': '.$e->getMessage();
    }
  }

  check('a string is refused', attempt('5'), false);
  check('a string does not lock the table', $log, array());
  check('a missing list is refused', attempt(null), false);

  check('an entry that is an array is skipped, the rest saved', attempt(array('4', array('x'), 'monitor_id-2')), true);
  check('only the real ids were updated', array_column($updates, 1), array('4', '2'));
  check('no PHP errors for an array entry', $phpErrors, array());
  check('table unlocked', end($log), 'UNLOCK TABLES');

  check('a normal list is saved', attempt(array('3', 'monitor_id-2')), true);
  check('both ids updated', array_column($updates, 1), array('3', '2'));

  exit($failures ? 1 : 0);
}
