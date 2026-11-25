<?php
// Saving a snapshot or report whose id no longer exists (deleted in another
// tab) built an empty object and save() INSERTed a new row instead of
// refusing.
// Run: php tests/php/test_snapshot_report_save_missing_id.php   (from the repo root)

namespace ZM {
  function Warning($m) {}
  function Error($m) {}
  function Debug($m) {}
  function AuditAction($a, $b, $c, $d) {}
  class Snapshot {
    public function __construct($id = null) {}
    public function Id() { return null; }
    public function CreatedBy() { return null; }
    public function changes($v) { return $v; }
    public function save($c) { $GLOBALS['done'][] = 'Snapshot::save'; return true; }
    public function delete() { $GLOBALS['done'][] = 'Snapshot::delete'; }
  }
  class Report {
    public function __construct($id = null) {}
    public function Id() { return null; }
    public function canEdit() { return true; }
    public function changes($v) { return $v; }
    public function save($c) { $GLOBALS['done'][] = 'Report::save'; return true; }
    public function get_last_error() { return ''; }
    public function delete() {}
  }
}

namespace {
  $stub = sys_get_temp_dir().'/zm_save_missing_id_'.getmypid();
  mkdir($stub.'/includes', 0777, true);
  foreach (array('Snapshot', 'Monitor', 'Report') as $f) file_put_contents($stub.'/includes/'.$f.'.php', "<?php\n");
  set_include_path($stub.PATH_SEPARATOR.get_include_path());

  class StubUser { public function Id() { return 2; } }
  $user = new StubUser();
  function canEdit($a, $m = false) { return true; }
  function validCardinal($v) { return preg_replace('/\D/', '', $v); }

  $fail = 0;
  function check($name, $ok) { global $fail; echo ($ok ? "ok - " : "NOT OK - ").$name."\n"; if (!$ok) $fail++; }

  $action = 'save'; $done = array(); $redirect = null; $error_message = '';
  $_REQUEST = array('id' => '999', 'snapshot' => array('Name' => 'x'));
  include __DIR__.'/../../web/includes/actions/snapshot.php';
  check('snapshot save with a missing id does not insert', !in_array('Snapshot::save', $done));
  check('snapshot save with a missing id goes back to the list', $redirect === '?view=snapshots');

  $action = 'save'; $done = array(); $redirect = null;
  $_REQUEST = array('id' => '999', 'Report' => array('Name' => 'x'));
  include __DIR__.'/../../web/includes/actions/report.php';
  check('report save with a missing id does not insert', !in_array('Report::save', $done));
  check('report save with a missing id goes back to the list', $redirect === '?view=reports');

  $action = 'save'; $done = array(); $redirect = null;
  $_REQUEST = array('Report' => array('Name' => 'x'));
  include __DIR__.'/../../web/includes/actions/report.php';
  check('report save without an id still creates', in_array('Report::save', $done));

  system('rm -rf '.escapeshellarg($stub));
  exit($fail ? 1 : 0);
}
