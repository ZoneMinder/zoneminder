<?php
// Saving a device whose did no longer exists (deleted in another tab) ran an UPDATE that
// matched no row and redirected to the device list as if the edit had been saved. It has
// to refuse and report that the device is gone, while an existing device and a new device
// still save.
//
// Runs the real web/includes/actions/device.php against a stubbed database.
//
// php tests/php/test_device_save_missing_device.php [root]   (root defaults to the repo)

function canEdit($a) { return true; }
function dbLastError() { return 'db down'; }
function setDeviceStatusX10($key, $command) {}
// Device 4 is the only one that exists.
function dbFetchOne($sql, $col = null, $args = array()) {
  return ($args == array('4')) ? 4 : false;
}
function dbQuery($sql, $values = array()) {
  $GLOBALS['queries'][] = trim($sql);
  return true;
}
eval('namespace ZM; function Warning($m) {} function Error($m) {}');

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';

$failures = 0;
function check($n, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $n\n";
  else { $failures++; echo "FAIL - $n (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

function save($did) {
  global $root, $error_message, $redirect, $view, $action;
  $error_message = '';
  $redirect = null;
  $view = 'device';
  $action = 'device';
  $GLOBALS['queries'] = array();
  $_REQUEST = array('did' => $did, 'newDevice' => array('Name' => 'Porch', 'KeyString' => 'A1'));
  include $root.'/web/includes/actions/device.php';
  return array($error_message, $redirect, $view, $GLOBALS['queries']);
}

list($err, $redir, $v, $queries) = save('9');
check('a deleted device runs no write', $queries, array());
check('a deleted device reports that it is gone', $err, 'Device not found<br/>');
check('a deleted device does not redirect', $redir, null);
check('a deleted device shows the Devices view', $v, 'devices');

list($err, $redir, $v, $queries) = save('4');
check('an existing device is updated', $queries, array('UPDATE Devices SET Name=?, KeyString=? WHERE Id=?'));
check('an existing device reports nothing', $err, '');
check('an existing device redirects to the list', $redir, '?view=devices');

list($err, $redir, $v, $queries) = save('');
check('a new device is inserted', $queries, array('INSERT INTO Devices SET Name=?, KeyString=?'));
check('a new device reports nothing', $err, '');
check('a new device redirects to the list', $redir, '?view=devices');

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
