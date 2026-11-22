<?php
// ajax/stats.php put the row request value unescaped into the returned table id
// (reflected XSS); it now keeps only word characters and dashes.
//
// Run as: php tests/php/test_ajax_stats_row_escaped.php

class Done extends Exception {}
function canView($a) { return true; }
function ajaxError($m) { $GLOBALS['out'] = "error: $m"; throw new Done(); }
function ajaxResponse($r) { $GLOBALS['out'] = $r; throw new Done(); }
function validHtmlStr($s) { return htmlspecialchars((string)$s); }
function translate($s) { return $s; }
function getStatsTableHTML($eid, $fid, $row) { $GLOBALS['ids'] = array($eid, $fid); return 'table'.$row; }
function validCardinal($v) { return preg_replace('/\D/', '', $v); }
function dbFetchAll($sql, $col = null, $args = array()) { return array(); }

$root = isset($argv[1]) ? rtrim($argv[1], '/') : __DIR__.'/../..';

$cases = array('3', '5"><script>alert(1)</script>', array('x'), 'ids');

$failures = 0;
function check($n, $got, $want) {
  global $failures;
  if ($got === $want) { echo "ok - $n\n"; } else { $failures++; echo "FAIL - $n (got ".var_export($got, true).")\n"; }
}

function run($row) {
  global $root;
  $_REQUEST = array('eid' => '5', 'fid' => '7', 'row' => $row);
  if ($row === 'ids') $_REQUEST = array('eid' => '5"><b>e</b>', 'fid' => '7<i>f</i>', 'row' => '1');
  try {
    include $root.'/web/ajax/stats.php';
  } catch (Done $e) {
  }
  return $GLOBALS['out'];
}

if (isset($argv[2])) {
  $index = (int)$argv[2];
  $out = run($cases[$index]);
  $id = is_array($out) ? ($out['id'] ?? null) : null;

  if ($index === 0) {
    check('safe row is kept in the returned table id', $id, '#contentStatsTable3');
  } else if ($index === 1) {
    check('unsafe row leaves no <, > or " in the returned table id', preg_match('/[<>"]/', (string)$id), 0);
  } else if ($index === 3) {
    check('eid and fid reach the stats table as digits only', $GLOBALS['ids'] ?? null, array('5', '7'));
  } else {
    check('non-scalar row is not put into the returned table id', $id, '#contentStatsTable');
  }

  exit($failures ? 1 : 0);
}

$failed = false;
foreach (array_keys($cases) as $index) {
  $cmd = escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' '.escapeshellarg($root).' '.escapeshellarg((string)$index);
  $status = 0;
  passthru($cmd, $status);
  if ($status !== 0) { $failed = true; }
}
exit($failed ? 1 : 0);
