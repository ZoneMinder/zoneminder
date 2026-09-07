<?php
// Deleting a monitor is a soft delete: Monitor::delete() sets Deleted=true via
// ZM_Object::save(). save() returns false and stashes the driver message on a
// failure (an out of range Importance was the reported trigger), but delete()
// discarded that and the console action reported nothing, so a monitor that had
// not been deleted looked deleted. Issue #4215.
// Run: php tests/php/test_object_save_error.php   (from the repo root)

$root = __DIR__.'/../..';
$failures = 0;
function check($label, $ok, $detail = '') {
  global $failures;
  if (!$ok) $failures++;
  printf("[%s] %s%s\n", $ok ? 'PASS' : 'FAIL', $label, ($ok || $detail === '') ? '' : "\n        $detail");
}

// Object.php requires database.php by include_path, so a stub in front of the
// real one lets the save path run without a database.
$stub_dir = sys_get_temp_dir().'/zm_obj_stub_'.getmypid();
@mkdir($stub_dir);
file_put_contents($stub_dir.'/database.php', <<<'STUB'
<?php
namespace {
$GLOBALS['queries'] = array();
$GLOBALS['fail'] = false;
function dbQuery($sql, $params = null) {
  $GLOBALS['queries'][] = $sql;
  return $GLOBALS['fail'] ? null : true;
}
function dbLastError() { return $GLOBALS['fail'] ? 'SQLSTATE[01000]: Data truncated for column Importance' : ''; }
function dbError($sql) { return ''; }
function dbInsertId() { return 1; }
function dbFetchAll($sql, $col = null, $params = null) { return array(); }
function dbFetchOne($sql, $col = null, $params = null) { return null; }
}
namespace ZM { function Debug($m) {} function Warning($m) {} function Error($m) {} function Info($m) {} }
STUB
);
set_include_path($stub_dir.PATH_SEPARATOR.$root.'/web/includes'.PATH_SEPARATOR.get_include_path());
require_once($root.'/web/includes/Object.php');

class FakeThing extends ZM\ZM_Object {
  protected static $table = 'Fakes';
  protected $defaults = array('Id' => null, 'Deleted' => 0);
}

// A save that the database rejects.
$GLOBALS['fail'] = true;
$thing = new FakeThing();
$thing->Id(7);
$ok = $thing->save(array('Deleted' => true));
check('a rejected save reports failure', $ok === false);
check('and keeps the driver message for the caller',
  strpos((string)$thing->get_last_error(), 'Importance') !== false,
  'got: '.var_export($thing->get_last_error(), true));

// And the normal path still works.
$GLOBALS['fail'] = false;
$thing2 = new FakeThing();
$thing2->Id(8);
check('a good save still reports success', $thing2->save(array('Deleted' => true)) === true);

@unlink($stub_dir.'/database.php');
@rmdir($stub_dir);

// Monitor::delete() must hand that result back rather than swallow it.
$monitor_src = file_get_contents($root.'/web/includes/Monitor.php');
check('Monitor::delete returns the result of the save',
  (bool)preg_match('/return \$this->save\(\[\'Deleted\'=>true\]\);/', $monitor_src));

// And the console action has to surface it, and not audit a delete that failed.
$console_src = file_get_contents($root.'/web/includes/actions/console.php');
check('the console action checks the result',
  (bool)preg_match('/if \(\$monitor->delete\(\)\)/', $console_src));
check('it tells the user what the database said',
  strpos($console_src, 'get_last_error()') !== false);
check('and only audits a delete that happened',
  strpos($console_src, "AuditAction('delete', 'monitor'") <
  strpos($console_src, "\$error_message .= 'Error deleting monitor "));

print $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
