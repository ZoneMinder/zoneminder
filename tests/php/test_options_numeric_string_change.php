<?php
// Saving an option compared the posted value with the stored one using !=, so a
// string option edited between values PHP reads as the same number ("1000" ->
// "1e3", "7" -> "007", "5" -> " 5") was treated as unchanged and not saved.
//
// The real actions/options.php runs with the database layer stubbed.
// Run as: php tests/php/test_options_numeric_string_change.php [repo-root]

namespace ZM {
  function Debug($m) {}
  function Warning($m) {}
  function Error($m) {}
  function AuditAction($a, $b, $c, $d) {}
}

namespace {
  $failures = 0;
  function check($label, $cond) {
    global $failures;
    if ($cond) echo "ok - $label\n"; else { $failures++; echo "FAIL - $label\n"; }
  }

  $root = isset($argv[1]) ? rtrim($argv[1], '/') : dirname(__DIR__, 2);
  $web = $root.'/web';

  $GLOBALS['queries'] = array();
  $GLOBALS['pending'] = array();
  $GLOBALS['config'] = array(
    array('Name' => 'ZM_STR_A', 'Value' => '1000', 'Type' => 'string', 'DefaultValue' => '', 'Requires' => '', 'System' => 0, 'Readonly' => 0),
    array('Name' => 'ZM_STR_B', 'Value' => '7', 'Type' => 'string', 'DefaultValue' => '', 'Requires' => '', 'System' => 0, 'Readonly' => 0),
    array('Name' => 'ZM_STR_C', 'Value' => 'same', 'Type' => 'string', 'DefaultValue' => '', 'Requires' => '', 'System' => 0, 'Readonly' => 0),
    array('Name' => 'ZM_BOOL', 'Value' => '1', 'Type' => 'boolean', 'DefaultValue' => '1', 'Requires' => '', 'System' => 0, 'Readonly' => 0),
  );
  function dbQuery($sql, $params = array()) {
    $GLOBALS['queries'][] = array($sql, $params);
    if (preg_match('/^\s*SELECT/i', $sql)) { $GLOBALS['pending'] = $GLOBALS['config']; return 'result'; }
    return 1;
  }
  function dbFetchNext($r) { return array_shift($GLOBALS['pending']); }
  function dbFetchAll($sql) { return $GLOBALS['config']; }
  function dbLastError() { return ''; }
  function canEdit($a) { return true; }
  function loadConfigFiles() { return array(); }
  function configFileValue($v, $t) { return $v; }
  function loadConfig($d = true) {}

  set_include_path($web.PATH_SEPARATOR.get_include_path());
  $action = 'options';
  $error_message = '';
  $_REQUEST = array('action' => 'options', 'tab' => 'x', 'newConfig' => array(
    'ZM_STR_A' => '1e3', 'ZM_STR_B' => '007', 'ZM_STR_C' => 'same', 'ZM_BOOL' => '1'));
  include($web.'/includes/actions/options.php');

  $saved = array();
  foreach ($GLOBALS['queries'] as $q)
    if (preg_match('/^\s*UPDATE\s+Config/i', $q[0])) $saved[$q[1][1]] = $q[1][0];

  check('"1000" edited to "1e3" is saved', isset($saved['ZM_STR_A']) && $saved['ZM_STR_A'] === '1e3');
  check('"7" edited to "007" is saved', isset($saved['ZM_STR_B']) && $saved['ZM_STR_B'] === '007');
  check('an unchanged string is not written', !isset($saved['ZM_STR_C']));
  check('an unchanged checked boolean is not written', !isset($saved['ZM_BOOL']));
  exit($failures ? 1 : 0);
}
