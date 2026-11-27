#!/usr/bin/perl
#
# zmc maps and validates the shared data (with a fresh heartbeat) before it
# records its startup time. In that window last_write_time and startup_time are
# both 0, and zmwatch computed "no image since startup" as time() - 0, which is
# past any limit, so it restarted a capture daemon that was still coming up.
#
# The check pass over one monitor is extracted from scripts/zmwatch.pl.in and run
# with stubbed shared memory and zmdc. Set ZMWATCH_SOURCE to test another copy.
#
use strict;
use warnings;
use FindBin;

my $src = $ENV{ZMWATCH_SOURCE} // "$FindBin::Bin/../../scripts/zmwatch.pl.in";
open(my $fh, '<', $src) or die "Cannot open $src: $!";
my $text = do { local $/; <$fh> };
close $fh;

$text =~ /(foreach my \$monitor \(.*?\) \{\n.*?\} # end foreach monitor)/s
  or die "Cannot find the monitor loop in $src";
my $loop = $1;
$loop =~ s/^foreach my \$monitor \(.*?\) \{/foreach my \$monitor (\@main::monitors) {/;

our ($stall_grace, %Config, $start_time, %seen_healthy, %no_shared_data_since, %recovered_before);
our (@monitors, @restarts, %mem);
sub Debug {} sub Info {} sub Warning {} sub Error {}
sub WarningImportance {}
sub zmMemVerify { return 1; }
sub zmMemRead { return $main::mem{$_[1]}; }
sub zmGetLastWriteTime { return $main::mem{'shared_data:last_write_time'}; }
sub zmGetLastReadTime { return $main::mem{'shared_data:last_read_time'}; }
sub zmGetStartupTime { return $main::mem{'shared_data:startup_time'}; }
sub close_abandoned_events {}

package FakeMonitor;
sub new { my ($c, %a) = @_; return bless {Id => 1, Name => 'cam', Type => 'Ffmpeg', Capturing => 'Always',
  Decoding => 'Always', Analysing => 'None', StartupDelay => 0, %a}, $c; }
sub control { push @main::restarts, $_[1]; }
sub ControlId { return 0; }
sub ImportanceNumber { return 0; }
sub WatchStartupLimit { return $_[1] + ($_[0]{StartupDelay} || 0); }
sub WatchImageLimit { return $_[1]; }

package main;
my $code = "sub run_pass { $loop\n}\n";
eval $code . "; 1" or die "Cannot compile extracted loop: $@";

my ($failures, $passes) = (0, 0);
sub check {
  my ($name, $got, $want) = @_;
  if ($got eq $want) { $passes++; print "ok - $name\n"; }
  else { $failures++; print "FAIL - $name\n  got:  $got\n  want: $want\n"; }
}

sub pass {
  my (%m) = @_;
  %Config = (ZM_WATCH_MAX_DELAY => 45, ZM_WATCH_CHECK_INTERVAL => 10);
  $start_time = time() - 3600;   # zmwatch has been running a long time
  %seen_healthy = (1 => 1); %no_shared_data_since = (); %recovered_before = ();
  $stall_grace = 0;
  @restarts = ();
  %mem = ('shared_data:heartbeat_time' => time(), %m);
  @monitors = (FakeMonitor->new());
  run_pass();
  return scalar @restarts;
}

# zmc has validated the shared data, but not set startup_time yet.
check('no restart while zmc has not set its startup time',
  pass('shared_data:last_write_time' => 0, 'shared_data:startup_time' => 0), 0);

# A zmc that set its startup time long ago and never got an image is stuck.
check('restart when no image long after the startup time',
  pass('shared_data:last_write_time' => 0, 'shared_data:startup_time' => time() - 600), 1);

# A zmc that set its startup time just now is inside its limit.
check('no restart shortly after the startup time',
  pass('shared_data:last_write_time' => 0, 'shared_data:startup_time' => time() - 5), 0);

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
