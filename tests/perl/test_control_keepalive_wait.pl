#!/usr/bin/perl
#
# The zmcontrol server waited a full keepAlive interval in select() after every
# command, so a command arriving just before a ping was due pushed the next ping
# almost two intervals after the last one (Dahua sessions expire at ~60s).
#
# Run as: perl tests/perl/test_control_keepalive_wait.pl
#
use strict;
use warnings;
use FindBin;

# Control.pm needs a generated ZoneMinder::Base and a database driver, so take the
# keepAlive subs out of the source and compile them on their own.
my $src = do { local (@ARGV, $/) = ("$FindBin::Bin/../../scripts/ZoneMinder/lib/ZoneMinder/Control.pm"); <> };
my $code = join("\n", map { $src =~ /^(sub $_ \{.*?^\})/ms ? $1 : '' } qw(keepAliveDue keepAliveWait));
eval "package ZoneMinder::Control; use strict; use warnings; $code; 1" or die $@;

my $failures = 0;
sub check {
  my ($name, $got, $want) = @_;
  if ($got eq $want) { print "ok - $name\n"; }
  else { $failures++; print "FAIL - $name\n  got:  $got\n  want: $want\n"; }
}

my $c = bless {}, 'ZoneMinder::Control';
my $interval = 30;

# Simulate the server loop: a command at t=29, ping check at each loop top, and
# a select that waits what the code asks for. Returns the longest gap between pings.
sub max_gap {
  my $use_wait = shift;
  my $s = bless {}, 'ZoneMinder::Control';
  my ($now, $last, $gap) = (0, 0, 0);
  my @commands = (29);
  $s->keepAliveDue($interval, $now);    # baseline
  while ($now < 200) {
    if ($s->keepAliveDue($interval, $now)) { $gap = $now - $last if $now - $last > $gap; $last = $now; }
    my $wait = $use_wait ? $s->keepAliveWait($interval, $now) : $interval;
    my $next = $now + $wait;
    if (@commands and $commands[0] <= $next) { $now = shift @commands; }
    else { $now = $next; }
  }
  return $gap;
}

check('keepAliveWait is the full interval before the baseline is set', $c->keepAliveWait(30, 100), 30);
$c->keepAliveDue(30, 100);
check('keepAliveWait is the time left until the ping is due', $c->keepAliveWait(30, 129), 1);
check('keepAliveWait after 10s', $c->keepAliveWait(30, 110), 20);
check('keepAliveWait after a clock step back', $c->keepAliveWait(30, 50), 30);
check('pings are never more than one interval apart', max_gap(1), $interval);

print $failures ? "FAILED\n" : "all passed\n";
exit($failures ? 1 : 0);
