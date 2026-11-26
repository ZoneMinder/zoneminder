<?php

namespace ZM {
  function Warning($m) {}
  function Error($m) {}
  function AuditAction() {}
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

  $queries = array();
  $packaged = array();
  function validHtmlStr($s) { return htmlspecialchars($s); }
  function canEdit($p) { return true; }
  function getHomeView() { return 'console'; }
  function packageControl($c) {
    global $packaged;
    if (!is_string($c)) throw new TypeError('escapeshellarg(): Argument #1 must be of type string, array given');
    $packaged[] = $c;
  }
  function dbFetchAll($sql) { return array(); }
  function dbQuery($sql, $params = null) {
    global $queries;
    foreach ((array)$params as $p) {
      if (!is_scalar($p)) throw new Error('Array to string conversion in bound parameter');
    }
    $queries[] = array($sql, $params);
  }

  $cases = array(
    array('state', array('runState'=>array('a', 'b'))),
    array('save', array('runState'=>array('a', 'b'))),
    array('save', array('newState'=>array('a', 'b'))),
    array('delete', array('runState'=>array('a', 'b'))),
  );
  foreach ($cases as $c) {
    $queries = array();
    $packaged = array();
    $action = $c[0];
    $_REQUEST = $c[1];
    $threw = false;
    try {
      include __DIR__.'/../../web/includes/actions/state.php';
    } catch (Throwable $e) {
      $threw = true;
    }
    check("$action with array param does not throw", $threw, false);
    check("$action with array param changes nothing", count($queries) + count($packaged), 0);
  }

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
