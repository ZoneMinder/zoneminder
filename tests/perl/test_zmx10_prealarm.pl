#!/usr/bin/perl
# A monitor passes IDLE -> PREALARM -> ALARM -> IDLE. The AlarmOutput device
# must be switched on when the alarm starts and off when it ends, even though
# the poll sees PREALARM before ALARM.
use strict;
use warnings;
use FindBin;
use Cwd;
require "$FindBin::Bin/zmx10_harness.pl";

zmx10_start(rows => [ {Id=>1, Name=>'m1', Activation=>'', AlarmInput=>'', AlarmOutput=>'7'} ]);
sleep(1.5);                       # first poll records the initial state
zmx10_set_state(2); sleep(1.5);   # PREALARM
zmx10_set_state(3); sleep(1.5);   # ALARM
check('device 7 switched on when alarm starts after prealarm', zmx10_calls('APPLIANCE_ON') == 1);
zmx10_set_state(1); sleep(1.5);   # IDLE
check('device 7 switched off when alarm ends', zmx10_calls('APPLIANCE_OFF') == 1);

# A false alarm (PREALARM -> IDLE) never reached ALARM, so nothing fires.
my $off_before = zmx10_calls('APPLIANCE_OFF');
zmx10_set_state(2); sleep(1.5);
zmx10_set_state(1); sleep(1.5);
check('prealarm that never alarms does not switch the device off', zmx10_calls('APPLIANCE_OFF') == $off_before);
finish();
