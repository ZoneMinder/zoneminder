<?php
// Options > Servers delete (and the API delete) must refuse a server that
// monitors, storage areas or cron jobs still reference, since ServerId has no
// foreign key and would be left dangling.
//
// Run as: php tests/php/test_server_delete_in_use.php [repo root]

namespace {
  $root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
  $GLOBALS['root'] = $root;
  set_include_path($root.'/web'.PATH_SEPARATOR.get_include_path());
}

namespace ZM {
  function Warning($s) {}
  function AuditAction($a, $o, $id, $m) { $GLOBALS['audits'][] = $a.':'.$id; }

  // Server::InUseSummary is taken from the real source so its SQL and wording are exercised.
  $src = file_get_contents($GLOBALS['root'].'/web/includes/Server.php');
  if (preg_match('/public static function InUseSummary\(.*?\n  }\n/s', $src, $m)) {
    eval('namespace ZM; class Server { '.$m[0].' }');
  } else {
    class Server {
      public static function InUseSummary($id) { return ''; }
    }
  }
}

namespace {
  function canEdit($area) { return true; }
  function validCardinal($v) { return ctype_digit((string)$v) ? (int)$v : 0; }
  function validHtmlStr($s) { return htmlspecialchars($s); }
  function dbLastError() { return ''; }
  function dbQuery($sql, $params = array()) { $GLOBALS['queries'][] = array($sql, $params); return true; }
  // Server 3 has 2 monitors and a storage area, 4 only a cron job, 2 nothing.
  function dbFetchOne($sql, $col = false, $params = null) {
    $id = $params[0];
    $counts = array(3 => array(2, 1, 0), 4 => array(0, 0, 1));
    $c = isset($counts[$id]) ? $counts[$id] : array(0, 0, 0);
    return array('Monitors' => $c[0], 'Storage' => $c[1], 'CronJobs' => $c[2]);
  }

  $failures = 0;
  function check($name, $got, $want) {
    global $failures;
    if ($got === $want) {
      echo "ok - $name\n";
    } else {
      $failures++;
      echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n";
    }
  }

  $GLOBALS['queries'] = array();
  $GLOBALS['audits'] = array();
  $error_message = '';
  $action = 'delete';
  $_REQUEST = array('object' => 'server', 'markIds' => array('2', '3', '4'));
  include $root.'/web/includes/actions/options.php';

  $deleted = array_map(function($q) { return $q[1][0]; }, $GLOBALS['queries']);
  check('only the unused server is deleted', $deleted, array(2));
  check('only the deleted server is audited', $GLOBALS['audits'], array('delete:2'));
  check('refusal counts monitors and storage', strpos($error_message, '2 monitors, 1 storage area') !== false, true);
  check('refusal counts cron jobs', strpos($error_message, '1 cron job') !== false, true);

  $api = file_get_contents($root.'/web/api/app/Controller/ServersController.php');
  check('API delete checks the server is unused', strpos($api, 'InUseSummary') !== false, true);

  exit($failures ? 1 : 0);
}
