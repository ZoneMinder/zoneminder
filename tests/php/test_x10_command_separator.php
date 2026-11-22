<?php
// setDeviceStatusX10() in web/includes/functions.php builds the zmx10 socket
// message "status;key" from request values, so a key or command containing
// ';' added fields to the message; it now refuses such values and returns
// 'unknown' without contacting zmx10.
//
// Run as: php tests/php/test_x10_command_separator.php [repo_root]

namespace ZM {
  function Warning($m) {}
  function Fatal($m) { throw new \Exception($m); }
}

namespace {
  $root = isset($argv[1]) ? rtrim($argv[1], '/') : __DIR__.'/../..';

  $failures = 0;
  function check($name, $got, $want) {
    global $failures;
    if ($got === $want) {
      echo "ok - $name\n";
    } else {
      $failures++;
      echo "FAIL - $name (got ".var_export($got, true).")\n";
    }
  }

  $dir = sys_get_temp_dir().'/zm_x10_'.getmypid();
  if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
  }
  $script = $dir.'/zmx10.pl';
  file_put_contents($script, "#!/bin/sh\necho \"\$4 on\"\n");
  chmod($script, 0755);

  define('ZM_PATH_BIN', $dir);
  define('ZM_PATH_SOCKS', $dir.'/nosocket');

  function cleanupX10Dir($dir) {
    @unlink($dir.'/zmx10.pl');
    @rmdir($dir);
  }

  $src = file_get_contents($root.'/web/includes/functions.php');
  $found = preg_match('/^function setDeviceStatusX10\(.*?^\}\n/ms', $src, $m);
  if (!$found) {
    echo "FAIL - setDeviceStatusX10 found in functions.php (got not found)\n";
    cleanupX10Dir($dir);
    exit(1);
  }
  eval($m[0]);

  check("plain key '7' with command 'on' returns 'on'",
    setDeviceStatusX10('7', 'on'), 'on');
  check("key '1;2' is refused",
    setDeviceStatusX10('1;2', 'on'), 'unknown');
  check("command 'on;off' is refused",
    setDeviceStatusX10('7', "on;off"), 'unknown');
  check("key with newline is refused",
    setDeviceStatusX10("7\n8", 'on'), 'unknown');

  cleanupX10Dir($dir);

  exit($failures ? 1 : 0);
}
