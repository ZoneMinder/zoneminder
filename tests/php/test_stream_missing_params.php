<?php
// web/ajax/stream.php read $_REQUEST['connkey'] before checking it was sent, so
// a request without connkey or command logged a PHP warning on top of the
// intended "No connkey or no command" error.
//
// Run as: php tests/php/test_stream_missing_params.php

$failures = 0;
function check($name, $ok) {
  global $failures;
  echo ($ok ? '  ok ' : '  FAIL ') . $name . "\n";
  if (!$ok) $failures++;
}

class StopRun extends Exception {}

define('ZM_WEB_AJAX_TIMEOUT', 10000);
define('ZM_PATH_SOCKS', sys_get_temp_dir().'/zm-stream-test-socks');
define('HTTP_STATUS_OK', 200);

$errors = array();
set_error_handler(function($no, $str) use (&$errors) {
  $errors[] = $str;
  return true;
});

$ajaxErrors = array();
function ajaxError($msg, $status = 200, $reason = '') {
  global $ajaxErrors;
  $ajaxErrors[] = $msg;
  throw new StopRun();
}

// stream.php declares functions, so each request runs in its own process.
if (isset($argv[1])) {
  $_REQUEST = json_decode($argv[1], true);
  try {
    include(__DIR__.'/../../web/ajax/stream.php');
  } catch (StopRun $e) {
  }
  echo json_encode(array('ajax'=>$ajaxErrors, 'php'=>$errors));
  exit(0);
}

foreach (array('{}', '{"command":"1"}', '{"connkey":"123"}') as $request) {
  $out = json_decode(shell_exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' '.escapeshellarg($request)), true);
  check("$request reports the missing parameters", $out && count($out['ajax']) == 1
    && strpos($out['ajax'][0], 'No connkey or no command') === 0);
  check("$request raises no PHP warning", $out && count($out['php']) == 0);
  if ($out && $out['php']) echo '    '.implode("\n    ", $out['php'])."\n";
}

exit($failures ? 1 : 0);
