#!/usr/bin/perl
#
# "<id>|disable+<N>" disables a monitor and schedules an enable N seconds
# later (and enable+N the reverse). A later plain "<id>|disable" or
# "<id>|enable" left that timer pending, so it still fired afterwards and
# undid the newer request. Any enable or disable now drops it.
#
# Run as: perl tests/perl/test_zmtrigger_pending_enable.pl
#
use strict;
use warnings;
use FindBin;
require "$FindBin::Bin/zmtrigger_harness.pm";

# One pass of the main loop is a quarter of a virtual second.
sub scenario {
  my ($first, $second) = @_;
  return <<"EOM";
package ZmtSim;
our \$max_ticks = 60;
our %rows = (
  1 => { Id => 1, Name => 'door', Deleted => 0, Capturing => 'Always', Recording => 'Always', ServerId => 0 },
);
sub connect_ok { return 1; }
sub mem { my (\$id, \$f) = \@_; return \$f eq 'state' ? 1 : 0; }
our \@inbox = (
  [ 2, '$first' ],
  [ 6, '$second' ],
);
1;
EOM
}

sub acts {
  my ($r) = @_;
  return join(',', map { /^ACT (\w+) 1/ ? $1 : () } @{$r->{lines}});
}

# disable+5 then disable: the monitor must stay disabled
my $r = run_zmtrigger(scenario('1|disable+5', '1|disable'));
check('daemon ran to the end (disable)', $r->{exit} . '/' . $r->{signal}, '0/0');
check('the timed enable does not fire after a later disable', acts($r), 'disable,disable');

# enable+5 then enable: the monitor must stay enabled
$r = run_zmtrigger(scenario('1|enable+5', '1|enable'));
check('daemon ran to the end (enable)', $r->{exit} . '/' . $r->{signal}, '0/0');
check('the timed disable does not fire after a later enable', acts($r), 'enable,enable');

# disable+5 then enable+8: only the newer timer fires
$r = run_zmtrigger(scenario('1|disable+5', '1|enable+8'));
check('only the newer timer fires', acts($r), 'disable,enable,disable');

# a timer with nothing after it still fires
$r = run_zmtrigger(scenario('1|disable+5', ''));
check('a timed enable still fires on its own', acts($r), 'disable,enable');
finish();
