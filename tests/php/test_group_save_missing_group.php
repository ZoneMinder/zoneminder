<?php
// Saving a group whose gid no longer exists (deleted in another tab) built an empty
// Group from the failed load and saved it, so a new group was inserted with the posted
// name and monitors and the save was audited as an update. It has to refuse and report
// that the group is gone, while an existing group and a new group still save.
//
// Runs the real web/includes/actions/group.php against a stubbed Group and database.
//
// php tests/php/test_group_save_missing_group.php [action-file]

namespace ZM {
  function Warning($s) {}
  function Debug($s) {}
  function Error($s) {}

  class Rec {
    public static $queries = array();
    public static $audit = array();
    public static $saves = array();
  }

  function AuditAction($action = null, $type = null, $id = null, $note = null) {
    Rec::$audit[] = array($action, $type, $id, $note);
  }

  class Group {
    private $id = null;
    // Group 3 is the only one that exists; any other id fails to load, as ZM_Object does.
    public function __construct($id = null) {
      if ($id !== null and $id !== '' and (int)$id === 3) $this->id = 3;
    }
    public static function validParent($group_id, $parent_id) { return true; }
    public static function canEditMembership($monitor_ids) { return true; }
    public function Id($v = null) {
      if ($v !== null) $this->id = $v;
      return $this->id;
    }
    public function ParentId() { return null; }
    public function MonitorIds() { return array(); }
    public function save($values) {
      Rec::$saves[] = $this->id === null ? 'insert' : 'update '.$this->id;
      if (!$this->id) $this->id = 42;
      return true;
    }
    public function get_last_error() { return ''; }
  }
}

namespace {
  function canEdit($p, $id = null) { return true; }
  function validHtmlStr($v) { return htmlspecialchars((string)$v, ENT_QUOTES); }
  // Copied from web/includes/functions.php.
  function validCardinal($input) {
    if (!is_scalar($input)) return '';
    return preg_replace('/\D/', '', $input);
  }
  function dbFetchAll($sql, $col = null, $args = array()) { return array(); }
  function dbQuery($sql, $args = array()) {
    ZM\Rec::$queries[] = array($sql, $args);
    return true;
  }

  $action_file = isset($argv[1]) ? $argv[1] : __DIR__.'/../../web/includes/actions/group.php';
  if (!preg_match('#^/#', $action_file)) $action_file = getcwd().'/'.$action_file;
  if (!is_file($action_file)) {
    fwrite(STDERR, "no such action file: $action_file\n");
    exit(1);
  }

  $failures = 0;
  $passes = 0;
  function check($name, $got, $want = true) {
    global $failures, $passes;
    if ($got === $want) {
      $passes++;
      echo "ok - $name\n";
    } else {
      $failures++;
      echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n";
    }
  }

  function run_action($action_file, $request) {
    ZM\Rec::$queries = array();
    ZM\Rec::$audit = array();
    ZM\Rec::$saves = array();
    $GLOBALS['error_message'] = '';
    $_REQUEST = $request;
    $action = 'save';
    $redirect = 'SENTINEL';
    $view = 'groups';

    ob_start();
    include $action_file;
    ob_end_clean();

    return array($GLOBALS['error_message'], $redirect);
  }

  $posted = array('Name' => 'Front', 'MonitorIds' => array('1', '2'));

  list($msg, $redirect) = run_action($action_file, array('gid' => '9', 'newGroup' => $posted));
  check('a deleted group is not inserted again', ZM\Rec::$saves, array());
  check('a deleted group changes no membership', count(ZM\Rec::$queries), 0);
  check('a deleted group is not audited', count(ZM\Rec::$audit), 0);
  check('a deleted group does not redirect', $redirect, 'SENTINEL');
  check('a deleted group reports that it is gone', $msg, 'Group not found.<br/>');

  list($msg, $redirect) = run_action($action_file, array('gid' => '3', 'newGroup' => $posted));
  check('an existing group is updated', ZM\Rec::$saves, array('update 3'));
  check('an existing group reports no error', $msg, '');
  check('an existing group redirects to the groups view', $redirect, '?view=groups');

  list($msg, $redirect) = run_action($action_file, array('newGroup' => $posted));
  check('a new group is inserted', ZM\Rec::$saves, array('insert'));
  check('a new group is audited as a create', ZM\Rec::$audit, array(array('create', 'group', 42, 'Name: Front')));
  check('a new group reports no error', $msg, '');

  list($msg, $redirect) = run_action($action_file, array('gid' => '0', 'newGroup' => $posted));
  check('gid 0 is a new group', ZM\Rec::$saves, array('insert'));

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
