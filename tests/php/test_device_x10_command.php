<?php
// The devices view sends its On/Off column name as the command, but zmx10.pl
// only accepts lower case 'on' and 'off', so ajax/device.php must lower it.
//
// Run as: php tests/php/test_device_x10_command.php

namespace ZM {
  function Warning($m) {}
}

namespace {
  class StopRequest extends Exception {}

  $failures = 0;
  $sent = array();
  function canEdit($area) { return true; }
  function validStr($v) { return is_null($v) ? '' : strip_tags($v); }
  function validHtmlStr($v) { return htmlspecialchars($v); }
  function setDeviceStatusX10($key, $status) { $GLOBALS['sent'][] = array($key, $status); return 'unknown'; }
  function dbQuery($sql, $params = array()) {}
  function ajaxResponse($data = array()) { throw new StopRequest('ok'); }
  function ajaxError($m) { throw new StopRequest($m); }

  foreach (array('On' => 'on', 'Off' => 'off') as $command => $want) {
    $sent = array();
    $_REQUEST = array('action' => 'device', 'command' => $command, 'key' => 'A1');
    try {
      include __DIR__.'/../../web/ajax/device.php';
    } catch (StopRequest $e) {
    }
    if ($sent === array(array('A1', $want))) {
      echo "ok - $command is sent to zmx10 as $want\n";
    } else {
      $failures++;
      echo "FAIL - $command sent as ".var_export($sent, true)."\n";
    }
  }
  exit($failures ? 1 : 0);
}
