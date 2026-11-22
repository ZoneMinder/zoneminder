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
  $selects = array();
  function validHtmlStr($s) { return htmlspecialchars($s); }
  function canEdit($p) { return true; }
  function getHomeView() { return 'console'; }
  function packageControl($c) {}
  function dbFetchAll($sql) {
    global $selects;
    $selects[] = $sql;
    return array(array('Id'=>1, 'Capturing'=>'Always', 'Analysing'=>'Always', 'Recording'=>'OnMotion'));
  }
  function dbQuery($sql, $params = null) {
    global $queries;
    $queries[] = array($sql, $params);
  }

  $action = 'save';
  $_REQUEST = array('runState'=>'night');
  include __DIR__.'/../../web/includes/actions/state.php';

  echo "save over an existing state\n";
  check('one statement', count($queries), 1);
  check('does not use REPLACE', stripos($queries[0][0], 'REPLACE') === false, true);
  check('updates on duplicate name', stripos($queries[0][0], 'ON DUPLICATE KEY UPDATE') !== false, true);
  check('does not touch IsActive', stripos($queries[0][0], 'IsActive') === false, true);
  check('binds name and definition', $queries[0][1], array('night', '1:Always:Always:OnMotion'));
  check('leaves out deleted monitors', stripos($selects[0], 'Deleted') !== false, true);

  echo "\nnames zmpkg treats as commands are refused\n";
  foreach (array('start', 'stop', 'restart', 'status', 'version', 'logrot') as $name) {
    $queries = array();
    $error_message = '';
    $_REQUEST = array('newState'=>$name);
    include __DIR__.'/../../web/includes/actions/state.php';
    check("$name not saved", count($queries), 0);
  }

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
