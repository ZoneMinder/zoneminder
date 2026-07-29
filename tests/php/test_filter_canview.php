<?php
// Security regression test for ZM\Filter::canView(), which gates the
// filterdebug modal (it exposes the MySQL EXPLAIN for a filter's query).
//
//   * System viewers can inspect any filter.
//   * A non-System user may only view a filter they own.
//   * A stored filter owned by someone else (or owned by nobody) is NOT
//     viewable by a non-System user.
//   * An unsaved/transient filter (no Id, e.g. built from the request in the
//     filterdebug modal) is viewable by the requester.
//
// Run as: php tests/php/test_filter_canview.php
//
// canView() only reads UserId()/Id() and the passed-in user, so we stub the
// require-only dependencies and drive the real ZM\Filter.

namespace ZM;

$failures = 0;
$passes = 0;

function check($name, $got, $want) {
  global $failures, $passes;
  if ($got === $want) {
    $passes++;
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n";
  }
}

$stubdir = sys_get_temp_dir().'/zm_filter_canview_stubs_'.getmypid();
@mkdir($stubdir, 0700, true);
foreach (array('database.php', 'FilterTerm.php', 'Monitor.php') as $stub) {
  file_put_contents($stubdir.'/'.$stub, "<?php\n");
}
set_include_path($stubdir.PATH_SEPARATOR.__DIR__.'/../../web/includes'.PATH_SEPARATOR.get_include_path());

function Warning($s) { /* noop */ }
function Error($s)   { /* noop */ }
function Debug($s)   { /* noop */ }
function Info($s)    { /* noop */ }

require_once __DIR__.'/../../web/includes/Filter.php';

class TestUser {
  private $id;
  private $perms;
  public function __construct($id, $perms) { $this->id = $id; $this->perms = $perms; }
  public function Id() { return $this->id; }
  private function level($area) { return isset($this->perms[$area]) ? $this->perms[$area] : 'None'; }
  public function canView($area) { $l = $this->level($area); return $l == 'View' || $l == 'Edit'; }
  public function canEdit($area) { return $this->level($area) == 'Edit'; }
}

// Build a filter with a given stored Id (0 = unsaved) and owner UserId.
function make_filter($id, $ownerId) {
  $f = new Filter();
  if ($id) $f->Id($id);
  $f->UserId($ownerId);
  return $f;
}

$systemViewer = new TestUser(1, array('System' => 'View'));
$systemAdmin  = new TestUser(2, array('System' => 'Edit'));
$owner        = new TestUser(3, array('Events' => 'View'));
$otherUser    = new TestUser(4, array('Events' => 'Edit'));

// Stored filter (Id=10) owned by user 3.
$stored = make_filter(10, 3);
check('System viewer CAN view any stored filter', $stored->canView($systemViewer), true);
check('System editor CAN view any stored filter', $stored->canView($systemAdmin), true);
check('owner CAN view own stored filter', $stored->canView($owner), true);
check('non-owner CANNOT view another user stored filter', $stored->canView($otherUser), false);

// Stored filter owned by nobody (UserId 0) must not leak to a non-System user.
$global = make_filter(11, 0);
check('non-System user CANNOT view unowned stored filter', $global->canView($otherUser), false);
check('System viewer CAN view unowned stored filter', $global->canView($systemViewer), true);

// Unsaved/transient filter (no Id) built from the requester's own input.
$adhoc = make_filter(0, 0);
check('any user CAN view unsaved (no-Id) filter', $adhoc->canView($otherUser), true);
check('any user CAN view unsaved filter even with foreign UserId',
      make_filter(0, 999)->canView($otherUser), true);

@unlink($stubdir.'/database.php');
@unlink($stubdir.'/FilterTerm.php');
@unlink($stubdir.'/Monitor.php');
@rmdir($stubdir);

echo "\n$passes passed, $failures failed.\n";
exit($failures ? 1 : 0);
