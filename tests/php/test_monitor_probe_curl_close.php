<?php
// curl() in web/includes/monitor_probe.php (used by the Add Monitors probe) called curl_close(), which
// PHP 8.5 deprecates because it has had no effect since PHP 8.0. The handle is freed when it goes out of
// scope, so the calls are simply dropped. The function must still return the response body.
//
// php tests/php/test_monitor_probe_curl_close.php   (from the repo root)

namespace ZM {
  function Debug($m) {}
  function Warning($m) {}
  function Error($m) {}
  function urlForLog($u) { return $u; }
}

namespace {
  $failures = 0;
  function check($name, $got, $expected) {
    global $failures;
    if ($got === $expected) {
      echo "ok - $name\n";
    } else {
      $failures++;
      echo 'not ok - '.$name.' (got '.var_export($got, true).' expected '.var_export($expected, true).")\n";
    }
  }

  $src = file_get_contents(__DIR__.'/../../web/includes/monitor_probe.php');
  $calls = array();
  foreach (token_get_all($src) as $t) {
    if (is_array($t) && $t[0] == T_STRING && $t[1] == 'curl_close') $calls[] = 'line '.$t[2];
  }
  check('monitor_probe.php does not call curl_close()', $calls, array());

  if (!function_exists('curl_init') || !function_exists('proc_open')) {
    echo "# runtime part skipped\n";
    exit($failures ? 1 : 0);
  }

  // Load only curl() from the file: the rest needs the database.
  $start = strpos($src, 'function curl(');
  $end = strpos($src, "\nfunction ", $start);
  eval(substr($src, $start, $end - $start));

  $dir = sys_get_temp_dir().'/zm_probe_'.getmypid();
  mkdir($dir);
  file_put_contents($dir.'/router.php', "<?php echo 'probe-body';\n");
  $sock = stream_socket_server('tcp://127.0.0.1:0');
  $port = (int)substr(strrchr(stream_socket_get_name($sock, false), ':'), 1);
  fclose($sock);
  $proc = proc_open(array(PHP_BINARY, '-S', "127.0.0.1:$port", '-t', $dir, $dir.'/router.php'),
    array(array('pipe', 'r'), array('file', '/dev/null', 'w'), array('file', '/dev/null', 'w')), $pipes);
  for ($i = 0; $i < 50 && !@fsockopen('127.0.0.1', $port); $i++) usleep(100000);

  $deprecations = array();
  set_error_handler(function($errno, $errstr) use (&$deprecations) {
    $deprecations[] = $errstr;
    return true;
  });
  check('curl() returns the response body', curl('GET', "http://127.0.0.1:$port/x", 'u', 'p'), 'probe-body');
  restore_error_handler();
  check('curl() raises no PHP deprecation', $deprecations, array());
  proc_terminate($proc);
  proc_close($proc);
  unlink($dir.'/router.php');
  rmdir($dir);
  exit($failures ? 1 : 0);
}
