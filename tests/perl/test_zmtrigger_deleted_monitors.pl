#!/usr/bin/perl
#
# zmtrigger must not keep deleted monitors.  The Monitors column is "Deleted"
# but ZoneMinder::Object only adds its automatic filter for a lowercase
# "deleted" column, so loadMonitors and the single monitor reload have to ask
# for Deleted=0 themselves.  Otherwise a deleted monitor stays in %monitors,
# fails connect() on every pass and is re-read from the database every
# MONITOR_RETRY_INTERVAL seconds for as long as the daemon runs.
#
# Run as: perl tests/perl/test_zmtrigger_deleted_monitors.pl
#
use strict;
use warnings;
use FindBin;
require "$FindBin::Bin/zmtrigger_harness.pm";

my $r = run_zmtrigger(<<'EOM');
package ZmtSim;
our $max_ticks = 1500;    # 375 virtual seconds, one full reload interval
our %rows = (
  1 => { Id => 1, Name => 'live', Deleted => 0, Capturing => 'Always', Recording => 'None', ServerId => 0 },
  2 => { Id => 2, Name => 'gone', Deleted => 1, Capturing => 'Always', Recording => 'None', ServerId => 0 },
  3 => { Id => 3, Name => 'later', Deleted => 0, Capturing => 'Always', Recording => 'None', ServerId => 0 },
);
our %dead;
sub connect_ok { return !$dead{$_[0]} && !$rows{$_[0]}{Deleted}; }
sub mem { my ($id, $f) = @_; return $f eq 'state' ? 1 : 0; }
sub tick {
  # Monitor 3 is deleted from the web ui after the daemon has started
  if ($_[0] == 100) { $rows{3}{Deleted} = 1; $dead{3} = 1; }
}
1;
EOM

my @finds = grep { /^FIND / } @{$r->{lines}};
my @all = grep { /FROM Monitors/ && !/WHERE `?Id`?=\?/ } @finds;
my @one = grep { /WHERE.*\bId\b/ } @finds;
my @ids = map { /\| (\d+)$/ ? $1 : () } @one;
my %per; $per{$_}++ for @ids;

check('daemon ran to the end', $r->{exit} . '/' . $r->{signal}, '0/0');
check('full loads ask for Deleted=0', scalar(grep { /Deleted/ } @all), scalar(@all));
# At least the start and the 300s reload; SIGTERM delivery can let one more
# reload cycle through before the daemon stops, so 3 is fine too.
check('there were full loads at start and after 300s', (scalar(@all) >= 2 && scalar(@all) <= 3) ? 1 : 0, 1);
check('deleted monitor 2 is never reloaded one by one', $per{2} // 0, 0);
check('monitor 3 deleted at runtime is reloaded at most once', ($per{3} // 0) <= 1 ? 1 : 0, 1);
check('single monitor reloads ask for Deleted=0', scalar(grep { /Deleted/ } @one), scalar(@one));
finish();
