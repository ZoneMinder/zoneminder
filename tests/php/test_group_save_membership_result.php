<?php
// A group save replaced Groups_Monitors with a DELETE and one INSERT per monitor
// and ignored every result, so a failed INSERT (e.g. a monitor deleted meanwhile)
// silently dropped members and the save still audited and redirected as success.
// A failed DELETE or INSERT is now reported and does not redirect.
//
// php tests/php/test_group_save_membership_result.php [action-file]

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
    public static $fail = array();
    public static $fail_mid = null;
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
  function dbLastError() { return 'fk <fail>'; }
  function dbQuery($sql, $args = array()) {
    ZM\Rec::$queries[] = array($sql, $args);
    foreach (ZM\Rec::$fail as $needle) {
      if (strpos($sql, $needle) === 0) {
        if ($needle !== 'INSERT' or ZM\Rec::$fail_mid === null or $args[1] === ZM\Rec::$fail_mid) return null;
      }
    }
    return true;
  }

  $action_file = isset($argv[1]) ? $argv[1] : __DIR__.'/../../web/includes/actions/group.php';
  if (!preg_match('#^/#', $action_file)) $action_file = getcwd().'/'.$action_file;
  if (!is_file($action_file)) { fwrite(STDERR, "no such action file: $action_file\n"); exit(1); }

  $failures = 0;
  function check($name, $got, $want = true) {
    global $failures;
    if ($got === $want) echo "ok - $name\n";
    else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
  }

  function run_action($action_file, $request, $fail, $fail_mid = null) {
    ZM\Rec::$queries = array();
    ZM\Rec::$audit = array();
    ZM\Rec::$save_result = true;
    ZM\Rec::$rows = array(3 => null);
    ZM\Rec::$fail = $fail;
    ZM\Rec::$fail_mid = $fail_mid;
    $GLOBALS['error_message'] = '';
    $_REQUEST = $request;
    $action = 'save';
    $redirect = 'SENTINEL';
    ob_start();
    include $action_file;
    ob_end_clean();
    return array($GLOBALS['error_message'], $redirect);
  }

  $req = array('gid' => '3', 'newGroup' => array('Name' => 'Front', 'MonitorIds' => array('1', '2', '3')));

  list($msg, $redirect) = run_action($action_file, $req, array());
  check('a good save redirects', $redirect, '?view=groups');
  check('a good save reports nothing', $msg, '');
  check('a good save audits', count(ZM\Rec::$audit), 1);

  list($msg, $redirect) = run_action($action_file, $req, array('INSERT'), 2);
  check('a failed insert does not redirect', $redirect, 'SENTINEL');
  check('a failed insert is reported', $msg !== '', true);
  check('the report names the monitor', strpos($msg, '2') !== false, true);
  check('the report is html escaped', strpos($msg, '&lt;fail&gt;') !== false, true);
  check('a failed insert is not audited as success', count(ZM\Rec::$audit), 0);

  list($msg, $redirect) = run_action($action_file, $req, array('DELETE'));
  check('a failed delete does not redirect', $redirect, 'SENTINEL');
  check('a failed delete is reported', $msg !== '', true);
  check('a failed delete adds no members', count(ZM\Rec::$queries), 1);
  check('a failed delete is not audited as success', count(ZM\Rec::$audit), 0);

  list($msg, $redirect) = run_action($action_file,
    array('newGroup' => array('Name' => 'Back', 'MonitorIds' => array('4'))), array());
  check('a new group clears membership by its own id',
    ZM\Rec::$queries[0][1] === array(42), true);

  echo $failures ? "\n$failures failed\n" : "\nall passed\n";
  exit($failures ? 1 : 0);
}
