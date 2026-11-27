<?php
// forwardControlRequest() read the reply status from $http_response_header, which PHP 8.5 deprecates
// ("call http_get_last_response_headers() instead"). The notice is raised when control_functions.php is
// compiled, so every control request logged it. The status must now come from the stream itself, with
// the same results: a 2xx body is returned, an error status, a redirect and an unreachable server give false.
//
// php tests/php/test_control_forward_response_headers.php   (from the repo root)

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

  $file = __DIR__.'/../../web/includes/control_functions.php';
  $uses = array();
  foreach (token_get_all(file_get_contents($file)) as $t) {
    if (is_array($t) && $t[0] == T_VARIABLE && $t[1] == '$http_response_header') $uses[] = 'line '.$t[2];
  }
  check('control_functions.php does not use $http_response_header', $uses, array());

  if (!function_exists('proc_open')) {
    echo "# runtime part skipped\n";
    exit($failures ? 1 : 0);
  }
  $stub = sys_get_temp_dir().'/zm_fwd_stub_'.getmypid();
  mkdir($stub);
  file_put_contents($stub.'/logger.php', "<?php\nnamespace ZM;\nfunction Error(\$m) { \$GLOBALS['zm_errors'][] = \$m; }\n");
  set_include_path($stub.PATH_SEPARATOR.get_include_path());
  require_once($file);

  $dir = sys_get_temp_dir().'/zm_fwd_'.getmypid();
  mkdir($dir);
  file_put_contents($dir.'/router.php', <<<'ROUTER'
<?php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path == '/ok') { echo 'reply-body'; }
elseif ($path == '/err') { http_response_code(500); echo 'server error page'; }
elseif ($path == '/login') { header('Location: /ok'); http_response_code(302); }
elseif ($path == '/empty') { http_response_code(200); }
else { http_response_code(404); }
ROUTER
  );
  $port = 0;
  $sock = stream_socket_server('tcp://127.0.0.1:0');
  $port = (int)substr(strrchr(stream_socket_get_name($sock, false), ':'), 1);
  fclose($sock);
  $proc = proc_open(array(PHP_BINARY, '-S', "127.0.0.1:$port", '-t', $dir, $dir.'/router.php'),
    array(array('pipe', 'r'), array('file', '/dev/null', 'w'), array('file', '/dev/null', 'w')), $pipes);
  for ($i = 0; $i < 50 && !@fsockopen('127.0.0.1', $port); $i++) usleep(100000);

  $base = "http://127.0.0.1:$port";
  $GLOBALS['zm_errors'] = array();
  check('a 200 reply body is returned', forwardControlRequest("$base/ok"), 'reply-body');
  check('an empty 200 reply is returned as an empty string', forwardControlRequest("$base/empty"), '');
  check('no error is logged for a 200', $GLOBALS['zm_errors'], array());
  check('a 500 reply gives false', forwardControlRequest("$base/err"), false);
  check('the 500 status is logged', count($GLOBALS['zm_errors']), 1);
  check('a redirect (login page) gives false', forwardControlRequest("$base/login"), false);
  check('a 404 gives false', forwardControlRequest("$base/none"), false);
  proc_terminate($proc);
  proc_close($proc);
  check('an unreachable server gives false', forwardControlRequest("$base/ok"), false);
  unlink($dir.'/router.php');
  rmdir($dir);
  unlink($stub.'/logger.php');
  rmdir($stub);
  exit($failures ? 1 : 0);
}
