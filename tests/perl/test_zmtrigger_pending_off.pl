#!/usr/bin/perl
#
# "<id>|off+<N>" schedules an off for N seconds later. A later "<id>|on" or
# "<id>|cancel" only dropped pending "<id>|cancel" actions, so the scheduled off
# still fired afterwards and ended the new alarm (or undid the cancel). They now
# drop the pending off too.
#
# Run as: perl tests/perl/test_zmtrigger_pending_off.pl
#
use strict;
use warnings;
use FindBin;
require "$FindBin::Bin/zmtrigger_harness.pm";

# One pass of the main loop is a quarter of a virtual second.
sub scenario {
  my ($second) = @_;
  return <<"EOM";
package ZmtSim;
our \$max_ticks = 60;
our %rows = (
  1 => { Id => 1, Name => 'door', Deleted => 0, Capturing => 'Always', Recording => 'None', ServerId => 0 },
);
sub connect_ok { return 1; }
sub mem { my (\$id, \$f) = \@_; return \$f eq 'state' ? 1 : 0; }
our \@inbox = (
  [ 2, '1|off+5|0|door|closed' ],
  [ 6, '$second' ],
);
1;
EOM
}

# off+5 then on: the alarm started by "on" must not be switched off by the old timer
my $r = run_zmtrigger(scenario('1|on|9|door|opened'));
my @acts = grep { /^ACT (on|off|cancel) 1/ } @{$r->{lines}};
my $on = (grep { $acts[$_] =~ /^ACT on 1/ } 0..$#acts)[0];
check('daemon ran to the end (on)', $r->{exit} . '/' . $r->{signal}, '0/0');
check('the on was applied', defined($on) ? 1 : 0, 1);
check('the pending off does not fire after a later on',
  scalar(grep { /^ACT off 1/ } @acts[($on // 0) .. $#acts]), 0);

# off+5 then cancel: nothing may fire afterwards
$r = run_zmtrigger(scenario('1|cancel'));
@acts = grep { /^ACT (on|off|cancel) 1/ } @{$r->{lines}};
check('daemon ran to the end (cancel)', $r->{exit} . '/' . $r->{signal}, '0/0');
check('the pending off does not fire after a cancel', scalar(grep { /^ACT off 1/ } @acts), 0);
finish();
