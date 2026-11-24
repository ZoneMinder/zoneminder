<?php
/*
 * Group::delete() must report failure instead of swallowing it, so the delete
 * action can stop auditing a delete that did not happen.
 *
 * Run: php tests/php/test_group_delete_failure.php [repo-root]
 */
$root = isset($argv[1]) ? rtrim($argv[1], '/') : dirname(__DIR__, 2);

$tests = 0;
$failures = 0;

function check($label, $cond) {
  global $tests, $failures;
  $tests++;
  if ($cond) echo "  ok  - $label\n"; else { $failures++; echo "  NOT OK - $label\n"; }
}

$GLOBALS['db_mode'] = 'ok';
$GLOBALS['issued'] = array();

function dbQuery($sql, $params = array()) {
  $GLOBALS['issued'][] = $sql;
  $mode = $GLOBALS['db_mode'];
  if ( ($mode == 'fail_monitors') && (strpos($sql, 'DELETE FROM `Groups_Monitors`') !== false) ) return null;
  if ( ($mode == 'fail_groups') && (strpos($sql, 'DELETE FROM `Groups` ') !== false) ) return null;
  return true;
}

function dbFetchOne($sql, $col = null, $params = array()) {
  return array('ParentId' => null);
}

function canEdit($p) { return true; }
function canView($p) { return true; }
function validHtmlStr($s) { return $s; }
function dbLastError() { return 'boom'; }

if ( isset($argv[2]) ) {
  $case = $argv[2];
  $src = file_get_contents($root.'/web/includes/Group.php');
  $action_src = file_get_contents($root.'/web/includes/actions/groups.php');

  switch ($case) {
    case '1':
      eval('namespace ZM { class ZM_Object { function __construct($p = null) { if (is_array($p)) foreach ($p as $k => $v) $this->{$k} = $v; } function Id() { return isset($this->{"Id"}) ? $this->{"Id"} : null; } } class Monitor extends ZM_Object {} class Group_Permission extends ZM_Object {} function zm_setcookie() {} }');
      eval('?>'.$src);
      $GLOBALS['db_mode'] = 'ok';
      $g = new ZM\Group(array('Id' => 2));
      check('delete returns true when every query succeeds', $g->delete() === true);
      break;

    case '2':
      eval('namespace ZM { class ZM_Object { function __construct($p = null) { if (is_array($p)) foreach ($p as $k => $v) $this->{$k} = $v; } function Id() { return isset($this->{"Id"}) ? $this->{"Id"} : null; } } class Monitor extends ZM_Object {} class Group_Permission extends ZM_Object {} function zm_setcookie() {} }');
      eval('?>'.$src);
      $GLOBALS['db_mode'] = 'fail_groups';
      $g = new ZM\Group(array('Id' => 2));
      check('delete returns false when the Groups delete fails', $g->delete() === false);
      break;

    case '3':
      eval('namespace ZM { class ZM_Object { function __construct($p = null) { if (is_array($p)) foreach ($p as $k => $v) $this->{$k} = $v; } function Id() { return isset($this->{"Id"}) ? $this->{"Id"} : null; } } class Monitor extends ZM_Object {} class Group_Permission extends ZM_Object {} function zm_setcookie() {} }');
      eval('?>'.$src);
      $GLOBALS['db_mode'] = 'fail_monitors';
      $GLOBALS['issued'] = array();
      $g = new ZM\Group(array('Id' => 2));
      check('delete returns false when the Groups_Monitors delete fails', $g->delete() === false);
      $saw_groups_delete = false;
      foreach ($GLOBALS['issued'] as $sql) {
        if ( strpos($sql, 'DELETE FROM `Groups` ') !== false ) $saw_groups_delete = true;
      }
      check('the Groups delete is not issued after an earlier failure', $saw_groups_delete === false);
      break;

    case '4':
      eval('namespace ZM { class Group { function Id() { return 5; } function Name() { return "G5"; } function MonitorIds() { return array(); } function delete() { return $GLOBALS["delete_result"]; } static function find($p = array(), $o = array()) { return array($GLOBALS["fake_group"]); } static function canEditMembership($ids, $u = null) { return true; } } function AuditAction($a, $b, $id, $d = null) { $GLOBALS["audit_calls"][] = array($a, $b, $id); } function Warning($m) {} }');
      $GLOBALS['fake_group'] = new ZM\Group();

      $GLOBALS['delete_result'] = false;
      $GLOBALS['audit_calls'] = array();
      $error_message = '';
      $action = 'delete';
      $_REQUEST = array('gid' => 5);
      eval('?>'.$action_src);
      check('failed delete appends an error message', strpos($error_message, 'Error deleting group') !== false);
      check('failed delete does not call AuditAction', count($GLOBALS['audit_calls']) === 0);

      $GLOBALS['delete_result'] = true;
      $GLOBALS['audit_calls'] = array();
      $error_message = '';
      $action = 'delete';
      $_REQUEST = array('gid' => 5);
      eval('?>'.$action_src);
      check('successful delete calls AuditAction once', count($GLOBALS['audit_calls']) === 1);
      check('successful delete leaves no error message', $error_message === '');
      break;

    default:
      echo "unknown case $case\n";
      exit(2);
  }

  exit($failures ? 1 : 0);
}

$passed = 0;
$failed = 0;
foreach (array('1', '2', '3', '4') as $case) {
  $out = array();
  $cmd = escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' '.escapeshellarg($root).' '.escapeshellarg($case);
  exec($cmd, $out, $rc);
  foreach ($out as $line) {
    if ( strpos($line, '  ok  - ') === 0 ) $passed++;
  }
  if ( $rc !== 0 ) {
    $failed++;
    echo "case $case failed:\n".implode("\n", $out)."\n";
  }
}

echo "\n$passed passed, $failed failed\n";
exit($failed ? 1 : 0);
