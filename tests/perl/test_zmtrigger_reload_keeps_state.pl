#!/usr/bin/perl
#
# A full monitor reload (a message for an unknown monitor id, enable/disable,
# the periodic reload) must not forget which state each monitor was last seen
# in.  If it re-reads the state from shared memory, an alarm that ended since
# the last poll never sends its "off" and one that started never sends its
# "on" to the clients.
#
# Run as: perl tests/perl/test_zmtrigger_reload_keeps_state.pl
# ZMT_REPO=<dir> points it at another copy of the scripts (e.g. the original).
#
use strict;
use warnings;
use FindBin;
require "$FindBin::Bin/zmtrigger_harness.pm";

my $r = run_zmtrigger(<<'EOM');
package ZmtSim;
our $max_ticks = 14;
our %rows = map { $_ => { Id => $_, Name => "m$_", Deleted => 0, Capturing => 'Always', Recording => 'None', ServerId => 0 } } 1 .. 2;
# A stray message for a monitor that does not exist makes zmtrigger reload all
# monitors, once while monitor 1's alarm has just ended, once while monitor 2's
# has just started.
our @inbox = ([6, '99|on|1|x'], [10, '99|on|1|x']);
sub t { return $ZoneMinder::tick; }
sub mem {
  my ($id, $f) = @_;
  my $t = t();
  my ($state, $event) = (1, 0);
  if ($id == 1) { $event = $t >= 3 ? 10 : 0; $state = ($t >= 3 && $t <= 5) ? 3 : 1; }
  if ($id == 2) { $event = $t >= 10 ? 20 : 0; $state = $t >= 10 ? 3 : 1; }
  return $f eq 'state' ? $state : $event;
}
1;
EOM

my %by;
foreach (grep { /^OUT / } @{$r->{lines}}) {
  my $l = s/\|\d{7}\|/|T|/r;
  $l =~ /^OUT (\d+)\|/ and push @{$by{$1}}, $l =~ s/^OUT //r;
}
check('daemon ran to the end', $r->{exit} . '/' . $r->{signal}, '0/0');
check('alarm that ended just before a reload still sends off', join(',', @{$by{1} || []}), '1|on|T|10,1|off|T|10');
check('alarm that started just before a reload still sends on', join(',', @{$by{2} || []}), '2|on|T|20');
finish();
