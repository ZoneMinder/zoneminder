<?php
// montagereview.php range parameters.
//
// - ?minTime= without maxTime (or the reverse) used an undefined variable.
// - ?current= that does not parse made strtotime() return false, which the
//   arithmetic below turned into a window around 1970.
//
// The parameter block is cut out of the view by its markers and run with the
// request set up here, so no database or session is needed.
//
// Run: php tests/php/test_montagereview_params.php   (from the repo root)

ini_set('date.timezone', 'UTC');
function validHtmlStr($s) { return htmlspecialchars($s, ENT_QUOTES); }

$passed = 0;
$failed = 0;
function check($label, $ok, $detail = '') {
  global $passed, $failed;
  if ($ok) {
    $passed++;
    echo "  ok $label\n";
  } else {
    $failed++;
    echo "  FAIL $label $detail\n";
  }
}

$source = file_get_contents(__DIR__.'/../../web/skins/classic/views/montagereview.php');
$start = strpos($source, "if (isset(\$_REQUEST['current'])) {");
$end = strpos($source, "\$filter = null;\nif (isset(\$_REQUEST['filter']))");
if ($start === false || $end === false) {
  echo "  FAIL could not find the parameter block\n";
  exit(1);
}
$block = substr($source, $start, $end - $start);

function run_block($block, $request) {
  $warnings = array();
  set_error_handler(function($no, $str) use (&$warnings) {
    $warnings[] = $str;
    return true;
  });
  $_REQUEST = $request;
  $_COOKIE = array();
  $vars = (function() use ($block) {
    eval($block);
    return get_defined_vars();
  })();
  restore_error_handler();
  return array($vars, $warnings);
}

list($v, $w) = run_block($block, array('minTime' => '2026-01-01 10:00:00'));
check('minTime alone raises no warning', count($w) == 0, implode('; ', $w));
check('minTime alone still gets a maxTime', !empty($v['maxTime']));
check('minTime alone keeps the minTime', ($v['minTime'] ?? null) === '2026-01-01 10:00:00');

list($v, $w) = run_block($block, array('maxTime' => '2026-01-01 10:00:00'));
check('maxTime alone raises no warning', count($w) == 0, implode('; ', $w));
check('maxTime alone still gets a minTime', !empty($v['minTime']));

list($v, $w) = run_block($block, array('current' => 'not a time'));
check('unparsable current= does not start in 1970', strtotime($v['minTime']) > strtotime('2000-01-01'),
  'minTime='.$v['minTime']);

list($v, $w) = run_block($block, array('current' => '2026-01-01 12:00:00'));
check('current= centres the window', $v['minTime'] === '2026-01-01 11:30:00' && $v['maxTime'] === '2026-01-01 12:30:00');

list($v, $w) = run_block($block, array('minTime' => '2026-01-01 10:00:00', 'maxTime' => '2026-01-01 11:00:00'));
check('both given are kept', $v['minTime'] === '2026-01-01 10:00:00' && $v['maxTime'] === '2026-01-01 11:00:00');

echo "\n$passed passed, $failed failed\n";
exit($failed ? 1 : 0);
