<?php
// A filter with AutoExecute runs its AutoExecuteCmd as an OS command through zmfilter.pl, so
// turning AutoExecute on, or changing the command, must require System edit. On master/1.38 the
// GHSA-ff93-w6fm-vqxr fix was needed because Filter::canEdit() asked the ZM\User object
// ($u->canEdit('System')), which fell through ZM_Object::__call() and returned a truthy area
// name, so any Events=View account passed as a System editor and could save and run an
// AutoExecute filter.
//
// 1.36 is not built that way: Filter::canView()/canEdit() and the filter action use the global
// array-based canView()/canEdit() ($user['System'] == 'Edit'), so the fall-through is never
// reached. This test drives the real web/includes/actions/filter.php against a stub ZM\Filter and
// confirms the gates hold on 1.36, so the invariant does not quietly regress if the user object is
// ever wired in here. refs GHSA-ff93-w6fm-vqxr
//
// Run as: php tests/php/test_filter_autoexecute_requires_system.php

namespace {

error_reporting(E_ALL & ~E_DEPRECATED);

$failures = 0;
$passes = 0;
function check($name, $cond) {
  global $failures, $passes;
  if ($cond) { $passes++; echo "ok - $name\n"; }
  else { $failures++; echo "FAIL - $name\n"; }
}

// ---- stubs for everything the action file reaches for ----------------------

$GLOBALS['user'] = null;          // the logged-in user, an array on 1.36
$GLOBALS['saved_filter'] = null;  // records what the action did to the filter

// The array-based global permission helpers, copied from 1.36 auth.php.
function canView($area, $mid=false) {
  global $user;
  return ($user && ($user[$area] == 'View' || $user[$area] == 'Edit') && (!$mid || visibleMonitor($mid)));
}
function canEdit($area, $mid=false) {
  global $user;
  return ($user && ($user[$area] == 'Edit') && (!$mid || visibleMonitor($mid)));
}
function visibleMonitor($mid) { return true; }

function validStr($s) { return $s; }
function validInt($s) { return (int)$s; }
function validCardinal($s) { return preg_replace('/\D/', '', $s); }
function addFilterTerm($f, $l) { return $f; }
function delFilterTerm($f, $l) { return $f; }

} // end global namespace (stubs)

namespace ZM {
  function Warning($s) {}
  function Error($s) {}
  function Debug($s) {}

  // A filter loaded from the database. canEdit() mirrors 1.36 Filter::canEdit(): System editors
  // always; otherwise only the owner, never an AutoExecute filter, and a View-only owner only when
  // the filter changes nothing.
  class Filter {
    public $fields;
    public $saved = false;
    public $executed = false;
    public $controlled = array();
    public function __construct($data = null) {
      // A stored filter is seeded from $GLOBALS['stored_filter']; a new one (null id) is empty.
      $this->fields = $data === null ? array('Id'=>null) : $GLOBALS['stored_filter'];
    }
    public function Id($v = null) { if ($v !== null || func_num_args()) { $this->fields['Id'] = $v; } return isset($this->fields['Id']) ? $this->fields['Id'] : null; }
    public function UserId() { return isset($this->fields['UserId']) ? $this->fields['UserId'] : 0; }
    public function AutoExecute() { return !empty($this->fields['AutoExecute']); }
    public function AutoExecuteCmd() { return isset($this->fields['AutoExecuteCmd']) ? $this->fields['AutoExecuteCmd'] : ''; }
    public function AutoDelete() { return 0; } public function AutoUnarchive() { return 0; }
    public function AutoArchive() { return 0; } public function AutoMove() { return 0; }
    public function AutoCopy() { return 0; } public function AutoVideo() { return 0; }
    public function AutoUpload() { return 0; } public function AutoEmail() { return 0; }
    public function AutoMessage() { return 0; }
    public function Background() { return !empty($this->fields['Background']); }
    public function Name($v = null) { if ($v !== null) $this->fields['Name'] = $v; return isset($this->fields['Name']) ? $this->fields['Name'] : ''; }
    public function canEdit() {
      global $user;
      if (canEdit('System')) return true;
      if (!$user or ($this->UserId() != $user['Id'])) return false;
      if ($this->AutoExecute()) return false;
      if (canEdit('Events')) return true;
      if (canView('Events')) return true;
      return false;
    }
    public function changes($new) {
      // Report only fields whose value differs from the stored filter.
      $changes = array();
      foreach ($new as $k => $v) {
        if (!is_array($v) and (!isset($this->fields[$k]) or $this->fields[$k] != $v)) $changes[$k] = $v;
      }
      return $changes;
    }
    public function save($changes) { $this->fields = array_merge($this->fields, $changes); $this->saved = true; $GLOBALS['saved_filter'] = $this; return true; }
    public function get_last_error() { return ''; }
    public function execute() { $this->executed = true; $GLOBALS['saved_filter'] = $this; }
    public function control($cmd, $server = null) { $this->controlled[] = $cmd; $GLOBALS['saved_filter'] = $this; }
    public function delete() {}
    public function querystring($a, $b) { return ''; }
  }
}

