<?php
// Saving an X10 device through web/ajax/device.php ignored the result of its
// UPDATE/INSERT and answered success, so a failed save looked like it worked.
// A failed save now answers an error instead of ajaxResponse()'s success.
//
// Run as: php tests/php/test_ajax_device_save_result.php [root]   (root defaults to the repo)

class Done extends Exception {}
function canEdit($a) { return true; }
function ajaxError($m) { $GLOBALS['out'] = 'error: '.$m; throw new Done(); }
function ajaxResponse($r = array()) { $GLOBALS['out'] = 'ok'; throw new Done(); }
function dbLastError() { return 'db down'; }
function validStr($v) { return is_scalar($v) ? (string)$v : ''; }
function validHtmlStr($v) { return htmlspecialchars((string)$v); }
function dbQuery($sql, $values = array()) {
  $GLOBALS['queries'][] = trim($sql);
  return $GLOBALS['dbResult'];
}
function setDeviceStatusX10($key, $command) {}
eval('namespace ZM; function Warning($m) {} function Error($m) {}');

$user = new class {
  public function Username() { return 'admin'; }
};

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';

$failures = 0;
function check($n, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $n\n";
  else { $failures++; echo "FAIL - $n (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

function save($request, $dbResult) {
  global $root;
  $GLOBALS['out'] = 'nothing';
  $GLOBALS['queries'] = array();
  $GLOBALS['dbResult'] = $dbResult;
  $_REQUEST = $request;
  try {
    include $root.'/web/ajax/device.php';
  } catch (Done $e) {
  }
  return $GLOBALS['out'];
}

$insert = array('action' => 'device', 'newDevice' => array('Name' => 'Lamp', 'KeyString' => 'A1'));
$update = array('action' => 'device', 'newDevice' => array('Name' => 'Lamp', 'KeyString' => 'A1'), 'did' => '5');

check('an insert that works answers ok', save($insert, 1), 'ok');
check('an insert that works runs an INSERT', substr($GLOBALS['queries'][0], 0, 6), 'INSERT');

check('a failed insert answers an error', substr(save($insert, null), 0, 28), 'error: Failed to save device');

check('an update that works answers ok', save($update, 1), 'ok');
check('an update that works runs an UPDATE', substr($GLOBALS['queries'][0], 0, 6), 'UPDATE');

check('a failed update answers an error', substr(save($update, null), 0, 28), 'error: Failed to save device');

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
