<?php
// Saving an X10 device (Devices > New/edit > Save) ignored the result of its UPDATE/INSERT
// and always redirected to the list, and deleting devices always answered success. A failed
// save or delete now shows its error. The save also refuses a request without Name and
// KeyString instead of reading them unchecked.
//
// Run as: php tests/php/test_devices_save_delete_result.php [root]   (root defaults to the repo)

class Done extends Exception {}
function canEdit($a) { return true; }
function ajaxError($m) { $GLOBALS['out'] = 'error: '.$m; throw new Done(); }
function ajaxResponse($r = array()) { $GLOBALS['out'] = 'ok'; throw new Done(); }
function dbLastError() { return 'db down'; }
function validStr($v) { return is_scalar($v) ? (string)$v : ''; }
function dbQuery($sql, $values = array()) {
  $GLOBALS['queries'][] = trim($sql);
  return $GLOBALS['dbResult'];
}
function setDeviceStatusX10($key, $command) {}
eval('namespace ZM; function Warning($m) {} function Error($m) {}');

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';

$failures = 0;
function check($n, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $n\n";
  else { $failures++; echo "FAIL - $n (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

function save($request, $dbResult) {
  global $root, $error_message, $redirect, $view, $action;
  $error_message = '';
  $redirect = null;
  $view = 'device';
  $action = 'device';
  $GLOBALS['queries'] = array();
  $GLOBALS['dbResult'] = $dbResult;
  $_REQUEST = $request;
  $warnings = array();
  set_error_handler(function($no, $str) use (&$warnings) { $warnings[] = $str; return true; });
  include $root.'/web/includes/actions/device.php';
  restore_error_handler();
  return array($error_message, $redirect, $view, $warnings);
}

function del($dbResult) {
  global $root, $user;
  $user = null;
  $GLOBALS['out'] = 'nothing';
  $GLOBALS['dbResult'] = $dbResult;
  $_REQUEST = array('action' => 'delete', 'markDids' => array('4'));
  try {
    include $root.'/web/ajax/devices.php';
  } catch (Done $e) {
  }
  return $GLOBALS['out'];
}

$new = array('newDevice' => array('Name' => 'Porch', 'KeyString' => 'A1'), 'did' => '');
list($err, $redir, $v) = save($new, true);
check('a save that works redirects to the list', $redir, '?view=devices');
check('a save that works reports nothing', $err, '');

list($err, $redir, $v) = save($new, null);
check('a failed save is reported', strpos($err, 'Error saving device') !== false, true);
check('a failed save does not redirect', $redir, null);
check('a failed save shows the Devices view', $v, 'devices');

list($err, $redir, $v, $warnings) = save(array('newDevice' => array('Name' => 'Porch')), true);
check('a save without KeyString is refused', strpos($err, 'KeyString') !== false, true);
check('a save without KeyString runs no query', count($GLOBALS['queries']), 0);
check('a save without KeyString raises no PHP warnings', $warnings, array());

check('a delete that works answers ok', del(true), 'ok');
check('a delete that fails answers an error', del(null), 'error: Failed to delete device: db down');

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
