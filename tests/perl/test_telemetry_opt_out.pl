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

# The main loop must not exit when telemetry is off (zmdc would restart it
# every 30 seconds) and must not upload; it sleeps and checks again.
my ($loop) = $source =~ /^(my \$lastCheck = .*?^\} # end while)/ms;
check('main loop is found', defined $loop);
if (defined $loop) {
  $loop =~ s/\bsleep\(/tsleep(/g;
  $loop =~ s/\bexit\(/texit(/g;
  my @enabled_seq;
  my ($uploads, $sleeps, @infos) = (0, 0);
  {
    package LoopStub;
    our $dbh_value;
    sub ping { 1 }
    sub selectrow_array { return shift @enabled_seq }
  }
  our %Config = (ZM_TELEMETRY_LAST_UPLOAD => 0);
  our ($interval, $force) = (10, 0);
  no warnings qw(redefine once);
  *main::tsleep = sub { die "stop\n" if ++$sleeps > 3 };
  *main::texit = sub { die "exit\n" };
  *main::Info = sub { push @infos, $_[0] };
  *main::Debug = *main::Warning = sub {};
  *main::collectData = sub {};
  *main::jsonEncode = sub { '{}' };
  *main::sendData = sub { $uploads++; 1 };
  *ZoneMinder::Database::zmDbDo = sub {};
  my $code = 'my $dbh = bless {}, "LoopStub"; my $interval = 10; my $force = 0; our %Config; '.$loop.' 1;';
  @enabled_seq = (0, 0, 1, 0);
  $sleeps = 0;
  my $ok = eval $code;
  check('loop keeps running while telemetry is off (ends only via the test stop)', $@, "stop\n");
  check('loop uploaded only while on', $uploads, 1);
  check('state changes are logged once each', scalar(@infos), 3);
  @enabled_seq = (0, 0, 0, 0);
  ($uploads, $sleeps, @infos) = (0, 0);
  eval $code;
  check('loop uploads nothing while off', $uploads, 0);
  check('off is logged once, not every pass', scalar(@infos), 1);
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
