<?php
/*
 * The AI dataset/class/model save actions must honour the result of dbQuery():
 * on a failed INSERT/UPDATE (e.g. a duplicate unique Name) they must append an
 * 'Error saving ...' message, and must NOT set $redirect or $refreshParent;
 * on success they must set neither an error message nor skip the redirect.
 * The modals post view=ai_class etc., which have no view file, so a failure
 * must switch to the options page on the matching tab to show the error.
 *
 * Runs the real action files against stubbed globals.
 *
 * Run: php tests/php/test_ai_actions_save_result.php [zm-root]
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
    global $error_message, $redirect, $refreshParent, $view;
    $error_message = ''; $redirect = null; $refreshParent = null; $view = null;
    try { eval($src); } catch (\Throwable $e) { return get_class($e).': '.$e->getMessage(); }
    return null;
  };
  $ex = $fn($action);
  restore_error_handler();
  $state = array(
    'error_message' => $GLOBALS['error_message'],
    'redirect' => $GLOBALS['redirect'],
    'refreshParent' => $GLOBALS['refreshParent'],
    'view' => $GLOBALS['view'],
    'tab' => $_REQUEST['tab'] ?? null,
    'problems' => $problems,
  );
  if ($ex) $state['problems'][] = $ex;
  return $state;
}

$cases = array(
  array('ai_dataset.php', 'newDataset', '?view=options&tab=ai_datasets'),
  array('ai_class.php',   'newClass',   '?view=options&tab=ai_classes'),
  array('ai_model.php',   'newModel',   '?view=options&tab=ai_models'),
);

foreach ($cases as $case) {
  list($file, $key, $want_redirect) = $case;
  $want_tab = substr($want_redirect, strlen('?view=options&tab='));
  echo "$file\n";

  echo "  db failure (duplicate Name):\n";
  $s = run_action($file, 'save', array($key => array('Name' => 'COCO')), false);
  check('reports Error saving', strpos($s['error_message'], 'Error saving') !== false);
  check('includes the db error', strpos($s['error_message'], 'Duplicate entry') !== false);
  check('escapes the db error', strpos($s['error_message'], '<b>') === false
        && strpos($s['error_message'], '&lt;b&gt;COCO') !== false);
  check('redirect stays unset', $s['redirect'] === null);
  check('refreshParent is not true', $s['refreshParent'] !== true);
  check('renders the options page', $s['view'] === 'options');
  check('on the '.$want_tab.' tab', $s['tab'] === $want_tab);
  check('raises no warnings', count($s['problems']) == 0);

  echo "  db success:\n";
  $s = run_action($file, 'save', array($key => array('Name' => 'COCO')), true);
  check('no error message', $s['error_message'] === '');
  check('redirects to '.$want_redirect, $s['redirect'] === $want_redirect);
  check('refreshParent is true', $s['refreshParent'] === true);
  check('raises no warnings', count($s['problems']) == 0);
}

echo "\n$tests tests, $failures failures\n";
exit($failures ? 1 : 0);
