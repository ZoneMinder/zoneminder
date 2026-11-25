<?php
// Numeric options were only format-checked, so ZM_WATCH_CHECK_INTERVAL=0 made
// zmwatch.pl spin with sleep(0) on the database, and ZM_WATCH_MAX_DELAY=0 made
// it treat every capture daemon as stale and restart it on every pass. Both are
// seconds and must be at least 1.
//
// The real web/includes/actions/options.php is included with the database layer
// stubbed, because the only thing that matters is which UPDATEs it issues and
// what error it reports.
//
// Run as: php tests/php/test_options_watch_minimums.php [repo-root]

namespace ZM {
  function Debug($m) {}
  function Warning($m) {}
  function Error($m) {}
  function AuditAction($a, $b, $c, $d) {}
}

namespace {
  $failures = 0;
  $tests = 0;
  function check($label, $cond, $detail = '') {
    global $tests, $failures;
    $tests++;
    if ($cond) {
      echo "ok - $label\n";
    } else {
      $failures++;
      echo "FAIL - $label".($detail === '' ? '' : " ($detail)")."\n";
    }
  }

  $root = isset($argv[1]) ? rtrim($argv[1], '/') : dirname(__DIR__, 2);
  $web = $root.'/web';
  if (!is_file($web.'/includes/actions/options.php')) $web = $root;

  $GLOBALS['queries'] = array();
  $GLOBALS['pending'] = array();
  $GLOBALS['config'] = array();

  function dbQuery($sql, $params = array()) {
    $GLOBALS['queries'][] = array('sql' => $sql, 'params' => $params);
    if (preg_match('/^\s*SELECT/i', $sql)) { $GLOBALS['pending'] = $GLOBALS['config']; return 'result'; }
    return 1;
  }
  function dbFetchNext($result) { return array_shift($GLOBALS['pending']); }
  function dbFetchAll($sql) { return $GLOBALS['config']; }
  function dbLastError() { return ''; }
  function canEdit($area) { return true; }
  function loadConfigFiles() { return array(); }
  function configFileValue($value, $type) { return $value; }
  function loadConfig($defineConsts = true) {}

  // Run the real save handler against a config table and posted values.
  function run_save(array $config, array $newConfig) {
    $GLOBALS['queries'] = array();
    $GLOBALS['pending'] = array();
    $GLOBALS['config'] = $config;
    $GLOBALS['error_message'] = '';

    $action = 'options';
    $error_message = '';
    $_REQUEST = array('view' => 'options', 'action' => 'options', 'tab' => 'system',
      'newConfig' => $newConfig);
    include($GLOBALS['web'].'/includes/actions/options.php');

    $saved = array();
    foreach ($GLOBALS['queries'] as $q) {
      if (preg_match('/^\s*UPDATE\s+Config/i', $q['sql'])) $saved[$q['params'][1]] = $q['params'][0];
    }
    return array('saved' => $saved, 'error' => $GLOBALS['error_message']);
  }

  function config_row($name, $value, $type, $default) {
    return array('Name' => $name, 'Value' => $value, 'Type' => $type,
      'DefaultValue' => $default, 'Requires' => '', 'System' => 0, 'Readonly' => 0);
  }

  $GLOBALS['web'] = $web;
  set_include_path($web.PATH_SEPARATOR.get_include_path());

  $watch = array(
    config_row('ZM_WATCH_CHECK_INTERVAL', '10', 'integer', '10'),
    config_row('ZM_WATCH_MAX_DELAY', '45', 'decimal', '45'),
  );

  // 0 is the value that spins the watchdog and restarts every daemon.
  $r = run_save($watch, array('ZM_WATCH_CHECK_INTERVAL' => '0', 'ZM_WATCH_MAX_DELAY' => '0'));
  check('0 is refused for ZM_WATCH_CHECK_INTERVAL', !isset($r['saved']['ZM_WATCH_CHECK_INTERVAL']),
    'saved: '.json_encode($r['saved']));
  check('0 is refused for ZM_WATCH_MAX_DELAY', !isset($r['saved']['ZM_WATCH_MAX_DELAY']),
    'saved: '.json_encode($r['saved']));
  check('the error names ZM_WATCH_CHECK_INTERVAL and its minimum',
    strpos($r['error'], 'ZM_WATCH_CHECK_INTERVAL') !== false && strpos($r['error'], 'minimum of 1') !== false,
    $r['error']);
  check('the error names ZM_WATCH_MAX_DELAY and its minimum',
    strpos($r['error'], 'ZM_WATCH_MAX_DELAY') !== false && strpos($r['error'], 'minimum of 1') !== false,
    $r['error']);

  // Everything below the minimum is refused, not just 0.
  $r = run_save($watch, array('ZM_WATCH_CHECK_INTERVAL' => '-5', 'ZM_WATCH_MAX_DELAY' => '0.5'));
  check('-5 is refused for ZM_WATCH_CHECK_INTERVAL', !isset($r['saved']['ZM_WATCH_CHECK_INTERVAL']),
    'saved: '.json_encode($r['saved']));
  check('0.5 is refused for ZM_WATCH_MAX_DELAY', !isset($r['saved']['ZM_WATCH_MAX_DELAY']),
    'saved: '.json_encode($r['saved']));

  // The minimum itself is accepted.
  $r = run_save($watch, array('ZM_WATCH_CHECK_INTERVAL' => '1', 'ZM_WATCH_MAX_DELAY' => '1'));
  check('1 is accepted for ZM_WATCH_CHECK_INTERVAL', ($r['saved']['ZM_WATCH_CHECK_INTERVAL'] ?? null) === '1',
    'saved: '.json_encode($r['saved']).' error: '.$r['error']);
  check('1 is accepted for ZM_WATCH_MAX_DELAY', ($r['saved']['ZM_WATCH_MAX_DELAY'] ?? null) === '1',
    'saved: '.json_encode($r['saved']).' error: '.$r['error']);
  check('valid values produce no error', $r['error'] === '', $r['error']);

  // The shipped defaults are accepted.
  $low = array(
    config_row('ZM_WATCH_CHECK_INTERVAL', '1', 'integer', '10'),
    config_row('ZM_WATCH_MAX_DELAY', '1', 'decimal', '45'),
  );
  $r = run_save($low, array('ZM_WATCH_CHECK_INTERVAL' => '10', 'ZM_WATCH_MAX_DELAY' => '45'));
  check('default 10 is accepted for ZM_WATCH_CHECK_INTERVAL', ($r['saved']['ZM_WATCH_CHECK_INTERVAL'] ?? null) === '10',
    'saved: '.json_encode($r['saved']).' error: '.$r['error']);
  check('default 45 is accepted for ZM_WATCH_MAX_DELAY', ($r['saved']['ZM_WATCH_MAX_DELAY'] ?? null) === '45',
    'saved: '.json_encode($r['saved']).' error: '.$r['error']);

  // Other integer options keep their existing behaviour, including 0.
  $other = array(config_row('ZM_SOME_INTEGER', '5', 'integer', '5'));
  $r = run_save($other, array('ZM_SOME_INTEGER' => '0'));
  check('an unrelated integer option still accepts 0', ($r['saved']['ZM_SOME_INTEGER'] ?? null) === '0',
    'saved: '.json_encode($r['saved']).' error: '.$r['error']);
  check('the unrelated option produces no error', $r['error'] === '', $r['error']);

  echo "\n$tests tests, $failures failures\n";
  exit($failures ? 1 : 0);
}
