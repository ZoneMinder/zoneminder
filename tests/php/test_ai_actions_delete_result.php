<?php
/*
 * The AI dataset/class/model delete actions must honour the result of dbQuery():
 * a failed DELETE must append an 'Error deleting ...' message and must NOT set
 * $redirect or $refreshParent; on success they redirect and refresh as before.
 *
 * Run: php tests/php/test_ai_actions_delete_result.php [zm-root]
 */
$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$tests = 0; $failures = 0;
function check($label, $cond) {
  global $tests, $failures;
  $tests++;
  if ($cond) echo "  ok   - $label\n"; else { $failures++; echo "  FAIL - $label\n"; }
}

eval('namespace ZM {
  function Warning($m) {} function Error($m) {} function Info($m) {} function Debug($m) {}
}');
function canEdit($area) { return true; }
function getFormChanges($old, $new, &$types) { return array("Name='COCO'"); }
function dbFetchOne($sql, $cache = NULL, $args = array()) { return array(); }
function dbQuery($sql, $args = array()) { return $GLOBALS['db_ok'] ? true : null; }
function dbLastError() { return "Duplicate entry '<b>COCO</b>' for key 'Name'"; }
function translate($k) { return $k; }
function validHtmlStr($s) { return htmlspecialchars($s); }

// Runs an action file; returns its effects on $error_message/$redirect/$refreshParent.
function run_action($file, $action, $request, $db_ok) {
  global $root;
  $src = file_get_contents($root.'/web/includes/actions/'.$file);
  $src = preg_replace('/^<\?php/', '', $src);
  $src = preg_replace('/\?>\s*$/', '', $src);
  $src = preg_replace('/^require_once\(.*$/m', '', $src);
  $problems = array();
  set_error_handler(function ($no, $str) use (&$problems) { $problems[] = $str; return true; });
  $_REQUEST = $request;
  $GLOBALS['db_ok'] = $db_ok;
  $fn = function ($action) use ($src) {
    global $error_message, $redirect, $refreshParent;
    $error_message = ''; $redirect = null; $refreshParent = null;
    try { eval($src); } catch (\Throwable $e) { return get_class($e).': '.$e->getMessage(); }
    return null;
  };
  $ex = $fn($action);
  restore_error_handler();
  $state = array(
    'error_message' => $GLOBALS['error_message'],
    'redirect' => $GLOBALS['redirect'],
    'refreshParent' => $GLOBALS['refreshParent'],
    'problems' => $problems,
  );
  if ($ex) $state['problems'][] = $ex;
  return $state;
}

$cases = array(
  array('ai_dataset.php', '?view=options&tab=ai_datasets'),
  array('ai_class.php',   '?view=options&tab=ai_classes'),
  array('ai_model.php',   '?view=options&tab=ai_models'),
);
foreach ($cases as $case) {
  list($file, $want_redirect) = $case;
  echo "$file\n";
  echo "  db failure:\n";
  $s = run_action($file, 'delete', array('markIds' => array(1, 2)), false);
  check('reports Error deleting', strpos($s['error_message'], 'Error deleting') !== false);
  check('escapes the db error', strpos($s['error_message'], '<b>') === false
        && strpos($s['error_message'], '&lt;b&gt;') !== false);
  check('redirect stays unset', $s['redirect'] === null);
  check('refreshParent is not true', $s['refreshParent'] !== true);
  check('raises no warnings', count($s['problems']) == 0);
  echo "  db success:\n";
  $s = run_action($file, 'delete', array('markIds' => array(1, 2)), true);
  check('no error message', $s['error_message'] === '');
  check('redirects to '.$want_redirect, $s['redirect'] === $want_redirect);
  check('refreshParent is true', $s['refreshParent'] === true);
  check('raises no warnings', count($s['problems']) == 0);
}
echo "\n$tests tests, $failures failures\n";
exit($failures ? 1 : 0);
