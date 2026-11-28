<?php
// Saving an existing filter stops its zmfilter.pl daemon so that it picks up
// the new settings; a Background filter is started again afterwards. The stop
// used to be issued before save(), so a save that failed still stopped the
// daemon and left the old settings in the database with nothing running them.
// control('stop') must only happen once save() has succeeded.
//
// Run as: php tests/php/test_filter_save_failure_keeps_daemon.php [webroot]
// webroot defaults to this worktree's web/ directory.

namespace ZM {
  class Filter {
    // Data of the filter being edited, as the editor would have loaded it.
    public static $data = array();
    public static $save_calls = 0;
    public static $saved_data = null;
    public static $last_error = '';
    public static $control_calls = array();
    public static $save_succeeds = true;

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
    // stored ones are returned.
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
      // save() and control() append here so the test can assert that the
      // daemon is stopped after the save, not before it.
      $GLOBALS['zm_call_order'][] = 'save';
      if (!self::$save_succeeds) {
        self::$last_error = 'Simulated failure writing the filter row';
        return false;
      }
      $this->set($c);
      self::$saved_data = self::$data;
      return true;
    }
    public function control($c, $s = null) {
      self::$control_calls[] = $c;
      $GLOBALS['zm_call_order'][] = 'control:'.$c;
    }
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
  $stubdir = sys_get_temp_dir().'/zm_filter_save_failure_stubs_'.getmypid();
  @mkdir($stubdir.'/includes', 0700, true);
  file_put_contents($stubdir.'/includes/Filter.php', "<?php\n");
  set_include_path($stubdir.PATH_SEPARATOR.get_include_path());

  // The fields the filter editor submits and the action normalises, for a
  // Background filter. The stored row is the same except for the name, so a
  // Save of this POST has changes to write.
  function filter_post($name = 'MyFilter') {
    return array(
      'Name'   => $name,
      'Query'  => array('sort_field'=>'Id', 'sort_asc'=>'1', 'limit'=>'', 'skip_locked'=>0, 'terms'=>array()),
      'AutoCopy'=>0, 'AutoCopyTo'=>0, 'AutoMove'=>0, 'AutoMoveTo'=>0,
      'AutoArchive'=>0, 'AutoUnarchive'=>0, 'AutoVideo'=>0, 'AutoUpload'=>0,
      'AutoEmail'=>0, 'AutoMessage'=>0, 'AutoExecute'=>0, 'AutoDelete'=>0,
      'UpdateDiskSpace'=>0, 'Background'=>1, 'Concurrent'=>0, 'LockRows'=>0,
    );
  }

  function run_action($action_name, $stored, $post, $save_succeeds) {
    global $action, $error_message, $redirect;
    ZM\Filter::$data = $stored;
    ZM\Filter::$save_calls = 0;
    ZM\Filter::$saved_data = null;
    ZM\Filter::$last_error = '';
    ZM\Filter::$control_calls = array();
    ZM\Filter::$save_succeeds = $save_succeeds;
    $GLOBALS['zm_call_order'] = array();

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
      'order'    => $GLOBALS['zm_call_order'],
    );
  }

  // 1. Save of an existing Background filter with changes, where save()
  //    returns false: the daemon must be left untouched and the error shown.
  $r = run_action('Save', filter_post(), filter_post('MyFilter Renamed'), false);
  check('failed Save attempts the save once', $r['saves'], 1);
  check('failed Save never stops the daemon', $r['controls'], array());
  check('failed Save reports the save error', $r['error'] !== '', true);
  check('failed Save reports the filter last_error', $r['error'], 'Simulated failure writing the filter row');
  check('failed Save does not redirect', $r['redirect'], null);
  check('failed Save reaches only the save, no control calls', $r['order'], array('save'));

  // 2. The same Save when save() succeeds: the daemon is stopped exactly
  //    once, after the save, and then started again for a Background filter.
  $r = run_action('Save', filter_post(), filter_post('MyFilter Renamed'), true);
  check('successful Save saves once', $r['saves'], 1);
  check('successful Save saves the new name', $r['saved']['Name'] ?? null, 'MyFilter Renamed');
  check('successful Save stops the daemon exactly once then starts it', $r['controls'], array('stop', 'start'));
  check('successful Save stops the daemon after the save, then starts it', $r['order'], array('save', 'control:stop', 'control:start'));
  check('successful Save reports no error', $r['error'], '');
  check('successful Save redirects back to the filter', $r['redirect'], '?view=filter&Id=5');

  @unlink($stubdir.'/includes/Filter.php');
  @rmdir($stubdir.'/includes');
  @rmdir($stubdir);
  echo "\n$passes passed, $failures failed.\n";
  exit($failures ? 1 : 0);
}
