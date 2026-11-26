<?php
// web/ajax/stream.php read $_REQUEST['connkey'] (and 'command') without isset, so a request
// without them logged "Undefined array key" warnings before the 'No connkey or no command'
// error. The start of the file is run here with those parameters missing.
//
// Run as: php tests/php/test_ajax_stream_connkey.php

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

$lines = file(__DIR__.'/../../web/ajax/stream.php');
$head = '';
foreach ($lines as $line) {
  if (strpos($line, '@mkdir') !== false) break;
  $head .= $line;
}
$head = preg_replace('/^<\?php/', '', $head);

class AjaxError extends Exception {}
function ajaxError($message, $code = 0, $reason = '') { throw new AjaxError($message); }
define('ZM_WEB_AJAX_TIMEOUT', 10);
define('HTTP_STATUS_OK', 200);

$warnings = array();
// The head defines constants, so a second run reports them as already defined.
set_error_handler(function($no, $str) use (&$warnings) {
  if (strpos($str, 'already defined') === false) $warnings[] = $str;
  return true;
});
error_reporting(E_ALL);

function run($head, $request) {
  $_REQUEST = $request;
  try {
    eval($head);
    return 'passed';
  } catch (AjaxError $e) {
    return 'ajaxError';
  }
}

foreach (array(
  'no connkey and no command' => array(),
  'a command without connkey' => array('command' => '1'),
  'a connkey without command' => array('connkey' => '123'),
) as $name => $request) {
  $warnings = array();
  $got = run($head, $request);
  check("$name is an ajaxError", $got, 'ajaxError');
  check("$name gives no warnings", $warnings, array());
}
$warnings = array();
check('connkey and command pass the guard', run($head, array('connkey' => '123', 'command' => '1')), 'passed');

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
?>
