<?php
/*
 * The report and filter delete actions must honour the result of ZM_Object::delete():
 * a failed delete must append an 'Error deleting ...' message and must not redirect
 * or audit as success; on success report redirects to the reports view and filter
 * records exactly one AuditAction.
 *
 * Run: php tests/php/test_report_filter_delete_result.php [repo-root]
 */
namespace ZM {
  function Warning($m) {}
  function Error($m) {}
  function Info($m) {}
  function Debug($m) {}

  function AuditAction($action, $object, $id, $note = '') {
    $GLOBALS['audit'][] = array('action' => $action, 'object' => $object, 'id' => $id, 'note' => $note);
  }

  class Report {
    public $id;
    function __construct($id = null) { $this->id = $id; }
    function Id() { return $this->id; }
    function canEdit() { return true; }
    function delete() { return $GLOBALS['report_delete_ok']; }
  }

  class Filter {
    public $id;
    function __construct($id = null) { $this->id = $id; }
    function Id() { return $this->id; }
    function Name() { return 'test filter'; }
    function Background() { return false; }
    function canDelete() { return true; }
    function delete() { return $GLOBALS['filter_delete_ok']; }
  }
}

namespace {
  $root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
  $tests = 0;
  $failures = 0;
  function check($label, $cond) {
    global $tests, $failures;
    $tests++;
    if ($cond) {
      echo "  ok   - $label\n";
    } else {
      $failures++;
      echo "  FAIL - $label\n";
    }
  }

  // Stub the action files' require_once dependencies; the classes used are the
  // stubs declared in the ZM namespace above.
  $stub = sys_get_temp_dir().'/zm_delete_result_stub_'.getmypid();
  mkdir($stub.'/includes', 0777, true);
  foreach (array('Report', 'Filter') as $f) {
    file_put_contents($stub."/includes/$f.php", "<?php\n");
  }
  set_include_path($stub.PATH_SEPARATOR.get_include_path());

  function canEdit($a) { return true; }
  function canView($a) { return true; }
  function validCardinal($v) { return (int)$v; }
  function validInt($v) { return (int)$v; }
  function validStr($v) { return (string)$v; }
  function dbLastError() { return "Duplicate entry '<b>f</b>' for key 'Name'"; }

  // Runs an action file and returns its effects. The action files are included at
  // global scope so $action/$user/$redirect/$error_message are visible to them.
  function run_action($root, $file, $action, $request) {
    $src = $root.'/web/includes/actions/'.$file;
    $_REQUEST = $request;
    $GLOBALS['error_message'] = '';
    $GLOBALS['redirect'] = null;
    $GLOBALS['action'] = $action;
    $GLOBALS['user'] = new class { function Id() { return 1; } function canEdit($a) { return true; } function canView($a) { return true; } };
    $problems = array();
    set_error_handler(function ($no, $str) use (&$problems) { $problems[] = $str; return true; });
    $ex = null;
    $fn = function () use ($src) {
      global $action, $user, $redirect, $error_message;
      try {
        include $src;
      } catch (\Throwable $e) {
        return get_class($e).': '.$e->getMessage();
      }
      return null;
    };
    $ex = $fn();
    restore_error_handler();
    if ($ex) $problems[] = $ex;
    return array(
      'error_message' => $GLOBALS['error_message'],
      'redirect' => $GLOBALS['redirect'],
      'problems' => $problems,
    );
  }

  echo "report.php delete\n";
  echo "  delete failure:\n";
  $GLOBALS['report_delete_ok'] = false;
  $s = run_action($root, 'report.php', 'delete', array('id' => 1));
  check('reports Error deleting report', strpos($s['error_message'], 'Error deleting report') !== false);
  check('escapes the db error', strpos($s['error_message'], '<b>') === false
        && strpos($s['error_message'], '&lt;b&gt;') !== false);
  check('redirect stays unset', $s['redirect'] === null);
  check('raises no warnings', count($s['problems']) == 0);

  echo "  delete success:\n";
  $GLOBALS['report_delete_ok'] = true;
  $s = run_action($root, 'report.php', 'delete', array('id' => 1));
  check('no error message', $s['error_message'] === '');
  check('redirects to ?view=reports', $s['redirect'] === '?view=reports');
  check('raises no warnings', count($s['problems']) == 0);

  echo "filter.php delete\n";
  $request = array('object' => 'filter', 'Id' => 7);

  echo "  delete failure:\n";
  $GLOBALS['filter_delete_ok'] = false;
  $GLOBALS['audit'] = array();
  $s = run_action($root, 'filter.php', 'delete', $request);
  check('reports Error deleting filter', strpos($s['error_message'], 'Error deleting filter') !== false);
  check('escapes the db error', strpos($s['error_message'], '<b>') === false
        && strpos($s['error_message'], '&lt;b&gt;') !== false);
  check('records no AuditAction', count($GLOBALS['audit']) === 0);
  check('raises no warnings', count($s['problems']) == 0);

  echo "  delete success:\n";
  $GLOBALS['filter_delete_ok'] = true;
  $GLOBALS['audit'] = array();
  $s = run_action($root, 'filter.php', 'delete', $request);
  check('no error message', $s['error_message'] === '');
  check('records exactly one AuditAction', count($GLOBALS['audit']) === 1);
  $a = count($GLOBALS['audit']) ? $GLOBALS['audit'][0] : array();
  check("audit action is 'delete'", isset($a['action']) && $a['action'] === 'delete');
  check("audit object is 'filter'", isset($a['object']) && $a['object'] === 'filter');
  check('audit id is the request id', isset($a['id']) && (string)$a['id'] === '7');
  check('raises no warnings', count($s['problems']) == 0);

  system('rm -rf '.escapeshellarg($stub));
  echo "\n$tests tests, $failures failures\n";
  exit($failures ? 1 : 0);
}
