<?php
// A filter term whose value is 0 (Archived = 0, Monitor IN 0, Current Weekday
// = Monday, No Tag) must keep that value in the hidden fields that carry a
// filter from one form to the next. Written as "" the term is invalid on the
// way back and is dropped, so the download modal's "Monitor 0 matches nothing"
// term for an empty group stops restricting the export.
//
// Run as: php tests/php/test_filter_term_hidden_zero_value.php

namespace ZM;

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

// The value the next request would see for term 0.
function submitted_val($term) {
  $html = $term->hidden_fields();
  if (!preg_match('/name="filter\[Query\]\[terms\]\[0\]\[val\]" value="([^"]*)"/', $html, $m)) return null;
  return html_entity_decode($m[1]);
}

foreach (array(
  array('Monitor', 'IN', '0'),
  array('Archived', '=', '0'),
  array('CurrentWeekday', '=', '0'),
  array('Tags', '=', '0'),
) as $t) {
  $term = new FilterTerm(null, array('attr'=>$t[0], 'op'=>$t[1], 'val'=>$t[2]), 0);
  check($t[0].' '.$t[1].' 0 keeps its value in the hidden field', submitted_val($term), '0');
  $again = new FilterTerm(null, array('attr'=>$t[0], 'op'=>$t[1], 'val'=>submitted_val($term)), 0);
  check($t[0].' '.$t[1].' 0 is still a valid term after the round trip', $again->valid(), true);
}

$term = new FilterTerm(null, array('attr'=>'Monitor', 'op'=>'IN', 'val'=>'0'), 0);
$again = new FilterTerm(null, array('attr'=>'Monitor', 'op'=>'IN', 'val'=>submitted_val($term)), 0);
check('Monitor IN 0 still restricts the export after the round trip',
  preg_match('/MonitorId\s+IN\s+\(\'0\'\)/', $again->sql()), 1);

$term = new FilterTerm(null, array('attr'=>'Name', 'op'=>'=', 'val'=>''), 0);
check('an empty value stays empty', submitted_val($term), '');

$term = new FilterTerm(null, array('attr'=>'Name', 'op'=>'=', 'val'=>'a"b<c'), 0);
check('a value is still escaped', strpos($term->hidden_fields(), 'value="a&quot;b&lt;c"') !== false, true);

echo "\n$passes passed, $failures failed.\n";
exit($failures ? 1 : 0);
