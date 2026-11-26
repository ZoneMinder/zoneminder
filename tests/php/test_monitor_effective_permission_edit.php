<?php
// The user page's Effective Permission column (Monitor::effectivePermission()) checked View
// before Edit across groups, so a monitor given View by one group and Edit by another showed
// View, while editableMonitor() lets any group's Edit through. Same for the role's groups.
//
// php tests/php/test_monitor_effective_permission_edit.php [root]   (root defaults to the repo)

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$src = file_get_contents($root.'/web/includes/Monitor.php');
$start = strpos($src, 'public function effectivePermission(');
$depth = 0;
for ($i = strpos($src, '{', $start); $i < strlen($src); $i++) {
  if ($src[$i] == '{') $depth++;
  else if ($src[$i] == '}' && --$depth == 0) break;
}
$method = substr($src, $start, $i - $start + 1);

class P { private $p; function __construct($p) { $this->p = $p; } function Permission() { return $this->p; } }
class G { private $ids; function __construct($ids) { $this->ids = $ids; } function MonitorIds() { return $this->ids; } }
class GP { private $p, $g; function __construct($p, $ids) { $this->p = $p; $this->g = new G($ids); }
  function Permission() { return $this->p; } function Group() { return $this->g; } }
class R { public $gps = array(); public $mon = 'None';
  function Monitor_Permission($id) { return new P('Inherit'); } function Group_Permissions() { return $this->gps; }
  function Monitors() { return $this->mon; } }
class U { public $gps = array(); public $role = null;
  function Monitor_Permission($id) { return new P('Inherit'); } function Group_Permissions() { return $this->gps; }
  function Role() { return $this->role; } function Monitors() { return 'None'; } }
eval('class M { function Id() { return 1; } '.$method.' }');

$failures = 0;
function check($n, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $n\n";
  else { $failures++; echo "FAIL - $n (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

$m = new M();
$u = new U();
$u->gps = array(new GP('View', array(1)), new GP('Edit', array(1)));
check('user groups View and Edit show Edit', $m->effectivePermission($u), 'Edit');

$u = new U();
$u->role = new R();
$u->role->gps = array(new GP('View', array(1)), new GP('Edit', array(1)));
check('role groups View and Edit show Edit', $m->effectivePermission($u), 'Edit');

$u = new U();
$u->gps = array(new GP('View', array(1)));
check('a single View group still shows View', $m->effectivePermission($u), 'View');

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
