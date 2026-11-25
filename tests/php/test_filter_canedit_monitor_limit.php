<?php
// zmfilter.pl runs a filter against every monitor. A user denied a monitor could
// save a filter with an action (AutoDelete, AutoMove, ...) and no Monitor term, and
// it then acted on that monitor's events. canEdit() now refuses a filter with an
// action unless its terms limit it to monitors the user can view.
//
// Run as: php tests/php/test_filter_canedit_monitor_limit.php [web root]

namespace ZM;

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $name\n";
  else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../../web';
$stubdir = sys_get_temp_dir().'/zm_filter_limit_stubs_'.getmypid();
@mkdir($stubdir, 0700, true);
foreach (array('database.php', 'FilterTerm.php') as $stub) file_put_contents($stubdir.'/'.$stub, "<?php\n");
file_put_contents($stubdir.'/Monitor.php', "<?php\n");
set_include_path($stubdir.PATH_SEPARATOR.$root.'/includes'.PATH_SEPARATOR.get_include_path());

function Warning($s) {}
function Error($s) {}
function Debug($s) {}
function Info($s) {}
function jsonEncode($v) { return json_encode($v); }
function jsonDecode($v) { return json_decode($v, true); }

// Monitor 1 is viewable but not editable, monitor 2 is denied.
class Monitor {
  public $id;
  function __construct($id) { $this->id = $id; }
  static function find($f = array()) { return array(new Monitor(1), new Monitor(2)); }
  static function find_one($f) { return in_array($f['Id'], array(1, 2)) ? new Monitor($f['Id']) : null; }
  function canView($u = null) { return $this->id == 1; }
  function canEdit($u = null) { return false; }
}

require_once $root.'/includes/Filter.php';

class TestUser {
  function Id() { return 2; }
  function canView($a) { return true; }
  function canEdit($a) { return $a == 'Events'; }
}
$u = new TestUser();

function make($flags, $terms) {
  $f = new Filter();
  $f->UserId(2);
  foreach ($flags as $k => $v) $f->$k($v);
  $f->Query(array('terms' => $terms));
  return $f;
}
$mon = function($val, $extra = array()) { return $extra + array('attr' => 'Monitor', 'op' => '=', 'val' => $val); };
$other = array('attr' => 'Name', 'op' => 'LIKE', 'val' => 'x', 'cnj' => 'and');

check('AutoDelete with no monitor term is refused', make(array('AutoDelete' => 1), array($other))->canEdit($u), false);
check('AutoDelete limited to a denied monitor is refused', make(array('AutoDelete' => 1), array($mon('2')))->canEdit($u), false);
check('AutoDelete naming viewable and denied monitors is refused', make(array('AutoDelete' => 1), array($mon('["1","2"]', array('op' => 'IN'))))->canEdit($u), false);
check('an OR term next to the monitor term is refused', make(array('AutoDelete' => 1), array($mon('1'), array_merge($other, array('cnj' => 'or'))))->canEdit($u), false);
check('a not-equal monitor term is refused', make(array('AutoDelete' => 1), array($mon('1', array('op' => '!='))))->canEdit($u), false);
check('AutoDelete limited to a viewable monitor is allowed', make(array('AutoDelete' => 1), array($mon('1')))->canEdit($u), true);
check('a viewable monitor list is allowed', make(array('AutoMove' => 1), array($mon('["1"]', array('op' => 'IN'))))->canEdit($u), true);
check('a filter with no action is allowed', make(array(), array($other))->canEdit($u), true);

@unlink($stubdir.'/database.php'); @unlink($stubdir.'/FilterTerm.php'); @unlink($stubdir.'/Monitor.php'); @rmdir($stubdir);
echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
