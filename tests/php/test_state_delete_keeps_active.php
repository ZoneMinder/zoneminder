<?php

namespace ZM {
  function Warning($m) {}
  function Error($m) {}
  function AuditAction() {}
}

namespace {
  $root = $argv[1] ?? __DIR__.'/../..';
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

  // Tiny in-memory States table: name => IsActive
  $states = array('default'=>0, 'night'=>1);
  function validHtmlStr($s) { return htmlspecialchars($s); }
  function canEdit($p) { return true; }
  function getHomeView() { return 'console'; }
  function packageControl($c) {}
  function dbFetchAll($sql) { return array(); }
  function dbQuery($sql, $params = null) {
    global $states;
    if (stripos($sql, 'DELETE FROM `States`') === 0) {
      unset($states[$params[0]]);
    } else if (stripos($sql, 'UPDATE `States` SET `IsActive`=1 WHERE `Name`=\'default\'') === 0) {
      if (isset($states['default']) and !in_array(1, $states, true)) $states['default'] = 1;
    }
  }

  $action = 'delete';
  $_REQUEST = array('runState'=>'night');
  include $root.'/web/includes/actions/state.php';
  echo "deleting the active state\n";
  check('night removed', isset($states['night']), false);
  check('default becomes active', $states['default'], 1);

  $states = array('default'=>1, 'night'=>0);
  $_REQUEST = array('runState'=>'night');
  include $root.'/web/includes/actions/state.php';
  echo "deleting an inactive state\n";
  check('default stays active', $states['default'], 1);

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
