<?php
// Deleting control capabilities (Options > Control) always answered success: the UPDATE of
// the monitors using it and the DELETE of the Controls row weren't checked. A failed delete
// now answers an error, which controlcaps.js shows.
//
// Run as: php tests/php/test_controlcaps_delete_result.php [root]   (root defaults to the repo)

class Done extends Exception {}
function canEdit($a) { return true; }
function validCardinal($v) { return is_scalar($v) ? preg_replace('/\D/', '', $v) : ''; }
function ajaxError($m) { $GLOBALS['out'] = 'error: '.$m; throw new Done(); }
function ajaxResponse($r = array()) { $GLOBALS['out'] = 'ok'; throw new Done(); }
function dbLastError() { return 'db down'; }
function dbQuery($sql, $values = array()) {
  $GLOBALS['queries'][] = array(trim($sql), $values);
  return $GLOBALS['dbResult'];
}

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';

function run($cids, $dbResult) {
  global $root;
  $GLOBALS['out'] = 'nothing';
  $GLOBALS['queries'] = array();
  $GLOBALS['dbResult'] = $dbResult;
  $_REQUEST = array('action' => 'delete', 'cids' => $cids);
  try {
    include $root.'/web/ajax/controlcaps.php';
  } catch (Done $e) {
  } catch (Throwable $e) {
    return 'threw: '.$e->getMessage();
  }
  return $GLOBALS['out'];
}

$failures = 0;
function check($n, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $n\n";
  else { $failures++; echo "FAIL - $n (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

check('a delete that works answers ok', run(array('3'), true), 'ok');
check('a delete that fails answers an error', run(array('3'), null), 'error: Failed to delete control capability: db down');
run(array('3', 'x', array('4')), true);
check('only numeric ids are deleted', count($GLOBALS['queries']), 2);

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
