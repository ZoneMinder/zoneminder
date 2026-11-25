<?php
// Deleting groups from the Groups view used to set $redirect even when a group was
// skipped for lack of monitor edit permission or its delete failed. index.php then
// redirected before the Groups page could render, so $error_message was never shown
// and the group appeared with no reason. The action now reports a refused group by
// name and leaves the Groups page rendered with the errors; only a clean run redirects.
//
// php tests/php/test_groups_delete_errors_shown.php [action-file]

namespace ZM {
  function Warning($s) {}
  function AuditAction($a, $b, $id, $d = null) { $GLOBALS['audit_calls'][] = array($a, $b, $id); }

  class Rec {
    public static $can_edit_membership = true;
    public static $delete_result = true;
    public static $groups = array();
  }

  class Group {
    private $id;
    private $name;
    public function __construct($id, $name) { $this->id = $id; $this->name = $name; }
    public function Id() { return $this->id; }
    public function Name() { return $this->name; }
    public function MonitorIds() { return array(); }
    public function delete() { return Rec::$delete_result; }
    public static function find($p = array(), $o = array()) { return Rec::$groups; }
    public static function canEditMembership($ids) { return Rec::$can_edit_membership; }
  }
}

namespace {
  function canEdit($p) { return true; }
  function canView($p) { return true; }
  function validHtmlStr($v) { return htmlspecialchars((string)$v, ENT_QUOTES); }
  function dbLastError() { return 'boom'; }

  $action_file = isset($argv[1]) ? $argv[1] : __DIR__.'/../../web/includes/actions/groups.php';

  $failures = 0;
  function check($name, $got, $want) {
    global $failures;
    if ($got === $want) echo "ok - $name\n";
    else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
  }

  function run($groups, $can_edit_membership, $delete_result) {
    global $action_file, $action, $error_message, $redirect, $refreshParent;
    ZM\Rec::$groups = $groups;
    ZM\Rec::$can_edit_membership = $can_edit_membership;
    ZM\Rec::$delete_result = $delete_result;
    $GLOBALS['audit_calls'] = array();
    $action = 'delete';
    $error_message = '';
    $redirect = null;
    $refreshParent = false;
    $_REQUEST = array('gid' => '5');
    include $action_file;
    return array($error_message, $redirect, $GLOBALS['audit_calls']);
  }

  list($err, $redir, $audit) = run(array(new ZM\Group(5, 'Front <Door>')), false, true);
  check('a refused group is named in the error', strpos($err, 'Front &lt;Door&gt;') !== false, true);
  check('a refused group does not redirect', $redir, null);
  check('a refused group is not deleted or audited', $audit, array());

  list($err, $redir, $audit) = run(array(new ZM\Group(5, 'Front')), true, false);
  check('a failed delete is reported', strpos($err, 'Error deleting group') !== false, true);
  check('a failed delete does not redirect', $redir, null);
  check('a failed delete is not audited', $audit, array());

  list($err, $redir, $audit) = run(array(new ZM\Group(5, 'Front')), true, true);
  check('a successful delete leaves no error', $err, '');
  check('a successful delete redirects to Groups', $redir, '?view=groups');
  check('a successful delete is audited once', count($audit), 1);

  list($err, $redir, $audit) = run(array(new ZM\Group(5, 'Front'), new ZM\Group(6, 'Back')), false, true);
  check('a mixed run keeps the refusal message', strpos($err, 'Front') !== false, true);
  check('a mixed run does not redirect', $redir, null);

  echo $failures ? "\n$failures failed\n" : "\nall passed\n";
  exit($failures ? 1 : 0);
}
