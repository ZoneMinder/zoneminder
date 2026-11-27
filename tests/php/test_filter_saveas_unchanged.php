<?php
// "Save As" on a filter the user has not modified must still create a copy.
// The whole save block in actions/filter.php used to be inside
// `if (count($changes))`, so an unchanged SaveAs did nothing: no new row, no
// error, and the redirect went back to the original Id (starting it again if
// Background was set).
//
// Run as: php tests/php/test_filter_saveas_unchanged.php [webroot]
// webroot defaults to this worktree's web/ directory.

namespace ZM {
  class Filter {
    // Data of the filter being edited, as the editor would have loaded it.
    public static $data = array();
    public static $new_id = 6;
    public static $assign_new_id = true;
    public static $save_calls = 0;
    public static $saved_data = null;
    public static $last_error = '';
    public static $control_calls = array();

    private $id;

    public function __construct($id = null) {
      $this->id = $id ? (int)$id : null;
    }
    public function Id($v = null) {
      if (func_num_args()) $this->id = $v;
      return $this->id;
    }
    public function Name($v = null) {
      if (func_num_args()) self::$data['Name'] = $v;
      return self::$data['Name'];
    }
    public function Background() { return self::$data['Background']; }
    public function AutoExecute() { return self::$data['AutoExecute']; }
    public function AutoExecuteCmd() { return ''; }
    public function canEdit() { return true; }
    public function canDelete() { return true; }

    // Same shape as ZM_Object::changes(): only values that differ from the
    // stored ones are returned, so an untouched editor POST yields array().
    public function changes($new) {
      $changes = array();
      foreach ($new as $field => $value) {
        if (is_array($value)) {
          if ((self::$data[$field] ?? null) != $value) $changes[$field] = $value;
        } else if ((self::$data[$field] ?? null) != $value) {
          $changes[$field] = $value;
        }
      }
      return $changes;
    }
    public function set($c) {
      foreach ($c as $field => $value) self::$data[$field] = $value;
    }
    public function save($c) {
      self::$save_calls++;
      $this->set($c);
      self::$saved_data = self::$data;
      if (self::$assign_new_id) $this->id = self::$new_id;
      return true;
    }
    public function control($c, $s = null) { self::$control_calls[] = $c; }
    public function execute() {}
    public function delete() {}
    public function get_last_error() { return self::$last_error; }
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
    if ($got === $want) { $passes++; echo "ok - $name\n"; }
    else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
  }

  foreach (array("FFMPEG", "UPLOAD", "EMAIL", "MESSAGE") as $opt) define("ZM_OPT_$opt", 1);
  function canView($a) { return true; }
  function validCardinal($i) { return preg_replace('/\D/', '', $i); }
  function validInt($i) { return preg_replace('/[^\-\d]/', '', $i); }
  function validStr($i) { return is_null($i) ? '' : strip_tags($i); }

  $webroot = isset($argv[1]) ? rtrim($argv[1], '/') : __DIR__.'/../../web';

  // actions/filter.php requires includes/Filter.php; the stub class above stands in.
  $stubdir = sys_get_temp_dir().'/zm_filter_saveas_stubs_'.getmypid();
  @mkdir($stubdir.'/includes', 0700, true);
  file_put_contents($stubdir.'/includes/Filter.php', "<?php\n");
  set_include_path($stubdir.PATH_SEPARATOR.get_include_path());

  // An untouched editor POST: these are the fields the view submits and the
  // action normalises. ZM\Filter::$data holds exactly the same values, so
  // changes() comes back empty.
  function filter_post($name = 'MyFilter') {
    return array(
      'Name'   => $name,
      'Query'  => array('sort_field'=>'Id', 'sort_asc'=>'1', 'limit'=>'', 'skip_locked'=>0, 'terms'=>array()),
      'AutoCopy'=>0, 'AutoCopyTo'=>0, 'AutoMove'=>0, 'AutoMoveTo'=>0,
      'AutoArchive'=>0, 'AutoUnarchive'=>0, 'AutoVideo'=>0, 'AutoUpload'=>0,
      'AutoEmail'=>0, 'AutoMessage'=>0, 'AutoExecute'=>0, 'AutoDelete'=>0,
      'UpdateDiskSpace'=>0, 'Background'=>0, 'Concurrent'=>0, 'LockRows'=>0,
    );
  }

  function run_action($action_name, $stored, $post, $assign_new_id = true) {
    global $action, $error_message, $redirect;
    ZM\Filter::$data = $stored;
    ZM\Filter::$assign_new_id = $assign_new_id;
    ZM\Filter::$save_calls = 0;
    ZM\Filter::$saved_data = null;
    ZM\Filter::$control_calls = array();
    ZM\Filter::$new_id = 6;

    $action = $action_name;
    $error_message = '';
    $redirect = null;
    $_REQUEST = array('object'=>'filter', 'Id'=>'5', 'filter'=>$post);
    require($GLOBALS['webroot'].'/includes/actions/filter.php');
    return array(
      'saves'    => ZM\Filter::$save_calls,
      'saved'    => ZM\Filter::$saved_data,
      'error'    => $error_message,
      'redirect' => $redirect,
      'id'       => $_REQUEST['Id'],
      'controls' => ZM\Filter::$control_calls,
    );
  }

  // 1. Unchanged SaveAs must insert a copy and select the new Id.
  $r = run_action('SaveAs', filter_post(), filter_post());
  check('unchanged SaveAs saves a copy', $r['saves'], 1);
  check('unchanged SaveAs redirects to the new Id', $r['redirect'], '?view=filter&Id=6');
  check('unchanged SaveAs selects the new Id', (string)$r['id'], '6');
  check('copy keeps the filter settings', $r['saved']['Name'] ?? null, 'MyFilter');
  check('copy keeps the query', $r['saved']['Query']['terms'] ?? null, array());
  check('unchanged SaveAs reports no error', $r['error'], '');

  // 2. Unchanged plain Save must not write anything.
  $r = run_action('Save', filter_post(), filter_post());
  check('unchanged Save saves nothing', $r['saves'], 0);
  check('unchanged Save stays on the original Id', $r['redirect'], '?view=filter&Id=5');
  check('unchanged Save reports no error', $r['error'], '');

  // 3. A changed SaveAs still saves the new values as a copy.
  $r = run_action('SaveAs', filter_post(), filter_post('Renamed'));
  check('changed SaveAs saves a copy', $r['saves'], 1);
  check('changed SaveAs redirects to the new Id', $r['redirect'], '?view=filter&Id=6');
  check('changed SaveAs keeps the new name', $r['saved']['Name'] ?? null, 'Renamed');

  // 4. SaveAs that produces no new Id must report an error and not redirect
  //    back to the original filter.
  $r = run_action('SaveAs', filter_post(), filter_post(), false);
  check('SaveAs without a new Id still calls save once', $r['saves'], 1);
  check('SaveAs without a new Id reports an error', strpos($r['error'], 'Failed to save a copy') !== false, true);
  check('SaveAs without a new Id does not redirect', $r['redirect'], null);

  @unlink($stubdir.'/includes/Filter.php');
  @rmdir($stubdir.'/includes');
  @rmdir($stubdir);
  echo "\n$passes passed, $failures failed.\n";
  exit($failures ? 1 : 0);
}
