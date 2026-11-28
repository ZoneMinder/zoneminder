<?php
// Saving a filter keeps the AutoTag action and its tag only when the tag is
// an existing tag's id. Anything else (no such tag, not a number, an array)
// saves the action switched off with no tag, so zmfilter never runs it.
//
// Run as: php tests/php/test_filter_autotag_save.php [webroot]
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
    public static $execute_calls = 0;

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
      $this->set($c);
      self::$saved_data = self::$data;
      if (self::$assign_new_id) $this->id = self::$new_id;
      return true;
    }
    public function control($c, $s = null) { self::$control_calls[] = $c; }
    public function execute() { self::$execute_calls++; }
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
  // Tag 7 exists, no other does
  function dbFetchOne($sql, $col = false, $params = array()) {
    if (preg_match('/FROM Tags WHERE Id=\?/', $sql)) return ($params[0] ?? null) == 7 ? 7 : false;
    return false;
  }

  $webroot = isset($argv[1]) ? rtrim($argv[1], '/') : __DIR__.'/../../web';

  // actions/filter.php requires includes/Filter.php; the stub class above stands in.
  $stubdir = sys_get_temp_dir().'/zm_filter_autotag_save_stubs_'.getmypid();
  @mkdir($stubdir.'/includes', 0700, true);
  file_put_contents($stubdir.'/includes/Filter.php', "<?php\n");
  set_include_path($stubdir.PATH_SEPARATOR.get_include_path());

  // The fields the filter editor submits and the action normalises.
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
    ZM\Filter::$execute_calls = 0;
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
      'executes' => ZM\Filter::$execute_calls,
    );
  }

  function saved_tag($post_tag, $post_id) {
    $post = filter_post('Tagger');
    if ($post_tag !== null) $post['AutoTag'] = $post_tag;
    if ($post_id !== null) $post['AutoTagId'] = $post_id;
    // Stored with a different tag, so every outcome is a change the save writes
    $stored = filter_post('Tagger') + array('AutoTag'=>1, 'AutoTagId'=>'3');
    $r = run_action('Save', $stored, $post);
    return array((int)($r['saved']['AutoTag'] ?? -1), (string)($r['saved']['AutoTagId'] ?? 'unset'));
  }

  check('an existing tag is saved with the action on', saved_tag('1', '7'), array(1, '7'));
  check('a tag that does not exist turns the action off', saved_tag('1', '99'), array(0, '0'));
  check('a tag id that is not a number turns the action off', saved_tag('1', '7 OR 1=1'), array(0, '0'));
  check('a tag id sent as an array turns the action off', saved_tag('1', array('7')), array(0, '0'));
  check('no tag chosen turns the action off', saved_tag('1', ''), array(0, '0'));
  check('the action unticked is saved off', saved_tag(null, '7'), array(0, '7'));

  @unlink($stubdir.'/includes/Filter.php');
  @rmdir($stubdir.'/includes');
  @rmdir($stubdir);
  echo "\n$passes passed, $failures failed.\n";
  exit($failures ? 1 : 0);
}
