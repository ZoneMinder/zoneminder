<?php
/*
 * The device and control capability delete actions must honour the result of
 * dbQuery(): a failed statement must append an 'Error deleting ...' message
 * (with the database error escaped) and must NOT set $refreshParent; on
 * success they refresh as before.
 *
 * Run: php tests/php/test_controlcaps_devices_delete_result.php [zm-root]
 */
$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$tests = 0; $failures = 0;
function check($label, $cond) {
  global $tests, $failures;
  $tests++;
  if ($cond) echo "  ok   - $label\n"; else { $failures++; echo "  FAIL - $label\n"; }
}

function canEdit($area) { return true; }
function dbQuery($sql, $args = array()) {
  if ($GLOBALS['db_mode'] == 'fail') return null;
  if ($GLOBALS['db_mode'] == 'fail_update' && strpos(ltrim($sql), 'UPDATE Monitors') === 0) return null;
  return true;
}
function dbLastError() { return "Dup <b>x</b>"; }

// Runs an action file; returns its effects on $error_message/$refreshParent.
function run_action($file, $action, $request, $db_mode) {
  global $root;
  $src = file_get_contents($root.'/web/includes/actions/'.$file);
  $src = preg_replace('/^<\?php/', '', $src);
  $src = preg_replace('/\?>\s*$/', '', $src);
  $src = preg_replace('/^require_once\(.*$/m', '', $src);
  $problems = array();
  set_error_handler(function ($no, $str) use (&$problems) { $problems[] = $str; return true; });
  $_REQUEST = $request;
  $GLOBALS['db_mode'] = $db_mode;
  $fn = function ($action) use ($src) {
    global $error_message, $refreshParent;
    $error_message = ''; $refreshParent = null;
    try { eval($src); } catch (\Throwable $e) { return get_class($e).': '.$e->getMessage(); }
    return null;
  };
  $ex = $fn($action);
  restore_error_handler();
  $state = array(
    'error_message' => $GLOBALS['error_message'],
    'refreshParent' => $GLOBALS['refreshParent'],
    'problems' => $problems,
  );
  if ($ex) $state['problems'][] = $ex;
  return $state;
}

$cases = array(
  array('devices.php',     'markDids', array(3, 4)),
  array('controlcaps.php', 'markCids', array(3, 4)),
);
foreach ($cases as $case) {
  list($file, $key, $ids) = $case;
  echo "$file\n";
  echo "  db failure:\n";
  $s = run_action($file, 'delete', array($key => $ids), 'fail');
  check('reports Error deleting', strpos($s['error_message'], 'Error deleting') !== false);
  check('escapes the db error', strpos($s['error_message'], '<b>') === false
        && strpos($s['error_message'], '&lt;b&gt;') !== false);
  check('refreshParent is not true', $s['refreshParent'] !== true);
  check('raises no warnings', count($s['problems']) == 0);
  echo "  db success:\n";
  $s = run_action($file, 'delete', array($key => $ids), 'ok');
  check('no error message', $s['error_message'] === '');
  check('refreshParent is true', $s['refreshParent'] === true);
  check('raises no warnings', count($s['problems']) == 0);
}

echo "controlcaps.php\n";
echo "  UPDATE Monitors failure:\n";
$s = run_action('controlcaps.php', 'delete', array('markCids' => array(3, 4)), 'fail_update');
check('reports Error deleting', strpos($s['error_message'], 'Error deleting') !== false);
check('escapes the db error', strpos($s['error_message'], '<b>') === false
      && strpos($s['error_message'], '&lt;b&gt;') !== false);
check('refreshParent is not true', $s['refreshParent'] !== true);
check('raises no warnings', count($s['problems']) == 0);

echo "\n$tests tests, $failures failures\n";
exit($failures ? 1 : 0);
