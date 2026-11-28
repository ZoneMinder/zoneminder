<?php
// A DiskPercent pre-condition with the != operator was rejected as an invalid op
// and the term always tested false, although the filter UI offers != for it.
//
// Run as: php tests/php/test_filter_term_diskpercent_ops.php

namespace ZM;

if (!defined('STRF_FMT_DATETIME_DB')) define('STRF_FMT_DATETIME_DB', 'Y-m-d H:i:s');
function dbEscape($s) { return "'".addslashes($s)."'"; }
$warnings = array();
function Warning($s) { global $warnings; $warnings[] = $s; }
function Error($s)   { /* noop */ }
function Debug($s)   { /* noop */ }
function translate($s) { return $s; }

class StubStorage {
  public function disk_usage_percent() { return 50; }
}
class StubFilter {
  public function get_StorageAreas() { return array(new StubStorage()); }
}

require_once __DIR__.'/../../web/includes/FilterTerm.php';

$failures = 0;
$passes = 0;

function check($name, $got, $want) {
  global $failures, $passes;
  if ($got === $want) {
    $passes++;
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n";
  }
}

function disk_test($op, $val) {
  $term = new FilterTerm(new StubFilter(), array('attr'=>'DiskPercent', 'op'=>$op, 'val'=>$val), 0);
  return $term->test();
}

// Disk usage is 50%.
check('50 != 40 is true', disk_test('!=', '40'), true);
check('50 != 50 is false', disk_test('!=', '50'), false);
check('!= logs no warning', count($warnings), 0);

check('50 = 50 is true', disk_test('=', '50'), true);
check('50 > 40 is true', disk_test('>', '40'), true);
check('50 > 50 is false', disk_test('>', '50'), false);
check('50 >= 50 is true', disk_test('>=', '50'), true);
check('50 < 60 is true', disk_test('<', '60'), true);
check('50 <= 40 is false', disk_test('<=', '40'), false);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
