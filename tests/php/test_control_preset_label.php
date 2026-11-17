<?php
// Saving a PTZ preset label stored it through validJsStr(), i.e. addslashes() and
// strip_tags(), into a prepared statement. The text is then escaped again when
// watch.js.php writes it into the page, so a label such as Bob's door came back as
// Bob\'s door, and gained a backslash on every further save. The label has to be
// stored as typed.
//
// Run as: php tests/php/test_control_preset_label.php

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

$stubdir = sys_get_temp_dir().'/zm_control_preset_stubs_'.getmypid();
@mkdir($stubdir, 0700, true);
file_put_contents($stubdir.'/logger.php', "<?php\nnamespace ZM;\nfunction Warning(\$s) {}\nfunction Error(\$s) {}\nfunction Debug(\$s) {}\n");
set_include_path($stubdir.PATH_SEPARATOR.get_include_path());

$src = file_get_contents(__DIR__.'/../../web/includes/functions.php');
foreach (array('validInt', 'validJsStr') as $fn) {
  if (!preg_match('/^function '.$fn.'\(.*?^}/ms', $src, $m)) { echo "FAIL - cannot extract $fn\n"; exit(1); }
  eval($m[0]);
}

$queries = array();
$stored = null;
function canEdit($area, $mid = null) { return true; }
function dbFetchOne($sql, $col = null, $params = array()) { global $stored; return $stored; }
function dbQuery($sql, $params = array()) { global $queries; $queries[] = array($sql, $params); }

require_once __DIR__.'/../../web/includes/control_functions.php';

class StubMonitor {
  public function Control() { return null; }
  public function Id() { return 1; }
}

function save_label($label) {
  global $queries;
  $queries = array();
  $_REQUEST = array('control' => 'presetSet', 'preset' => '2', 'newLabel' => $label);
  buildControlCommand(new StubMonitor());
  return $queries;
}

$q = save_label("Bob's door");
check('a REPLACE is issued', count($q), 1);
check('an apostrophe is stored as typed', $q[0][1][2] ?? null, "Bob's door");

$q = save_label('Back "gate" \\ side');
check('quotes and backslashes are stored as typed', $q[0][1][2] ?? null, 'Back "gate" \\ side');

$q = save_label(array('x'));
check('an array label stores nothing', count($q), 0);

$_REQUEST = array('control' => 'presetSet', 'preset' => '2', 'newLabel' => 'a');
check('the preset option is given once', buildControlCommand(new StubMonitor()), ' --preset=2 --command=presetSet');

exit($failures ? 1 : 0);
