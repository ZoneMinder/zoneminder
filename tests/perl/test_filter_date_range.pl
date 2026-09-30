#!/usr/bin/perl
#
# Tests that ZoneMinder::Filter generates sargable range queries for
# Date/StartDate/EndDate attrs instead of to_days(column) which defeats
# the index on StartDateTime.
#
# Run as: sudo -u www-data perl tests/perl/test_filter_date_range.pl
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

use ZoneMinder::Filter;

my $failures = 0;
my $passes = 0;

sub make_filter {
  my ($terms_json) = @_;
  return bless {
    Id => 1,
    Name => 'test',
    Query_json => qq({"terms":$terms_json}),
  }, 'ZoneMinder::Filter';
}

sub assert_sql_matches {
  my ($name, $sql, $regex, $should_match) = @_;
  my $matched = $sql =~ $regex;
  my $ok = $should_match ? $matched : !$matched;
  if ($ok) {
    $passes++;
    print "ok - $name\n";
  } else {
    $failures++;
    print "FAIL - $name\n";
    print "  SQL:    $sql\n";
    print "  Regex:  $regex (should ".($should_match?'match':'not match').")\n";
  }
}

# === Date attr, equality ===
{
  my $f = make_filter(q([{"attr":"Date","op":"=","val":"2026-05-06"}]));
  my $sql = $f->Sql();
  assert_sql_matches('Date = uses range, no to_days on column',
    $sql, qr/to_days\s*\(\s*E\.StartDateTime/i, 0);
  assert_sql_matches('Date = lower bound StartDateTime >=',
    $sql, qr/E\.StartDateTime\s*>=\s*'2026-05-06 00:00:00'/, 1);
  assert_sql_matches('Date = upper bound StartDateTime <',
    $sql, qr/E\.StartDateTime\s*<\s*'2026-05-07 00:00:00'/, 1);
}

# === StartDate attr, equality ===
{
  my $f = make_filter(q([{"attr":"StartDate","op":"=","val":"2026-05-06"}]));
  my $sql = $f->Sql();
  assert_sql_matches('StartDate = uses range',
    $sql, qr/to_days\s*\(\s*E\.StartDateTime/i, 0);
  assert_sql_matches('StartDate = lower bound',
    $sql, qr/E\.StartDateTime\s*>=\s*'2026-05-06 00:00:00'/, 1);
}

# === EndDate attr, equality ===
{
  my $f = make_filter(q([{"attr":"EndDate","op":"=","val":"2026-05-06"}]));
  my $sql = $f->Sql();
  assert_sql_matches('EndDate = uses range on E.EndDateTime',
    $sql, qr/E\.EndDateTime\s*>=\s*'2026-05-06 00:00:00'/, 1);
  assert_sql_matches('EndDate = no to_days on E.EndDateTime',
    $sql, qr/to_days\s*\(\s*E\.EndDateTime/i, 0);
}

# === Date attr, >= ===
{
  my $f = make_filter(q([{"attr":"Date","op":">=","val":"2026-05-06"}]));
  my $sql = $f->Sql();
  assert_sql_matches('Date >= emits col >= lower bound',
    $sql, qr/E\.StartDateTime\s*>=\s*'2026-05-06 00:00:00'/, 1);
  assert_sql_matches('Date >= no to_days',
    $sql, qr/to_days/i, 0);
}

# === Date attr, > ===
{
  my $f = make_filter(q([{"attr":"Date","op":">","val":"2026-05-06"}]));
  my $sql = $f->Sql();
  assert_sql_matches('Date > emits col >= upper bound',
    $sql, qr/E\.StartDateTime\s*>=\s*'2026-05-07 00:00:00'/, 1);
}

# === Date attr, < ===
{
  my $f = make_filter(q([{"attr":"Date","op":"<","val":"2026-05-06"}]));
  my $sql = $f->Sql();
  assert_sql_matches('Date < emits col < lower bound',
    $sql, qr/E\.StartDateTime\s*<\s*'2026-05-06 00:00:00'/, 1);
}

# === Date attr, <= ===
{
  my $f = make_filter(q([{"attr":"Date","op":"<=","val":"2026-05-06"}]));
  my $sql = $f->Sql();
  assert_sql_matches('Date <= emits col < upper bound',
    $sql, qr/E\.StartDateTime\s*<\s*'2026-05-07 00:00:00'/, 1);
}

# === Date attr, != ===
{
  my $f = make_filter(q([{"attr":"Date","op":"!=","val":"2026-05-06"}]));
  my $sql = $f->Sql();
  assert_sql_matches('Date != emits OR with bounds',
    $sql, qr/E\.StartDateTime\s*<\s*'2026-05-06 00:00:00'\s*OR\s*E\.StartDateTime\s*>=\s*'2026-05-07 00:00:00'/, 1);
}

# === Date attr, IN ===
{
  my $f = make_filter(q([{"attr":"Date","op":"IN","val":"2026-05-06,2026-05-08"}]));
  my $sql = $f->Sql();
  assert_sql_matches('Date IN no to_days',
    $sql, qr/to_days/i, 0);
  assert_sql_matches('Date IN includes first range',
    $sql, qr/E\.StartDateTime\s*>=\s*'2026-05-06 00:00:00'/, 1);
  assert_sql_matches('Date IN includes second range',
    $sql, qr/E\.StartDateTime\s*>=\s*'2026-05-08 00:00:00'/, 1);
}

# === Date attr, CURDATE() ===
{
  my $f = make_filter(q([{"attr":"Date","op":"=","val":"CURDATE()"}]));
  my $sql = $f->Sql();
  assert_sql_matches('Date = CURDATE() uses CURDATE() bound',
    $sql, qr/E\.StartDateTime\s*>=\s*CURDATE\(\)/, 1);
  assert_sql_matches('Date = CURDATE() uses interval upper bound',
    $sql, qr/E\.StartDateTime\s*<\s*CURDATE\(\)\s*\+\s*INTERVAL\s+1\s+DAY/i, 1);
}

# === Sanity: DateTime (non-date attr) is unchanged ===
{
  my $f = make_filter(q([{"attr":"StartDateTime","op":">=","val":"2026-05-06 09:42:56"}]));
  my $sql = $f->Sql();
  assert_sql_matches('StartDateTime unchanged (no range expansion)',
    $sql, qr/E\.StartDateTime\s*>=\s*'2026-05-06 09:42:56'/, 1);
}

print "\n$passes passed, $failures failed.\n";
exit($failures ? 1 : 0);
