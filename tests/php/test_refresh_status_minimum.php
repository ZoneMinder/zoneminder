<?php
// ZM_WEB_<H|M|L>_REFRESH_STATUS is an integer option the Options page accepts as
// 0. Watch, montage, zone, zones, cycle and event set their status poll to
// 1000*ZM_WEB_REFRESH_STATUS ms, so 0 made each open page poll zms and the web
// server as fast as the browser allows. The skin config now aliases it with a
// minimum of 1 second.
//
// Each case runs the skin config in a child process, since constants cannot be
// redefined. Run as: php tests/php/test_refresh_status_minimum.php

namespace ZM {
  function Warning($m) { $GLOBALS['warnings'][] = $m; }
}

namespace {
  $config = __DIR__.'/../../web/skins/classic/includes/config.php';

  if (isset($argv[1]) and $argv[1] == '--child') {
    if (!function_exists('translate')) { function translate($s) { return $s; } }
    $GLOBALS['warnings'] = array();
    // Every per-profile setting the config aliases, with harmless values.
    foreach (array('H', 'M', 'L') as $p) {
      foreach (array('REFRESH_MAIN', 'REFRESH_NAVBAR', 'REFRESH_CYCLE', 'REFRESH_IMAGE', 'REFRESH_STATUS',
        'REFRESH_EVENTS', 'CAN_STREAM', 'STREAM_METHOD', 'DEFAULT_SCALE', 'DEFAULT_RATE', 'VIDEO_BITRATE',
        'VIDEO_MAXFPS', 'SCALE_THUMBS', 'EVENTS_VIEW', 'SHOW_PROGRESS', 'AJAX_TIMEOUT') as $s) {
        define("ZM_WEB_{$p}_$s", $s == 'REFRESH_STATUS' ? $argv[2] : '1');
      }
    }
    $_COOKIE['zmBandwidth'] = 'high';
    require $config;
    echo json_encode(array('status' => ZM_WEB_REFRESH_STATUS, 'warnings' => count($GLOBALS['warnings']),
      'main' => ZM_WEB_REFRESH_MAIN));
    exit(0);
  }

  $failures = 0;
  function check($name, $got, $want) {
    global $failures;
    if ($got === $want) echo "ok - $name\n";
    else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
  }
  function run_with($value) {
    $out = shell_exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' --child '.escapeshellarg($value).' 2>&1');
    $r = json_decode((string)$out, true);
    if (!is_array($r)) { echo "child output: $out\n"; return array('status' => null, 'warnings' => null); }
    return $r;
  }

  $r = run_with('0');
  check('a status refresh of 0 becomes 1 second', (int)$r['status'], 1);
  check('and logs a warning', $r['warnings'], 1);
  $r = run_with('5');
  check('5 seconds is kept', (int)$r['status'], 5);
  check('with no warning', $r['warnings'], 0);
  check('other settings are aliased unchanged', (string)($r['main'] ?? ''), '1');

  echo $failures ? "\n$failures failed\n" : "\nall passed\n";
  exit($failures ? 1 : 0);
}
