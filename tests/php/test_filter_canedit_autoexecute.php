<?php
// Security regression test: an Events=View user must not be able to execute
// arbitrary OS commands through a Filter's AutoExecute/AutoExecuteCmd.
//
// Exercises the REAL ZM\Filter::canEdit() decision table:
//   * A view-only (Events=View) user must NOT be able to edit/execute a filter
//     that has ANY auto side-effect enabled (the original bug used `and`, so it
//     only blocked when ALL five were set at once -> RCE).
//   * AutoExecute (arbitrary OS command via zmfilter.pl) requires System edit
//     permission, not merely Events edit.
//   * Ownership is enforced: a non-System user can only act on their own filter.
//
// Run as: php tests/php/test_filter_canedit_autoexecute.php
//
// The real Filter class pulls in database.php / FilterTerm.php / Monitor.php via
// require_once, but canEdit() itself only uses ZM_Object's magic accessors (the
// `defaults` array, no DB) plus the passed-in user. So we stub the require-only
// dependencies, keep the real Object.php + Filter.php, and drive canEdit()
// directly.

namespace ZM;

// ---- test harness (top-level namespace-free helpers) -----------------------

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

// ---- stub the require-only dependencies of Filter.php ----------------------

$stubdir = sys_get_temp_dir().'/zm_filter_canedit_stubs_'.getmypid();
@mkdir($stubdir, 0700, true);
foreach (array('database.php', 'FilterTerm.php', 'Monitor.php') as $stub) {
  file_put_contents($stubdir.'/'.$stub, "<?php\n");
}
// Stub dir first so its empty database.php/FilterTerm.php/Monitor.php win, then
// the real web/includes for Object.php and Filter.php.
set_include_path($stubdir.PATH_SEPARATOR.__DIR__.'/../../web/includes'.PATH_SEPARATOR.get_include_path());

// Logging stubs (ZM namespace) in case any accessor path emits one.
function Warning($s) { /* noop */ }
function Error($s)   { /* noop */ }
function Debug($s)   { /* noop */ }
function Info($s)    { /* noop */ }

require_once __DIR__.'/../../web/includes/Filter.php';

// ---- stub user -------------------------------------------------------------

class TestUser {
  private $id;
  private $perms; // area => level ('None'|'View'|'Edit')
  public function __construct($id, $perms) { $this->id = $id; $this->perms = $perms; }
  public function Id() { return $this->id; }
  private function level($area) { return isset($this->perms[$area]) ? $this->perms[$area] : 'None'; }
  public function canView($area) { $l = $this->level($area); return $l == 'View' || $l == 'Edit'; }
  public function canEdit($area) { return $this->level($area) == 'Edit'; }
}

// Helper: build a Filter owned by $ownerId with the given auto-flags set.
function make_filter($ownerId, array $flags) {
  $f = new Filter();
  $f->UserId($ownerId);
  foreach ($flags as $k => $v) {
    $f->$k($v);
  }
  return $f;
}

$systemAdmin = new TestUser(1, array('System' => 'Edit'));
$eventsEditor = new TestUser(2, array('Events' => 'Edit'));
$eventsViewer = new TestUser(3, array('Events' => 'View'));

// ---- the reported vulnerability: view-only user + AutoExecute --------------

$f = make_filter(3, array('AutoExecute' => 1, 'AutoExecuteCmd' => '/bin/sh -c "id"'));
check('Events=View owner CANNOT edit AutoExecute filter', $f->canEdit($eventsViewer), false);

// Each side-effect alone must block a view-only user (the `and` -> `or` fix).
foreach (array('AutoExecute','AutoDelete','AutoUnarchive','AutoArchive',
               'AutoMove','AutoCopy','AutoVideo','AutoUpload',
               'AutoEmail','AutoMessage') as $flag) {
  $f = make_filter(3, array($flag => 1));
  check("Events=View owner blocked when only $flag set", $f->canEdit($eventsViewer), false);
}

// A pure query filter (no auto actions) is fine for a view-only owner.
$f = make_filter(3, array());
check('Events=View owner CAN edit plain query filter', $f->canEdit($eventsViewer), true);

// ---- AutoExecute requires System edit, not just Events edit ----------------

$f = make_filter(2, array('AutoExecute' => 1, 'AutoExecuteCmd' => '/bin/sh -c "id"'));
check('Events=Edit owner CANNOT edit AutoExecute filter', $f->canEdit($eventsEditor), false);

// Events editor may still run event-changing filters.
$f = make_filter(2, array('AutoDelete' => 1));
check('Events=Edit owner CAN edit AutoDelete filter', $f->canEdit($eventsEditor), true);
$f = make_filter(2, array('AutoMove' => 1, 'AutoMoveTo' => 5));
check('Events=Edit owner CAN edit AutoMove filter', $f->canEdit($eventsEditor), true);

// System editor can do anything, including AutoExecute.
$f = make_filter(999, array('AutoExecute' => 1, 'AutoExecuteCmd' => '/bin/sh -c "id"'));
check('System editor CAN edit AutoExecute filter (any owner)', $f->canEdit($systemAdmin), true);

// ---- ownership enforcement -------------------------------------------------

$f = make_filter(1, array()); // owned by someone else
check('Events=Edit user CANNOT edit filter owned by another user', $f->canEdit($eventsEditor), false);
check('Events=View user CANNOT edit filter owned by another user', $f->canEdit($eventsViewer), false);

// A user with no Events permission at all cannot edit even their own filter.
$noPerm = new TestUser(4, array());
$f = make_filter(4, array());
check('User with no Events perm CANNOT edit own plain filter', $f->canEdit($noPerm), false);

// ---- cleanup ---------------------------------------------------------------

@unlink($stubdir.'/database.php');
@unlink($stubdir.'/FilterTerm.php');
@unlink($stubdir.'/Monitor.php');
@rmdir($stubdir);

echo "\n$passes passed, $failures failed.\n";
exit($failures ? 1 : 0);
