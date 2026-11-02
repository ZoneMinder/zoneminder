<?php
// Regression test for #5418: a filter term on Current Weekday produced SQL with a
// missing closing parenthesis, so the events query failed, and a value of 0
// (Monday) was taken for a missing value and the term dropped.
//
// Run as: php tests/php/test_filter_term_current_weekday.php

namespace ZM;

// Stubs for FilterTerm.php dependencies
if (!defined('STRF_FMT_DATETIME_DB')) define('STRF_FMT_DATETIME_DB', 'Y-m-d H:i:s');
function dbEscape($s) { return "'".addslashes($s)."'"; }
function Warning($s) { /* noop */ }
function Error($s)   { /* noop */ }
function Debug($s)   { /* noop */ }
function translate($s) { return $s; }
if (!class_exists('\ZM\Group')) {
  class Group {
    public function __construct($v=null) {}
    public function MonitorIds() { return array(); }
  }
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

function make_term($attr, $op, $val) {
  return new FilterTerm(null, array('attr'=>$attr, 'op'=>$op, 'val'=>$val), 0);
}

function balanced($sql) {
  return substr_count($sql, '(') === substr_count($sql, ')');
}

$sql = make_term('CurrentWeekday', '=', '6')->sql();
check('Sunday term compares weekday(NOW())', (bool)preg_match("/weekday\(NOW\(\)\)\s*=\s*'6'/", $sql), true);
check('Sunday term has balanced parentheses', balanced($sql), true);

foreach (array(array('0', 'string'), array(0, 'int')) as list($val, $type)) {
  $term = make_term('CurrentWeekday', '=', $val);
  check("Monday ($type 0) is a valid value", $term->valid(), true);
  check("Monday ($type 0) term compares against 0",
    (bool)preg_match("/weekday\(NOW\(\)\)\s*=\s*'0'/", $term->sql()), true);
}

check('an empty value is still not valid', make_term('CurrentWeekday', '=', '')->valid(), false);
check('a missing value is still not valid', make_term('CurrentWeekday', '=', null)->valid(), false);

// The other Current* terms keep their SQL.
foreach (array('CurrentDate' => '2026-05-06', 'CurrentTime' => '12:00:00', 'CurrentDateTime' => '2026-05-06 12:00:00') as $attr => $val) {
  $sql = make_term($attr, '=', $val)->sql();
  check("$attr term has balanced parentheses", balanced($sql), true);
  check("$attr term compares NOW()", strpos($sql, 'NOW()') !== false, true);
}

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
