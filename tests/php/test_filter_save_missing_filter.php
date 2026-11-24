<?php
// Save on a filter whose Id no longer exists (deleted in another tab) built an empty Filter
// from the failed load and saved it, so a new filter was inserted with the posted settings,
// audited and selected, instead of telling the user the filter is gone. Save has to refuse.
// An existing filter still saves, a new filter (no Id) still saves, and SaveAs, which always
// writes a new row from the form, is unchanged.
//
// Runs the real web/includes/actions/filter.php against a stubbed Filter.
//
// php tests/php/test_filter_save_missing_filter.php [webroot]   (webroot defaults to web/)

namespace ZM {
  class Filter {
    public static $saves = array();
    private $id = null;
    private $data = array('Name' => 'MyFilter', 'Background' => 0, 'AutoExecute' => 0);

    // Filter 5 is the only one that exists; any other id fails to load, as ZM_Object does.
    public function __construct($id = null) {
      if ($id and (int)$id === 5) $this->id = 5;
    }
    public function Id($v = null) {
      if (func_num_args()) $this->id = $v;
      return $this->id;
    }
    public function Name($v = null) { return $this->data['Name']; }
    public function Background() { return 0; }
    public function AutoExecute() { return 0; }
    public function AutoExecuteCmd() { return ''; }
    public function canEdit() { return true; }
    public function canDelete() { return true; }
    // A filter that did not load has nothing stored, so every posted field is a change.
    public function changes($new) {
      return $this->id ? array() : array('Name' => $new['Name']);
    }
    public function set($c) {}
    public function save($c) {
      self::$saves[] = $this->id === null ? 'insert' : 'update '.$this->id;
      if ($this->id === null) $this->id = 6;
      return true;
    }
    public function control($c, $s = null) {}
    public function execute() {}
    public function delete() {}
    public function get_last_error() { return ''; }
  }
  function Debug($s) {}
  function Warning($s) {}
  function Error($s) {}
  function Info($s) {}
  function AuditAction($a, $t, $id, $d) { $GLOBALS['audits'][] = array($a, $t, $id); }
}

namespace {
  $failures = 0;
  function check($name, $got, $want) {
    global $failures;
    if ($got === $want) echo "ok - $name\n";
    else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
  }

  foreach (array("FFMPEG", "UPLOAD", "EMAIL", "MESSAGE") as $opt) define("ZM_OPT_$opt", 1);
  function canView($a) { return true; }
  function validCardinal($i) { return preg_replace('/\D/', '', $i); }
  function validInt($i) { return preg_replace('/[^\-\d]/', '', $i); }
  function validStr($i) { return is_null($i) ? '' : strip_tags($i); }

  $webroot = isset($argv[1]) ? rtrim($argv[1], '/') : __DIR__.'/../../web';

  $stubdir = sys_get_temp_dir().'/zm_filter_missing_stubs_'.getmypid();
  @mkdir($stubdir.'/includes', 0700, true);
  file_put_contents($stubdir.'/includes/Filter.php', "<?php\n");
  set_include_path($stubdir.PATH_SEPARATOR.get_include_path());

  function run_action($action_name, $id) {
    global $action, $error_message, $redirect;
    ZM\Filter::$saves = array();
    $GLOBALS['audits'] = array();
    $action = $action_name;
    $error_message = '';
    $redirect = null;
    $_REQUEST = array('object' => 'filter', 'Id' => $id, 'filter' => array(
      'Name' => 'Edited',
      'Query' => array('sort_field' => 'Id', 'sort_asc' => '1', 'limit' => '', 'skip_locked' => 0, 'terms' => array()),
    ));
    require($GLOBALS['webroot'].'/includes/actions/filter.php');
    return array(ZM\Filter::$saves, $error_message, $redirect, $_REQUEST['Id']);
  }

  list($saves, $err, $redir, $id) = run_action('Save', '9');
  check('Save on a deleted filter inserts nothing', $saves, array());
  check('Save on a deleted filter is not audited', $GLOBALS['audits'], array());
  check('Save on a deleted filter reports that it is gone', $err, 'Filter not found.<br/>');
  check('Save on a deleted filter does not redirect', $redir, null);
  check('Save on a deleted filter keeps the requested Id', (string)$id, '9');

  list($saves, $err, $redir, $id) = run_action('SaveAs', '9');
  check('SaveAs still writes a copy from the form', $saves, array('insert'));
  check('SaveAs reports no error', $err, '');

  list($saves, $err, $redir, $id) = run_action('Save', '5');
  check('Save on an existing filter updates nothing when unchanged', $saves, array());
  check('Save on an existing filter reports no error', $err, '');
  check('Save on an existing filter stays on it', $redir, '?view=filter&Id=5');

  list($saves, $err, $redir, $id) = run_action('Save', '');
  check('Save without an Id inserts a new filter', $saves, array('insert'));
  check('Save without an Id selects the new filter', (string)$id, '6');
  check('Save without an Id reports no error', $err, '');

  @unlink($stubdir.'/includes/Filter.php');
  @rmdir($stubdir.'/includes');
  @rmdir($stubdir);
  echo $failures ? "\n$failures failed\n" : "\nall passed\n";
  exit($failures ? 1 : 0);
}
