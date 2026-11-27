<?php
// Saving a group with an empty, whitespace-only, missing or array
// newGroup[Name] must be rejected before ZM\Group::save() is called
// (error message set, view back to 'groups'), and a padded name must be
// saved trimmed.
//
// Run as: php tests/php/test_group_save_empty_name.php [webroot]
// webroot defaults to this worktree's web/ directory.

namespace ZM {
  class Group {
    public static $save_calls = 0;
    public static $saved_data = null;
    public static $last_error = '';
    public static $next_id = 7;

    private $id;

    public function __construct($id = null) {
      $this->id = $id ? (int)$id : null;
    }
    public function Id($v = null) {
      if (func_num_args()) $this->id = $v;
      return $this->id;
    }
    public function ParentId() { return null; }
    public function MonitorIds() { return array(); }
    public function save($changes) {
      self::$save_calls++;
      self::$saved_data = $changes;
      if (!$this->id) $this->id = self::$next_id;
      return true;
    }
    public function get_last_error() { return self::$last_error; }
    public static function validParent($id, $parent) { return true; }
    public static function canEditMembership($monitor_ids) { return true; }
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

  // The pre-fix action reads $_REQUEST['newGroup']['Name'] without a guard,
  // which raises an undefined-index warning on the missing-name case.
  error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

  function canEdit($area) { return true; }
  function dbFetchAll($sql, $col = false, $params = array()) { return array(); }
  function dbQuery($sql, $params = array()) { return true; }
  function dbLastError() { return ''; }
  function validHtmlStr($s) { return htmlspecialchars((string)$s); }

  $webroot = isset($argv[1]) ? rtrim($argv[1], '/') : __DIR__.'/../../web';

  // Run the save action with the given newGroup POST fields. A truthy $gid
  // edits an existing group (exercising the dbFetchAll of old monitor ids);
  // null creates a new one.
  function run_action($new_group, $gid = '3') {
    global $action, $error_message, $view, $redirect;
    ZM\Group::$save_calls = 0;
    ZM\Group::$saved_data = null;
    ZM\Group::$last_error = '';
    ZM\Group::$next_id = 7;

    $action = 'save';
    $error_message = '';
    $view = null;
    $redirect = null;
    $_REQUEST = array('object' => 'group', 'newGroup' => $new_group);
    if ($gid) $_REQUEST['gid'] = $gid;
    require($GLOBALS['webroot'].'/includes/actions/group.php');
    return array(
      'saves'    => ZM\Group::$save_calls,
      'saved'    => ZM\Group::$saved_data,
      'error'    => $error_message,
      'view'     => $view,
      'redirect' => $redirect,
    );
  }

  // 1. Whitespace-only name on an existing group must not be saved.
  $r = run_action(array('Name' => '   ', 'ParentId' => ''));
  check('whitespace-only name does not save', $r['saves'], 0);
  check('whitespace-only name reports an error', $r['error'] !== '', true);
  check('whitespace-only name returns to groups view', $r['view'], 'groups');
  check('whitespace-only name does not redirect', $r['redirect'], null);

  // 2. Empty name on a new group must not be saved.
  $r = run_action(array('Name' => '', 'ParentId' => ''), null);
  check('empty name does not save', $r['saves'], 0);
  check('empty name reports an error', $r['error'] !== '', true);
  check('empty name returns to groups view', $r['view'], 'groups');
  check('empty name does not redirect', $r['redirect'], null);

  // 3. A missing Name field must not be saved either.
  $r = run_action(array('ParentId' => ''), null);
  check('missing name does not save', $r['saves'], 0);
  check('missing name reports an error', $r['error'] !== '', true);
  check('missing name returns to groups view', $r['view'], 'groups');
  check('missing name does not redirect', $r['redirect'], null);

  // 4. An array Name (crafted POST) must not be saved.
  $r = run_action(array('Name' => array('x' => 'y'), 'ParentId' => ''));
  check('array name does not save', $r['saves'], 0);
  check('array name reports an error', $r['error'] !== '', true);
  check('array name returns to groups view', $r['view'], 'groups');
  check('array name does not redirect', $r['redirect'], null);

  // 5. A padded real name must be saved once, trimmed.
  $r = run_action(array('Name' => '  Front door ', 'ParentId' => ''));
  check('padded name saves once', $r['saves'], 1);
  check('padded name is saved trimmed', $r['saved']['Name'] ?? null, 'Front door');
  check('padded name saves the parent', $r['saved']['ParentId'] ?? null, null);
  check('padded name reports no error', $r['error'], '');
  check('padded name redirects to groups', $r['redirect'], '?view=groups');

  echo "\n$passes passed, $failures failed.\n";
  exit($failures ? 1 : 0);
}
