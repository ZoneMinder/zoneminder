<?php
// buildControlCommand() only skipped the ControlPresets update for a user
// without Control edit permission but still returned the presetSet command, so
// a user who may only view Control could overwrite a preset stored on the
// camera; it now returns no command for presetSet without that permission.
//
// Run as: php tests/php/test_control_preset_set_permission.php

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) {
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n";
  }
}

$root = isset($argv[1]) ? rtrim($argv[1], '/') : __DIR__.'/../..';

$stubdir = sys_get_temp_dir().'/zm_control_preset_stubs_'.getmypid();
@mkdir($stubdir, 0700, true);
file_put_contents($stubdir.'/logger.php', "<?php\nnamespace ZM;\nfunction Warning(\$s) {}\nfunction Error(\$s) {}\nfunction Debug(\$s) {}\n");
set_include_path($stubdir.PATH_SEPARATOR.get_include_path());

$src = file_get_contents($root.'/web/includes/functions.php');
foreach (array('validInt', 'validJsStr') as $fn) {
  if (!preg_match('/^function '.$fn.'\(.*?^}/ms', $src, $m)) { echo "FAIL - cannot extract $fn\n"; exit(1); }
  eval($m[0]);
}

$queries = array();
$stored = null;
function canEdit($area, $mid = null) { return $GLOBALS['can_edit_control']; }
function dbFetchOne($sql, $col = null, $params = array()) { global $stored; return $stored; }
function dbQuery($sql, $params = array()) { global $queries; $queries[] = array($sql, $params); }

require_once $root.'/web/includes/control_functions.php';

class StubMonitor {
  public function Control() { return null; }
  public function Id() { return 1; }
}

$queries = array();
$GLOBALS['can_edit_control'] = true;
$_REQUEST = array('control' => 'presetSet', 'preset' => '2', 'newLabel' => 'a');
check('an editor can set a preset', buildControlCommand(new StubMonitor()), ' --preset=2 --command=presetSet');

$queries = array();
$GLOBALS['can_edit_control'] = false;
$_REQUEST = array('control' => 'presetSet', 'preset' => '2', 'newLabel' => 'a');
check('a viewer gets no presetSet command', buildControlCommand(new StubMonitor()), '');
check('a viewer stores no preset label', $queries, array());

$GLOBALS['can_edit_control'] = false;
$_REQUEST = array('control' => 'presetGoto', 'preset' => '2');
check('a viewer can still go to a preset', strpos(buildControlCommand(new StubMonitor()), '--command=presetGoto') !== false, true);

exit($failures ? 1 : 0);
