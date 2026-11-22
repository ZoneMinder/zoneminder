<?php
// web/ajax/device.php action=device must not report success when the X10
// device did not answer, and setDeviceStatusX10() must preg_quote the key
// when matching the zmx10 reply, so a key like 'A+1' matches its own reply.
//
// Run as: php tests/php/test_ajax_device_status.php [repo_root]

namespace ZM {
  function Warning($m) {}
}

namespace {
  class StopRequest extends Exception {}

  $root = $argv[1] ?? __DIR__.'/../..';

  $failures = 0;
  function check($name, $got, $want) {
    global $failures;
    if ($got === $want) {
      echo "ok - $name\n";
    } else {
      $failures++;
      echo "FAIL - $name\n  got:  ".var_export($got, true)."\n  want: ".var_export($want, true)."\n";
    }
  }

  function canEdit($area) { return true; }
  function validStr($v) { return is_null($v) ? '' : strip_tags($v); }
  function validHtmlStr($v) { return htmlspecialchars($v); }
  function setDeviceStatusX10($key, $status) { return $GLOBALS['x10_status']; }
  function dbQuery($sql, $params = array()) {}
  function ajaxResponse($data = array()) { throw new StopRequest('ok'); }
  function ajaxError($m) { throw new StopRequest($m); }

  // ---- Part 1: web/ajax/device.php handler ----

  // zmx10 did not answer: ajaxError must be reached, not ajaxResponse
  $GLOBALS['x10_status'] = 'unknown';
  $_REQUEST = array('action' => 'device', 'command' => 'On', 'key' => 'A1');
  $msg = null;
  try {
    include $root.'/web/ajax/device.php';
  } catch (StopRequest $e) {
    $msg = $e->getMessage();
  }
  check("unknown status reaches ajaxError, not ajaxResponse", $msg !== 'ok' && !is_null($msg), true);
  check("ajaxError message contains the key 'A1'", strpos($msg, 'A1') !== false, true);

  // zmx10 answered: ajaxResponse must be reached
  $GLOBALS['x10_status'] = 'on';
  $_REQUEST = array('action' => 'device', 'command' => 'On', 'key' => 'A1');
  $msg = null;
  try {
    include $root.'/web/ajax/device.php';
  } catch (StopRequest $e) {
    $msg = $e->getMessage();
  }
  check("'on' status reaches ajaxResponse", $msg, 'ok');

  // ---- Part 2: setDeviceStatusX10 regex in web/includes/functions.php ----

  $src = file_get_contents($root.'/web/includes/functions.php');
  $found = preg_match('/function setDeviceStatusX10\(.*?\n\}\n/s', $src, $m);
  check("setDeviceStatusX10 found in functions.php", $found, 1);
  if ($found) {
    check("setDeviceStatusX10 preg_quotes the key in the reply match",
      strpos($m[0], 'preg_quote($key') !== false, true);
  }

  // Why: an unescaped '+' in the key breaks the match against the device's own reply
  check("quoted key 'A+1' matches its own reply 'A+1 on'",
    preg_match('/^'.preg_quote('A+1', '/').'\s+(.*)/', 'A+1 on'), 1);
  check("unquoted key 'A+1' does not match 'A+1 on'",
    preg_match('/^A+1\s+(.*)/', 'A+1 on'), 0);

  echo "\n".($failures ? "FAILURES: $failures\n" : "All passed\n");
  exit($failures ? 1 : 0);
}
