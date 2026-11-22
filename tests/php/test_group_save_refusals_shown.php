<?php
// The group modal posts view=group, which has no view file. When the group save action
// refused a save (a parent that is the group itself or one of its children, or a monitor
// moved in or out that the user can't edit) it only logged a warning and returned, so
// index.php found no view "group" and sent the user to the console with no message; a
// failed save() set $error_message but was lost the same way. The action now sets the
// message and shows the Groups page.
//
// php tests/php/test_group_save_refusals_shown.php [action-file]

namespace ZM {
  function Warning($s) {}
  function Debug($s) {}
  function Error($s) {}
  function AuditAction() {}

  class Rec {
    public static $valid_parent = true;
    public static $can_edit_membership = true;
    public static $save_result = true;
  }

  class Group {
    private $id;
    public function __construct($id = null) { $this->id = ($id === null or $id === '') ? null : (int)$id; }
    public static function validParent($group_id, $parent_id) { return Rec::$valid_parent; }
    public static function canEditMembership($monitor_ids) { return Rec::$can_edit_membership; }
    public function Id() { return $this->id; }
    public function ParentId() { return null; }
    public function MonitorIds() { return array(); }
    public function save($values) {
      if (!Rec::$save_result) return false;
      if (!$this->id) $this->id = 42;
      return true;
    }
    public function get_last_error() { return 'Data too long for column Name'; }
  }
}

namespace {
  function canEdit($p, $id = null) { return true; }
  function validHtmlStr($v) { return htmlspecialchars((string)$v, ENT_QUOTES); }
  function dbFetchAll($sql, $col = null, $args = array()) { return array(); }
  function dbQuery($sql, $args = array()) { return true; }

  $action_file = isset($argv[1]) ? $argv[1] : __DIR__.'/../../web/includes/actions/group.php';

  $failures = 0;
  function check($name, $got, $want) {
    global $failures;
    if ($got === $want) echo "ok - $name\n";
    else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
  }

  function run($valid_parent, $can_edit_membership, $save_result) {
    global $action_file, $action, $error_message, $redirect, $view;
    ZM\Rec::$valid_parent = $valid_parent;
    ZM\Rec::$can_edit_membership = $can_edit_membership;
    ZM\Rec::$save_result = $save_result;
    $action = 'save';
    $view = 'group';
    $error_message = '';
    $redirect = null;
    $_REQUEST = array('action' => 'save', 'gid' => '5',
      'newGroup' => array('Name' => 'Front', 'ParentId' => '', 'MonitorIds' => array('7')));
    include $action_file;
    return array($error_message, $redirect, $view);
  }

  list($err, $redir, $v) = run(true, true, true);
  check('a save that works redirects to Groups', $redir, '?view=groups');

  list($err, $redir, $v) = run(false, true, true);
  check('an invalid parent is reported', $err !== '', true);
  check('an invalid parent shows the Groups page', array($redir, $v), array(null, 'groups'));

  list($err, $redir, $v) = run(true, false, true);
  check('a refused monitor move is reported', $err !== '', true);
  check('a refused monitor move shows the Groups page', array($redir, $v), array(null, 'groups'));

  list($err, $redir, $v) = run(true, true, false);
  check('a failed save is reported', strpos($err, 'Data too long') !== false, true);
  check('a failed save shows the Groups page', array($redir, $v), array(null, 'groups'));

  echo $failures ? "\n$failures failed\n" : "\nall passed\n";
  exit($failures ? 1 : 0);
}
