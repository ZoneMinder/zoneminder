#!/usr/bin/perl
# An X10 activation string with a limit on the 'off' side must restart the
# monitor when the limit elapses, mirroring the 'on' side:
#   '5-2'  OFF -> monitor disabled, enabled again after 2 seconds
#   '5+2'  ON  -> monitor enabled, disabled again after 2 seconds
#   '5'    OFF -> monitor disabled and stays disabled (no limit)
use strict;
use warnings;
use FindBin;
use Cwd;
require "$FindBin::Bin/zmx10_harness.pl";
our $Dir;

# The harness reads the template from its fixed worktree path. Let a test run
# against another copy (e.g. the original from git) without changing the
# harness or the worktree: ZMX10_SRC replaces just that read.
my $harness_read_file = \&read_file;
{
  no warnings 'redefine';
  *read_file = sub {
    my $path = shift;
    return $harness_read_file->($ENV{ZMX10_SRC})
      if $ENV{ZMX10_SRC} && $path =~ m{/scripts/zmx10\.pl\.in$};
    return $harness_read_file->($path);
  };
}

sub restart_with {
  my ($activation) = @_;
  zmx10_stop();
  system('rm', '-rf', $Dir) if $Dir;
  zmx10_start(rows => [ {Id=>1, Name=>'m1', Activation=>$activation, AlarmInput=>'', AlarmOutput=>''} ]);
  sleep(1);
}

# '5-2': the OFF list holds stop_active with a 2 second limit. Disabling the
# monitor must schedule start_active so it comes back on, exactly once.
restart_with('5-2');
zmx10_event('A 5 OFF');
sleep(1);
check("'5-2' OFF disables the monitor", zmx10_calls('DISABLE') == 1);
check("'5-2' monitor is not enabled before the limit", zmx10_calls('ENABLE') == 0);
sleep(3);
check("'5-2' monitor is enabled again after the limit", zmx10_calls('ENABLE') == 1);
check("'5-2' monitor is disabled exactly once", zmx10_calls('DISABLE') == 1);

# '5+2': unchanged behaviour on the ON side.
restart_with('5+2');
zmx10_event('A 5 ON');
sleep(1);
check("'5+2' ON enables the monitor", zmx10_calls('ENABLE') == 1);
check("'5+2' monitor is not disabled before the limit", zmx10_calls('DISABLE') == 0);
sleep(3);
check("'5+2' monitor is disabled again after the limit", zmx10_calls('DISABLE') == 1);
check("'5+2' monitor is enabled exactly once", zmx10_calls('ENABLE') == 1);

# '5': no limit, so OFF disables and no reverse task is scheduled.
restart_with('5');
zmx10_event('A 5 OFF');
sleep(1);
check("plain '5' OFF disables the monitor", zmx10_calls('DISABLE') == 1);
sleep(3);
check("plain '5' monitor stays disabled (no pending task)", zmx10_calls('DISABLE') == 1);
check("plain '5' monitor is never enabled", zmx10_calls('ENABLE') == 0);

finish();
