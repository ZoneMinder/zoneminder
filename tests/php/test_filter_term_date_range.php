<?php
// Tests that ZM\FilterTerm generates sargable range queries for
// Date / StartDate / EndDate attrs instead of wrapping the column
// in to_days(), which defeats the index on StartDateTime.
//
// Run as: php tests/php/test_filter_term_date_range.php

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

function make_term($attr, $op, $val) {
  return new FilterTerm(null, array('attr'=>$attr, 'op'=>$op, 'val'=>$val), 0);
}

function assert_sql($name, $sql, $regex, $should_match) {
  global $failures, $passes;
  $matched = (bool)preg_match($regex, $sql);
  $ok = $should_match ? $matched : !$matched;
  if ($ok) {
    $passes++;
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "FAIL - $name\n";
    echo "  SQL:   $sql\n";
    echo "  Regex: $regex (should ".($should_match?'match':'not match').")\n";
  }
}

// Date = '2026-05-06'
{
  $sql = make_term('Date', '=', '2026-05-06')->sql();
  assert_sql('Date = no to_days on column', $sql, '/to_days\s*\(\s*E\.StartDateTime/i', false);
  assert_sql('Date = lower bound', $sql, "/E\.StartDateTime\s*>=\s*'2026-05-06 00:00:00'/", true);
  assert_sql('Date = upper bound', $sql, "/E\.StartDateTime\s*<\s*'2026-05-07 00:00:00'/", true);
}

// StartDate = '2026-05-06'
{
  $sql = make_term('StartDate', '=', '2026-05-06')->sql();
  assert_sql('StartDate = no to_days', $sql, '/to_days\s*\(\s*E\.StartDateTime/i', false);
  assert_sql('StartDate = lower bound', $sql, "/E\.StartDateTime\s*>=\s*'2026-05-06 00:00:00'/", true);
}

// EndDate = '2026-05-06'
{
  $sql = make_term('EndDate', '=', '2026-05-06')->sql();
  assert_sql('EndDate = uses E.EndDateTime', $sql, "/E\.EndDateTime\s*>=\s*'2026-05-06 00:00:00'/", true);
  assert_sql('EndDate = no to_days on EndDateTime', $sql, '/to_days\s*\(\s*E\.EndDateTime/i', false);
}

// Date >= '2026-05-06'
{
  $sql = make_term('Date', '>=', '2026-05-06')->sql();
  assert_sql('Date >= col >= lower', $sql, "/E\.StartDateTime\s*>=\s*'2026-05-06 00:00:00'/", true);
  assert_sql('Date >= no to_days', $sql, '/to_days/i', false);
}

// Date > '2026-05-06'
{
  $sql = make_term('Date', '>', '2026-05-06')->sql();
  assert_sql('Date > col >= upper', $sql, "/E\.StartDateTime\s*>=\s*'2026-05-07 00:00:00'/", true);
}

// Date < '2026-05-06'
{
  $sql = make_term('Date', '<', '2026-05-06')->sql();
  assert_sql('Date < col < lower', $sql, "/E\.StartDateTime\s*<\s*'2026-05-06 00:00:00'/", true);
}

// Date <= '2026-05-06'
{
  $sql = make_term('Date', '<=', '2026-05-06')->sql();
  assert_sql('Date <= col < upper', $sql, "/E\.StartDateTime\s*<\s*'2026-05-07 00:00:00'/", true);
}

// Date != '2026-05-06'
{
  $sql = make_term('Date', '!=', '2026-05-06')->sql();
  assert_sql('Date != or-with-bounds', $sql,
    "/E\.StartDateTime\s*<\s*'2026-05-06 00:00:00'\s*OR\s*E\.StartDateTime\s*>=\s*'2026-05-07 00:00:00'/", true);
}

// Date IN
{
  $sql = make_term('Date', 'IN', '2026-05-06,2026-05-08')->sql();
  assert_sql('Date IN no to_days', $sql, '/to_days/i', false);
  assert_sql('Date IN includes first', $sql, "/E\.StartDateTime\s*>=\s*'2026-05-06 00:00:00'/", true);
  assert_sql('Date IN includes second', $sql, "/E\.StartDateTime\s*>=\s*'2026-05-08 00:00:00'/", true);
}

// Date = CURDATE()
{
  $sql = make_term('Date', '=', 'CURDATE()')->sql();
  assert_sql('Date = CURDATE() lower', $sql, '/E\.StartDateTime\s*>=\s*CURDATE\(\)/', true);
  assert_sql('Date = CURDATE() upper', $sql, '/E\.StartDateTime\s*<\s*CURDATE\(\)\s*\+\s*INTERVAL\s+1\s+DAY/i', true);
}

// Sanity: StartDateTime (non-date attr) unchanged
{
  $sql = make_term('StartDateTime', '>=', '2026-05-06 09:42:56')->sql();
  assert_sql('StartDateTime unchanged', $sql, "/E\.StartDateTime\s*>=\s*'2026-05-06 09:42:56'/", true);
}

echo "\n$passes passed, $failures failed.\n";
exit($failures ? 1 : 0);
