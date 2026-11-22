<?php
// Saving a filter while ZM_OPT_FFMPEG/UPLOAD/EMAIL/MESSAGE is off must not
// clear AutoVideo/AutoUpload/AutoEmail/AutoMessage. The view does not render
// those checkboxes then, so the POST lacks them and the action used to write 0.
//
// Run as: php tests/php/test_filter_disabled_options_kept.php [webroot]
// Constants cannot be redefined, so the script runs itself once per scenario.

namespace ZM {
  class Filter {
    public static $data = array();
    public static $saved_data = null;
    private $id;
    public function __construct($id = null) { $this->id = $id ? (int)$id : null; }
    public function Id($v = null) { if (func_num_args()) $this->id = $v; return $this->id; }
    public function Name($v = null) { return self::$data['Name']; }
    public function Background() { return 0; }
    public function AutoExecute() { return 0; }
    public function AutoExecuteCmd() { return ''; }
    public function canEdit() { return true; }
    public function canDelete() { return true; }
    public function changes($new) {
      $changes = array();
      foreach ($new as $field => $value) {
        if ((self::$data[$field] ?? null) != $value) $changes[$field] = $value;
      }
      return $changes;
    }
    public function set($c) { foreach ($c as $f => $v) self::$data[$f] = $v; }
    public function save($c) { $this->set($c); self::$saved_data = self::$data; return true; }
    public function control($c, $s = null) {}
    public function execute() {}
    public function delete() {}
    public function get_last_error() { return ''; }
  }
  function Debug($s) {}
  function Warning($s) {}
  function Error($s) {}
  function Info($s) {}
  function AuditAction() {}
}

namespace {
  $webroot = isset($argv[1]) ? rtrim($argv[1], '/') : __DIR__.'/../../web';
  $mode = $argv[2] ?? '';

  if ($mode === '') {
    $failures = 0;
    foreach (array('off', 'on') as $m) {
      passthru(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' '.escapeshellarg($webroot).' '.$m, $rc);
      if ($rc) $failures++;
    }
    exit($failures ? 1 : 0);
  }

  $on = ($mode === 'on') ? 1 : 0;
  foreach (array('FFMPEG', 'UPLOAD', 'EMAIL', 'MESSAGE') as $o) define("ZM_OPT_$o", $on);

  $failures = 0;
  $passes = 0;
  function check($name, $got, $want) {
    global $failures, $passes, $mode;
    if ($got === $want) { $passes++; echo "ok - [options $mode] $name\n"; }
    else { $failures++; echo "FAIL - [options $mode] $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
  }
  function canView($a) { return true; }
  function validCardinal($i) { return preg_replace('/\D/', '', $i); }
  function validInt($i) { return preg_replace('/[^\-\d]/', '', $i); }
  function validStr($i) { return is_null($i) ? '' : strip_tags($i); }

  $stubdir = sys_get_temp_dir().'/zm_filter_disabled_stubs_'.getmypid();
  @mkdir($stubdir.'/includes', 0700, true);
  file_put_contents($stubdir.'/includes/Filter.php', "<?php\n");
  set_include_path($stubdir.PATH_SEPARATOR.get_include_path());

  $stored = array(
    'Name'=>'MyFilter',
    'Query'=>array('sort_field'=>'Id', 'sort_asc'=>'1', 'limit'=>'', 'skip_locked'=>0, 'terms'=>array()),
    'AutoCopy'=>0, 'AutoCopyTo'=>0, 'AutoMove'=>0, 'AutoMoveTo'=>0,
    'AutoArchive'=>0, 'AutoUnarchive'=>0, 'AutoVideo'=>1, 'AutoUpload'=>1,
    'AutoEmail'=>1, 'AutoMessage'=>1, 'AutoExecute'=>0, 'AutoDelete'=>0,
    'UpdateDiskSpace'=>0, 'Background'=>0, 'Concurrent'=>0, 'LockRows'=>0,
  );

  // The editor POST after the user changed only the name. Checkboxes that were
  // not rendered, or were unticked, are absent. With the options on they were
  // rendered ticked, so they are posted; the test unticks AutoEmail below.
  $post = $stored;
  $post['Name'] = 'Renamed';
  foreach (array('AutoVideo', 'AutoUpload', 'AutoEmail', 'AutoMessage') as $f) {
    unset($post[$f]);
    if ($on and $f != 'AutoEmail') $post[$f] = '1';
  }
  foreach (array('AutoCopy','AutoMove','AutoArchive','AutoUnarchive','AutoExecute','AutoDelete','UpdateDiskSpace','Background','Concurrent','LockRows') as $f) unset($post[$f]);

  ZM\Filter::$data = $stored;
  $action = 'Save';
  $error_message = '';
  $redirect = null;
  $_REQUEST = array('object'=>'filter', 'Id'=>'5', 'filter'=>$post);
  require($webroot.'/includes/actions/filter.php');
  $saved = ZM\Filter::$saved_data;

  check('save happened', $saved !== null, true);
  check('name change saved', $saved['Name'] ?? null, 'Renamed');
  if ($on) {
    check('AutoEmail unticked saves 0', $saved['AutoEmail'] ?? null, 0);
    check('AutoVideo ticked saves 1', $saved['AutoVideo'] ?? null, 1);
    check('AutoUpload ticked saves 1', $saved['AutoUpload'] ?? null, 1);
    check('AutoMessage ticked saves 1', $saved['AutoMessage'] ?? null, 1);
  } else {
    check('AutoEmail kept', $saved['AutoEmail'] ?? null, 1);
    check('AutoVideo kept', $saved['AutoVideo'] ?? null, 1);
    check('AutoUpload kept', $saved['AutoUpload'] ?? null, 1);
    check('AutoMessage kept', $saved['AutoMessage'] ?? null, 1);
  }

  @unlink($stubdir.'/includes/Filter.php');
  @rmdir($stubdir.'/includes');
  @rmdir($stubdir);
  echo "$passes passed, $failures failed.\n";
  exit($failures ? 1 : 0);
}
