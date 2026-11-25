<?php
// Pressing Execute in the filter editor ran zmfilter.pl but left the user with
// no feedback: the action ignored Filter::execute()'s status. The redirect back
// to the filter page now carries executed=1 or executed=0 so the page can say
// whether it ran, while a reload still does not run the filter again.
//
// Runs the real web/includes/actions/filter.php against a stubbed Filter.
//
// Run as: php tests/php/test_filter_execute_feedback.php [webroot]

namespace ZM {
  class Filter {
    public static $status = 0;
    public static $execute_calls = 0;

    private $id;

    public function __construct($id = null) { $this->id = $id ? (int)$id : null; }
    public function Id($v = null) {
      if (func_num_args()) $this->id = $v;
      return $this->id;
    }
    public function Name($v = null) { return 'MyFilter'; }
    public function Background() { return 0; }
    public function AutoExecute() { return 0; }
    public function AutoExecuteCmd() { return ''; }
    public function canEdit() { return true; }
    public function canDelete() { return true; }
    // No changes: execute runs the stored filter rather than a temporary copy.
    public function changes($new) { return array(); }
    public function set($c) {}
    public function save($c) { return true; }
    public function control($c, $s = null) {}
    public function execute() {
      self::$execute_calls++;
      return self::$status;
    }
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

  foreach (array('FFMPEG', 'UPLOAD', 'EMAIL', 'MESSAGE') as $opt) define("ZM_OPT_$opt", 1);
  function canView($a) { return true; }
  function validCardinal($i) { return preg_replace('/\D/', '', $i); }
  function validInt($i) { return preg_replace('/[^\-\d]/', '', $i); }
  function validStr($i) { return is_null($i) ? '' : strip_tags($i); }

  $webroot = isset($argv[1]) ? rtrim($argv[1], '/') : __DIR__.'/../../web';

  // The action requires includes/Filter.php; the stub class above stands in.
  $stubdir = sys_get_temp_dir().'/zm_filter_execute_feedback_stubs_'.getmypid();
  @mkdir($stubdir.'/includes', 0700, true);
  file_put_contents($stubdir.'/includes/Filter.php', "<?php\n");
  set_include_path($stubdir.PATH_SEPARATOR.get_include_path());

  function run_execute($status) {
    global $action, $error_message, $info_message, $redirect;
    ZM\Filter::$status = $status;
    ZM\Filter::$execute_calls = 0;
    $action = 'execute';
    $error_message = '';
    $info_message = '';
    $redirect = null;
    $_REQUEST = array('object' => 'filter', 'Id' => '5', 'filter' => array(
      'Name' => 'MyFilter',
      'Query' => array('sort_field' => 'Id', 'sort_asc' => '1', 'limit' => '', 'skip_locked' => 0, 'terms' => array()),
      'AutoCopy' => 0, 'AutoCopyTo' => 0, 'AutoMove' => 0, 'AutoMoveTo' => 0,
      'AutoArchive' => 0, 'AutoUnarchive' => 0, 'AutoVideo' => 0, 'AutoUpload' => 0,
      'AutoEmail' => 0, 'AutoMessage' => 0, 'AutoExecute' => 0, 'AutoDelete' => 0,
      'UpdateDiskSpace' => 0, 'Background' => 0, 'Concurrent' => 0, 'LockRows' => 0,
    ));
    require($GLOBALS['webroot'].'/includes/actions/filter.php');
    return array(
      'executes'   => ZM\Filter::$execute_calls,
      'error'      => $error_message,
      'info'       => $info_message,
      'redirect'   => $redirect,
    );
  }

  $r = run_execute(0);
  check('a successful execute runs the filter once', $r['executes'], 1);
  check('a successful execute reports no error', $r['error'], '');
  check('a successful execute redirects with executed=1', $r['redirect'], '?view=filter&Id=5&executed=1');

  $r = run_execute(1);
  check('a failed execute still runs the filter once', $r['executes'], 1);
  check('a failed execute redirects with executed=0', $r['redirect'], '?view=filter&Id=5&executed=0');

  @unlink($stubdir.'/includes/Filter.php');
  @rmdir($stubdir.'/includes');
  @rmdir($stubdir);
  echo "\n$passes passed, $failures failed.\n";
  exit($failures ? 1 : 0);
}
