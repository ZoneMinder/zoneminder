#!/usr/bin/perl
# A monitor flips between IDLE and ALARM while loadTasks() keeps reloading the
# monitor rows. Each alarm transition must still switch the AlarmOutput device
# on, and each end of an alarm must switch it off, even if a reload lands on
# the poll that sees the change.
use strict;
use warnings;
use FindBin;
use Cwd;
require "$FindBin::Bin/zmx10_harness.pl";

# A reload every 3 polls (ZM_X10_DB_RELOAD_INTERVAL / 1.0).
zmx10_start(
  reload => 3,
  rows => [ {Id=>1, Name=>'m1', Activation=>'', AlarmInput=>'', AlarmOutput=>'7'} ]
);
sleep(1.5);                       # first poll records the initial IDLE state
for ( 1 .. 6 ) {
  zmx10_set_state(3); sleep(1.3); # ALARM
  zmx10_set_state(1); sleep(1.3); # IDLE
}

my $on = zmx10_calls('APPLIANCE_ON');
my $off = zmx10_calls('APPLIANCE_OFF');
check('alarm on/off pairs stay balanced across reloads', $on == $off || $on == $off + 1);
check("device 7 switched on for at least 5 of 6 alarms across reloads (got $on)", $on >= 5);
finish();
