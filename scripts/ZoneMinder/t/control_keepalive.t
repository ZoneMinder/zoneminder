use strict;
use warnings;
use Test::More tests => 14;

require_ok('ZoneMinder::Control');

# zmcontrol used to send keepAlive only when its select() timed out. A monitor
# under steady command traffic never reaches that branch, so it never pinged,
# and the camera expired the session while the monitor was at its busiest.
# Observed on 2026-09-11: monitor 1 pinged twice while idle, then took a
# command every ~3s for three minutes and lost its session 181s after the last
# ping. Timing from the last ping is what fixes it.

my $c = bless {}, 'ZoneMinder::Control';

# The first call sets the baseline instead of firing: open() has just
# established the session, so an immediate ping would be wasted.
is($c->keepAliveDue(30, 1000), 0, 'nothing is due on the first check');
is($c->keepAliveDue(30, 1029), 0, 'nor one second before the interval');
is($c->keepAliveDue(30, 1030), 1, 'due exactly on the interval');
is($c->keepAliveDue(30, 1031), 0, 'and not again immediately after');
is($c->keepAliveDue(30, 1059), 0, 'the clock restarts from the ping');
is($c->keepAliveDue(30, 1060), 1, 'so the next one is an interval later');

# The whole point: a busy monitor pings on schedule. These stand in for
# commands arriving every few seconds, which never let select() time out.
{
  my $busy = bless {}, 'ZoneMinder::Control';
  $busy->keepAliveDue(30, 0);
  my $pings = 0;
  for my $t (1 .. 180) {          # three minutes, a check every second
    $pings++ if $busy->keepAliveDue(30, $t);
  }
  is($pings, 6, 'six pings in three minutes regardless of command traffic');
}

# Before the fix the camera expired at ~180s of no ping. Nothing may ever
# leave a gap that long.
{
  my $o = bless {}, 'ZoneMinder::Control';
  $o->keepAliveDue(30, 0);
  my $last = 0;
  my $worst = 0;
  for my $t (1 .. 600) {
    if ($o->keepAliveDue(30, $t)) { $worst = $t - $last if $t - $last > $worst; $last = $t }
  }
  cmp_ok($worst, '<=', 30, 'no gap between pings ever exceeds the interval');
  cmp_ok($worst, '<', 180, 'and stays well inside the observed session timeout');
}

# A clock that jumps backwards (ntp step) must not wedge it forever.
{
  my $o = bless {}, 'ZoneMinder::Control';
  $o->keepAliveDue(30, 5000);
  is($o->keepAliveDue(30, 4000), 0, 'a backwards jump does not fire immediately');
  is($o->keepAliveDue(30, 4030), 1, 'and it recovers an interval after the new time');
}

# A long stall produces one ping, not a burst making up for lost time.
{
  my $o = bless {}, 'ZoneMinder::Control';
  $o->keepAliveDue(30, 0);
  is($o->keepAliveDue(30, 100000), 1, 'a long gap fires once');
  is($o->keepAliveDue(30, 100000), 0, 'and not repeatedly at the same instant');
}
