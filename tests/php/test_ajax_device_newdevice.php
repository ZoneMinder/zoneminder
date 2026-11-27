<?php
// web/ajax/device.php action=device with newDevice must not INSERT a device
// unless a KeyString is supplied, and a complete newDevice (Name+KeyString)
// must still INSERT and respond.
//
// Run as: php tests/php/test_ajax_device_newdevice.php [repo_root]

namespace ZM {
  function Warning($m) {}
}

namespace {
  class StopRequest extends Exception {}

  $root = $argv[1] ?? __DIR__.'/../..';

  error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);

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
  function setDeviceStatusX10($key, $status) { return 'unknown'; }
  function dbQuery($sql, $params = array()) {
    $GLOBALS['db_calls'][] = array($sql, $params);
    return true;
  }
  function dbLastError() { return ''; }
  function ajaxResponse($data = array()) { throw new StopRequest('ok'); }
  function ajaxError($m) { throw new StopRequest($m); }

  // Runs the handler with a fresh request and returns the StopRequest message.
  function runDevice($root, $request) {
    $GLOBALS['db_calls'] = array();
    $_REQUEST = $request;
    try {
      include $root.'/web/ajax/device.php';
    } catch (StopRequest $e) {
      return $e->getMessage();
    }
    return null;
  }

  function countInserts() {
    $n = 0;
    foreach ($GLOBALS['db_calls'] as $call) {
      if (stripos($call[0], 'INSERT') !== false) $n++;
    }
    return $n;
  }

  // ---- Part 1: newDevice with no fields ----

  $msg = runDevice($root, array('action' => 'device', 'newDevice' => array()));
  check("empty newDevice does not INSERT", countInserts(), 0);
  check("empty newDevice reaches ajaxError, not ajaxResponse",
    $msg !== 'ok' && !is_null($msg), true);

  // ---- Part 2: newDevice with a Name but no KeyString ----

  $msg = runDevice($root, array('action' => 'device', 'newDevice' => array('Name' => 'a')));
  check("newDevice without KeyString does not INSERT", countInserts(), 0);
  check("newDevice without KeyString reaches ajaxError, not ajaxResponse",
    $msg !== 'ok' && !is_null($msg), true);

  // ---- Part 3: valid newDevice ----

  $msg = runDevice($root, array('action' => 'device',
    'newDevice' => array('Name' => 'a', 'KeyString' => 'b')));
  check("valid newDevice reaches ajaxResponse", $msg, 'ok');
  check("valid newDevice INSERTs once", countInserts(), 1);
  check("valid newDevice INSERT carries Name and KeyString",
    isset($GLOBALS['db_calls'][0]) ? $GLOBALS['db_calls'][0][1] : null,
    array('a', 'b'));

  echo "\n".($failures ? "FAILURES: $failures\n" : "All passed\n");
  exit($failures ? 1 : 0);
}
