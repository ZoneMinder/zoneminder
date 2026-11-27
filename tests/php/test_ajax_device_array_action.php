<?php
// device.php and devices.php passed $_REQUEST['action'] to validStr(), which is strip_tags(), and
// device.php passed $_REQUEST['command'] to strtolower(). An array there (action[]=x) is a
// TypeError on PHP 8, so the request died with a 500 instead of the 'Unrecognised action' error.
// A plain action and command must still work.
//
// Run as: php tests/php/test_ajax_device_array_action.php [repo_root]

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
  function validHtmlStr($v) { return is_array($v) ? '' : htmlspecialchars($v); }
  function setDeviceStatusX10($key, $status) {
    return (is_scalar($key) and is_scalar($status)) ? 'on' : 'unknown';
  }
  function dbQuery($sql, $params = array()) { $GLOBALS['db_calls'][] = $sql; return true; }
  function dbLastError() { return ''; }
  function ajaxResponse($data = array()) { throw new StopRequest('ok'); }
  function ajaxError($m) { throw new StopRequest('error: '.$m); }

  function run($root, $file, $request) {
    $GLOBALS['db_calls'] = array();
    $_REQUEST = $request;
    try {
      include $root.'/web/ajax/'.$file;
    } catch (StopRequest $e) {
      return $e->getMessage();
    } catch (Throwable $e) {
      return get_class($e).': '.$e->getMessage();
    }
    return null;
  }

  foreach (array('device.php' => 'error: Unrecognised action ', 'devices.php' => 'error: Unrecognised action ""') as $file => $want) {
    check("$file: an array action is an unrecognised action",
      run($root, $file, array('action' => array('x'))), $want);
    check("$file: a nested array action is an unrecognised action",
      run($root, $file, array('action' => array(array('x')))), $want);
  }

  check('devices.php: action=delete still deletes', run($root, 'devices.php', array('action' => 'delete', 'markDids' => array('3', '4'))), 'ok');
  check('devices.php: action=delete ran a DELETE per id', count($GLOBALS['db_calls']), 2);

  check('device.php: a command still reaches the X10 helper', run($root, 'device.php', array('action' => 'device', 'command' => 'On', 'key' => 'A1')), 'ok');
  check('device.php: an array command is refused, not a TypeError',
    run($root, 'device.php', array('action' => 'device', 'command' => array('x'), 'key' => 'A1')),
    'error: No response from X10 device A1');

  exit($failures ? 1 : 0);
}
