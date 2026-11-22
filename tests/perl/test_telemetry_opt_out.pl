#!/usr/bin/perl
#
# telemetryEnabled must treat the config value as opt-in: only '1' enables
# telemetry, and the force argument must bypass the database lookup.
#
# Run as: perl tests/perl/test_telemetry_opt_out.pl
#
use strict;
use warnings;
use FindBin;

my $failures = 0;
my $passes = 0;

sub check {
  my ($name, $got, $want) = @_;
  my $ok = @_ >= 3
    ? (defined $got ? $got : '<undef>') eq (defined $want ? $want : '<undef>')
    : ($got ? 1 : 0);
  if ($ok) {
    $passes++;
    print "ok - $name\n";
  } else {
    $failures++;
    print "FAIL - $name\n  got:  ".(defined $got ? $got : '<undef>')
      .(@_ >= 3 ? "\n  want: ".(defined $want ? $want : '<undef>') : '')."\n";
  }
}

my $root = $ENV{ZM_TEST_ROOT} || "$FindBin::Bin/../..";

my $source = do {
  open(my $fh, '<', "$root/scripts/zmtelemetry.pl.in")
    or die "Can't open zmtelemetry.pl.in: $!";
  local $/;
  <$fh>;
};

my ($block) = $source =~ /^(sub telemetryEnabled \{.*?^\})/ms;

check('telemetryEnabled is defined in zmtelemetry.pl.in', defined $block);
unless (defined $block) {
  print "\n$passes passed, $failures failed\n";
  exit 1;
}

eval $block;
die $@ if $@;

package StubDbh;

sub new {
  my ($c, $v) = @_;
  bless {v => $v, calls => 0}, $c;
}

sub selectrow_array {
  my $s = shift;
  $s->{calls}++;
  return ($s->{v});
}

package main;

my $dbh = StubDbh->new('1');
check('enabled when value is 1', telemetryEnabled($dbh, 0) ? 1 : 0, 1);

$dbh = StubDbh->new('0');
check('disabled when value is 0', telemetryEnabled($dbh, 0) ? 1 : 0, 0);

$dbh = StubDbh->new('');
check('disabled when value is empty', telemetryEnabled($dbh, 0) ? 1 : 0, 0);

$dbh = StubDbh->new('0');
check('force enables despite stored 0', telemetryEnabled($dbh, 1) ? 1 : 0, 1);
check('force skips database lookup', $dbh->{calls}, 0);

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