namespace {

// The action file does require_once('includes/Filter.php'); point the include path at a directory
// holding an empty stub for it, since our ZM\Filter is already declared here.
$base = sys_get_temp_dir().'/zm_ff93_'.getmypid();
@mkdir($base.'/includes', 0700, true);
file_put_contents($base.'/includes/Filter.php', "<?php\n");
set_include_path($base.PATH_SEPARATOR.get_include_path());

// Run the real action file with the given logged-in user, request and action, and report whether
// the filter was saved/executed/controlled. Including it inside this function means a top-level
// return in the action (its way of refusing) just ends the include, leaving $saved_filter unset.
function run_filter_action($user, $request, $action, $stored = null) {
  $GLOBALS['user'] = $user;
  $GLOBALS['stored_filter'] = $stored;
  $GLOBALS['saved_filter'] = null;
  $GLOBALS['error_message'] = '';
  $GLOBALS['redirect'] = '';
  $_REQUEST = $request;
  include __DIR__.'/../../web/includes/actions/filter.php';
  return $GLOBALS['saved_filter'];
}

// Build a saveable AutoExecute request: turn AutoExecute on with a command.
function autoexec_request() {
  return array(
    'object' => 'filter',
    'Id' => '',
    'filter' => array(
      'Name' => 'pwn',
      'AutoExecute' => '1',
      'AutoExecuteCmd' => 'touch /tmp/pwned',
      'Query' => array('sort_field'=>'', 'sort_asc'=>'', 'limit'=>''),
    ),
  );
}

// ---- the attacker roles ----------------------------------------------------

// Events=View only: the ff93 attacker. Must not reach the save path at all.
$f = run_filter_action(array('Id'=>5, 'Events'=>'View', 'System'=>'None'),
  autoexec_request(), 'Save');
check('Events=View user cannot save a filter at all', $f === null);

$f = run_filter_action(array('Id'=>5, 'Events'=>'View', 'System'=>'None'),
  autoexec_request(), 'execute');
check('Events=View user cannot execute a filter at all', $f === null);

// Events=Edit, System=None: may edit filters, but must not set AutoExecute.
$f = run_filter_action(array('Id'=>5, 'Events'=>'Edit', 'System'=>'None'),
  autoexec_request(), 'SaveAs');
check('Events=Edit System=None user cannot save an AutoExecute filter',
  $f === null || (!$f->saved && !$f->executed));

$f = run_filter_action(array('Id'=>5, 'Events'=>'Edit', 'System'=>'None'),
  autoexec_request(), 'execute');
check('Events=Edit System=None user cannot execute a new AutoExecute filter',
  $f === null || (!$f->saved && !$f->executed));

// Changing the command on a stored AutoExecute filter also needs System edit.
$stored = array('Id'=>9, 'UserId'=>5, 'AutoExecute'=>1, 'AutoExecuteCmd'=>'old', 'Background'=>0);
$req = autoexec_request();
$req['Id'] = '9';
$req['filter']['AutoExecuteCmd'] = 'touch /tmp/pwned';
$f = run_filter_action(array('Id'=>5, 'Events'=>'Edit', 'System'=>'None'), $req, 'Save', $stored);
check('Events=Edit System=None owner cannot change an AutoExecuteCmd',
  $f === null || (!$f->saved && !$f->executed));

// System=Edit: the intended operator. May save the AutoExecute filter.
$f = run_filter_action(array('Id'=>1, 'Events'=>'Edit', 'System'=>'Edit'),
  autoexec_request(), 'SaveAs');
check('System=Edit user can save an AutoExecute filter', $f !== null && $f->saved);

// ---- structural: the gates are present in the real source ------------------

$src = file_get_contents(__DIR__.'/../../web/includes/actions/filter.php');
check('filter action gates the whole block on canEdit(Events)',
  preg_match('/else if \(canEdit\(\s*[\'"]Events[\'"]\s*\)\)/', $src) === 1);
check('filter action requires canEdit(System) to set AutoExecute',
  preg_match('/!canEdit\(\s*[\'"]System[\'"]\s*\).*AutoExecute/s', $src) === 1);
check('filter action checks canEdit() on the stored filter before applying changes',
  preg_match('/\$filter->Id\(\)\s+and\s+!\$filter->canEdit\(\)/', $src) === 1);

$fsrc = file_get_contents(__DIR__.'/../../web/includes/Filter.php');
check('Filter::canEdit uses the global canEdit(), not a user object method',
  preg_match('/function canEdit\(\)/', $fsrc) &&
  preg_match('/function canEdit\(\).*canEdit\(\s*[\'"]System[\'"]\s*\)/s', $fsrc) &&
  !preg_match('/function canEdit\(\$area/', $fsrc));

@unlink($base.'/includes/Filter.php');
@rmdir($base.'/includes'); @rmdir($base);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
}
