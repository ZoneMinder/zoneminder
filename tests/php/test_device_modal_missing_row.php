<?php
/*
 * Opening the device modal for a device that was deleted since the list was drawn
 * (dbFetchOne returns false) read Name and KeyString from false, raising warnings
 * on PHP 8 and an empty title. It falls back to the new device form instead.
 * Also, ajax/devices.php delete takes a scalar or nested markDids without a warning.
 *
 * Run: php tests/php/test_device_modal_missing_row.php
 */
namespace {
  function canEdit($a) { return true; }
  function translate($s) { return $s; }
  function validHtmlStr($s) { return htmlspecialchars((string)$s); }
  function getCSRFinputHTML() { return ''; }
  function dbFetchOne($sql, $col = null, $params = array()) { return false; }
  $deleted = array();
  function dbQuery($sql, $params = array()) { $GLOBALS['deleted'][] = $params[0]; return true; }
  function validStr($v) { return strip_tags((string)$v); }
  function ajaxResponse() {}
  function ajaxError($m) {}

  $tests = 0; $failures = 0;
  function check($label, $cond) {
    global $tests, $failures;
    $tests++;
    if ($cond) echo "  ok  - $label\n"; else { $failures++; echo "  NOT OK - $label\n"; }
  }
  $warnings = array();
  set_error_handler(function($no, $str) use (&$warnings) { $warnings[] = $str; return true; });

  $_REQUEST = array('did' => '99');
  ob_start();
  include __DIR__.'/../../web/ajax/modals/device.php';
  $html = ob_get_clean();
  check('no warnings for a missing device row', count($warnings) === 0);
  check('modal falls back to a new device', strpos($html, 'New Device') !== false && strpos($html, 'name="did" value=""') !== false);

  $warnings = array();
  $user = new stdClass();
  $_REQUEST = array('action' => 'delete', 'markDids' => '7');
  include __DIR__.'/../../web/ajax/devices.php';
  check('scalar markDids deletes that device', $deleted === array('7'));
  check('scalar markDids raises no warning', count($warnings) === 0);
  $deleted = array();
  $_REQUEST = array('action' => 'delete', 'markDids' => array('3', array('x'), '4'));
  include __DIR__.'/../../web/ajax/devices.php';
  check('nested markDids entries are skipped', $deleted === array('3', '4') && count($warnings) === 0);

  echo "\n$tests tests, $failures failures\n";
  exit($failures ? 1 : 0);
}
