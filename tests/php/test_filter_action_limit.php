<?php
// Saving a filter from the editor must not store a negative result limit:
// "LIMIT -5" is a syntax error in the SQL built by Filter::Events() and by
// zmfilter.pl, so the filter would never run again.
//
// Run as: php tests/php/test_filter_action_limit.php

namespace ZM {
  class Filter {
    public static $changes = null;
    public function __construct($id = null) {}
    public function Id($v = null) { return 3; }
    public function Name($v = null) { return ''; }
    public function Background() { return 0; }
    public function AutoExecute() { return 0; }
    public function AutoExecuteCmd() { return ''; }
    public function canEdit() { return true; }
    public function canDelete() { return true; }
    public function changes($new) { self::$changes = $new; return $new; }
    public function set($c) {}
    public function save($c) { return true; }
    public function control($c, $s = null) {}
    public function execute() {}
    public function delete() {}
    public function get_last_error() { return ''; }
  }
  function Debug($s) {}
  function Warning($s) {}
  function Error($s) {}
  function AuditAction() {}
}

namespace {
  $failures = 0;
  $passes = 0;
  function check($name, $got, $want) {
    global $failures, $passes;
    if ($got === $want) { $passes++; echo "ok - $name\n"; }
    else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
  }

  foreach (array("FFMPEG", "UPLOAD", "EMAIL", "MESSAGE") as $opt) define("ZM_OPT_$opt", 1);
  function canView($a) { return true; }
  function validCardinal($i) { return preg_replace('/\D/', '', $i); }
  function validInt($i) { return preg_replace('/[^\-\d]/', '', $i); }
  function validStr($i) { return is_null($i) ? '' : strip_tags($i); }

  // The action file requires includes/Filter.php; the stub class above stands in for it.
  $stubdir = sys_get_temp_dir().'/zm_filter_action_stubs_'.getmypid();
  @mkdir($stubdir.'/includes', 0700, true);
  file_put_contents($stubdir.'/includes/Filter.php', "<?php\n");
  set_include_path($stubdir.PATH_SEPARATOR.get_include_path());

  function run_save($limit) {
    global $action, $error_message, $redirect;
    $action = 'Save';
    $error_message = '';
    $_REQUEST = array('object'=>'filter', 'Id'=>'3', 'filter'=>array(
      'Name'=>'n',
      'Query'=>array('sort_field'=>'Id', 'sort_asc'=>'1', 'limit'=>$limit, 'terms'=>array()),
    ));
    ZM\Filter::$changes = null;
    require(__DIR__.'/../../web/includes/actions/filter.php');
    return ZM\Filter::$changes['Query']['limit'];
  }

  check('a positive limit is kept', run_save('25'), '25');
  check('an empty limit stays empty', run_save(''), '');
  check('a negative limit is not stored', run_save('-5'), '0');

  @unlink($stubdir.'/includes/Filter.php');
  @rmdir($stubdir.'/includes');
  @rmdir($stubdir);
  echo "\n$passes passed, $failures failed.\n";
  exit($failures ? 1 : 0);
}
