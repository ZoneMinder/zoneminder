#!/usr/bin/perl
#
# Alarm state changes zmtrigger broadcasts to its clients.  An "off" must only
# follow an "on": PREALARM going back to IDLE never started an event, and the
# first successful read of a monitor whose zmc was not up when zmtrigger loaded
# it (no previous state) is not a transition either.
#
# Run as: perl tests/perl/test_zmtrigger_alarm_notifications.pl
#
use strict;
use warnings;
use FindBin;
require "$FindBin::Bin/zmtrigger_harness.pm";

my $r = run_zmtrigger(<<'EOM');
package ZmtSim;
our $max_ticks = 16;
our %rows = map { $_ => { Id => $_, Name => "m$_", Deleted => 0, Capturing => 'Always', Recording => 'None', ServerId => 0 } } 1 .. 4;
sub t { return $ZoneMinder::tick; }
# 1: prealarm that never turns into an alarm
# 2: zmc not running when zmtrigger starts, idle once it is
# 3: a real alarm, event 10, then alert, then idle
# 4: an event that started and ended between two passes
sub connect_ok { my $id = shift; return $id == 2 ? t() >= 3 : 1; }
sub mem {
  my ($id, $f) = @_;
  my $t = t();
  my ($state, $event) = (1, 0);
  if ($id == 1) { $state = ($t >= 3 && $t <= 4) ? 2 : 1; }
  elsif ($id == 3) {
    $event = $t >= 8 ? 10 : 0;
    $state = $t >= 12 ? 1 : $t >= 10 ? 4 : $t >= 8 ? 3 : 1;
  }
  elsif ($id == 4) { $event = $t >= 6 ? 20 : 5; }
  return $f eq 'state' ? $state : $event;
}
1;
EOM

my @out = map { s/\|\d{7}\|/|T|/r } grep { /^OUT / } @{$r->{lines}};
my %by;
foreach (@out) { /^OUT (\d+)\|/ and push @{$by{$1}}, $_; }
my $j = sub { join(',', map { s/^OUT //r } @{$by{$_[0]} || []}) };

check('daemon ran to the end', $r->{exit} . '/' . $r->{signal}, '0/0');
check('prealarm back to idle sends nothing', $j->(1), '');
check('first read of a late started monitor sends nothing', $j->(2), '');
check('a real alarm sends on then off', $j->(3), '3|on|T|10,3|off|T|10');
check('a missed event sends on then off', $j->(4), '4|on|T|20,4|off|T|20');
finish();
