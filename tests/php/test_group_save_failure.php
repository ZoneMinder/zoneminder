<?php
// Runs the real web/includes/actions/group.php save action against a stubbed
// Group and database to check that a rejected save is reported and stops the
// action:
//
// ZM_Object::save() returns false and sets get_last_error() when the database
// rejects the row (e.g. a Name over Groups.Name's varchar(64) under strict SQL
// mode). The group action ignored that result and carried on, so a failed
// update still replaced Groups_Monitors (membership applied, rename lost), a
// failed create audited a null Id and added no monitors, and both redirected as
// if the save had worked.
//
// php tests/php/test_group_save_failure.php [action-file]
//
// The optional action-file argument lets the test run against another copy of
// the action, e.g. `git show HEAD:web/includes/actions/group.php > /tmp/group.php`.

namespace ZM {
  function Warning($s) {}
  function Debug($s) {}
  function Error($s) {}

  class Rec {
    public static $queries = array();
    public static $audit = array();
    public static $save_result = true;
    public static $last_error = '';
    public static $rows = array();
  }

  function AuditAction($action = null, $type = null, $id = null, $note = null) {
    Rec::$audit[] = array($action, $type, $id, $note);
  }

  class Group {
    private $id = null;
    private $parent = null;
    public function __construct($id = null) {
      $this->id = ($id === null or $id === '') ? null : (int)$id;
      if ($this->id) {
        $this->parent = isset(Rec::$rows[$this->id]) ? Rec::$rows[$this->id] : null;
      }
    }
    public static function validParent($group_id, $parent_id) { return true; }
    public static function canEditMembership($monitor_ids) { return true; }
    public function Id($v = null) {
      if ($v !== null) $this->id = $v;
      return $this->id;
    }
    public function ParentId() { return $this->parent; }
    public function MonitorIds() { return array(); }
    public function save($values) {
      if (!Rec::$save_result) return false;
      if (!$this->id) $this->id = 42;
      return true;
    }
    public function get_last_error() { return Rec::$last_error; }
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

  // Runs the action. $request is $_REQUEST, $rows are the existing groups.
  function run_action($action_file, $request, $save_result, $last_error = '', $rows = array()) {
    ZM\Rec::$queries = array();
    ZM\Rec::$audit = array();
    ZM\Rec::$save_result = $save_result;
    ZM\Rec::$last_error = $last_error;
    ZM\Rec::$rows = $rows;

    $GLOBALS['error_message'] = '';
    $_REQUEST = $request;
    $action = 'save';
    $redirect = 'SENTINEL';

    ob_start();
    include $action_file;
    ob_end_clean();

    return array($GLOBALS['error_message'], $redirect);
  }

  $membership = function() {
    $out = array();
    foreach (ZM\Rec::$queries as $q) {
      if (strpos($q[0], 'Groups_Monitors') !== false) $out[] = $q;
    }
    return $out;
  };

  // A rejected update must not touch membership, audit or redirect.
  $error = 'SQLSTATE[22001]: Data too long for column <Name>';
  list($msg, $redirect) = run_action($action_file,
    array('gid' => '3', 'newGroup' => array('Name' => str_repeat('x', 80), 'MonitorIds' => array('1', '2'))),
    false, $error, array(3 => null));
  $q = $membership();
  check('a rejected update runs no Groups_Monitors query', count($q), 0);
  check('a rejected update audits nothing', count(ZM\Rec::$audit), 0);
  check('a rejected update does not redirect', $redirect, 'SENTINEL');
  check('a rejected update reports a non-empty error', $msg !== '', true);
  check('the error carries the database message', strpos($msg, 'Data too long') !== false, true);
  check('the error is html escaped', strpos($msg, '&lt;Name&gt;') !== false, true);

  // A rejected create must not audit a null id or add monitors either.
  list($msg, $redirect) = run_action($action_file,
    array('newGroup' => array('Name' => str_repeat('x', 80), 'MonitorIds' => array('1'))),
    false, 'Data truncated for column Name');
  check('a rejected create runs no Groups_Monitors query', count($membership()), 0);
  check('a rejected create audits nothing', count(ZM\Rec::$audit), 0);
  check('a rejected create does not redirect', $redirect, 'SENTINEL');
  check('a rejected create explains why', strpos($msg, 'Data truncated') !== false, true);

  // A successful update still replaces membership and redirects.
  list($msg, $redirect) = run_action($action_file,
    array('gid' => '3', 'newGroup' => array('Name' => 'Front', 'MonitorIds' => array('1', '2'))),
    true, '', array(3 => null));
  $q = $membership();
  check('a saved update still clears membership once',
    count($q) === 3 && strpos($q[0][0], 'DELETE FROM `Groups_Monitors`') !== false, true);
  check('a saved update inserts each monitor',
    count($q) === 3 && $q[1][1] === array(3, 1) && $q[2][1] === array(3, 2), true);
  check('a saved update audits the update',
    ZM\Rec::$audit === array(array('update', 'group', 3, 'Name: Front')), true);
  check('a saved update redirects to the groups view', $redirect, '?view=groups');
  check('a saved update reports no error', $msg, '');

  // And a successful create adds monitors and audits the new id.
  list($msg, $redirect) = run_action($action_file,
    array('newGroup' => array('Name' => 'Back', 'MonitorIds' => array('4'))),
    true);
  $q = $membership();
  check('a saved create inserts its monitors',
    count($q) === 2 && $q[1][1] === array(42, 4), true);
  check('a saved create audits the new id',
    ZM\Rec::$audit === array(array('create', 'group', 42, 'Name: Back')), true);
  check('a saved create redirects to the groups view', $redirect, '?view=groups');
  check('a saved create reports no error', $msg, '');

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
