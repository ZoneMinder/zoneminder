<?php
// Deleting zones on the Zones page reported success even when the DELETE failed:
// ZM_Object::delete() discarded the query result and returned nothing, and
// actions/zones.php appended that nothing to $error_message, then restarted the
// monitor and redirected as if the zone were gone. delete() now returns whether
// it worked and the action reports a failure.
//
// Run as: php tests/php/test_zones_delete_result.php [root]   (root defaults to the repo)

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$failures = 0;
function check($n, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $n\n";
  else { $failures++; echo "FAIL - $n (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

// Object.php requires database.php through the include path; a stub in front of the
// real one lets delete() run without a database.
$stub_dir = sys_get_temp_dir().'/zm_zones_del_stub_'.getmypid();
@mkdir($stub_dir);
file_put_contents($stub_dir.'/database.php', <<<'STUB'
<?php
namespace {
$GLOBALS['fail'] = false;
function dbQuery($sql, $params = null) { return $GLOBALS['fail'] ? null : true; }
function dbLastError() { return $GLOBALS['fail'] ? 'lock wait timeout' : ''; }
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
  protected $defaults = array('Id' => null);
}
$thing = new FakeThing();
$thing->Id(7);
$GLOBALS['fail'] = false;
check('ZM_Object::delete() returns true when the DELETE works', $thing->delete(), true);
$GLOBALS['fail'] = true;
check('ZM_Object::delete() returns false when the DELETE fails', $thing->delete(), false);

// actions/zones.php with stub zones and monitors.
eval('namespace ZM {
  class FakeMonitor {
    public $restarts = 0;
    function canEdit() { return true; }
    function Id() { return 1; }
    function Name() { return "Door"; }
    function Type() { return "Ffmpeg"; }
    function zmcControl($m) { $this->restarts++; }
  }
  class Zone {
    static $monitor;
    private $id;
    function __construct($id) { $this->id = $id; }
    function Id() { return $this->id; }
    function Monitor() { return self::$monitor; }
    function delete() { return !$GLOBALS["fail"]; }
  }
  function AuditAction() { $GLOBALS["audits"]++; }
}');
function validCardinal($v) { return is_scalar($v) ? preg_replace('/\D/', '', $v) : ''; }
function daemonCheck() { return true; }

$zones_dir = sys_get_temp_dir().'/zm_zones_del_inc_'.getmypid();
@mkdir($zones_dir.'/includes', 0700, true);
file_put_contents($zones_dir.'/includes/Zone.php', "<?php\n");
set_include_path($zones_dir.PATH_SEPARATOR.get_include_path());

function run_delete($fail) {
  global $root, $error_message, $redirect, $action;
  $GLOBALS['fail'] = $fail;
  $GLOBALS['audits'] = 0;
  ZM\Zone::$monitor = new ZM\FakeMonitor();
  $error_message = '';
  $redirect = null;
  $action = 'delete';
  $_REQUEST = array('action' => 'delete', 'markZids' => array('3'), 'mids' => array('1'));
  include $root.'/web/includes/actions/zones.php';
  return array($error_message, $redirect, ZM\Zone::$monitor->restarts, $GLOBALS['audits']);
}

list($err, $redir, $restarts, $audits) = run_delete(false);
check('a delete that works redirects back to the zones', $redir, '?view=zones&mids[]=1');
check('a delete that works restarts the monitor', $restarts, 1);
list($err, $redir, $restarts, $audits) = run_delete(true);
check('a failed delete is reported', strpos($err, 'Error deleting zone 3') !== false, true);
check('a failed delete does not redirect', $redir, null);
check('a failed delete does not restart the monitor', $restarts, 0);
check('a failed delete is not audited', $audits, 0);

@unlink($zones_dir.'/includes/Zone.php'); @rmdir($zones_dir.'/includes'); @rmdir($zones_dir);
@unlink($stub_dir.'/database.php'); @rmdir($stub_dir);
echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
