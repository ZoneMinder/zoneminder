#!/usr/bin/perl
# Activation '5+4': X10 unit 5 ON enables the monitor for 4 seconds. A second
# ON while the timer is running must extend it, not leave the first timer in
# place (which would disable the monitor early and a second time).
use strict;
use warnings;
use FindBin;
use Cwd;
require "$FindBin::Bin/zmx10_harness.pl";

zmx10_start(rows => [ {Id=>1, Name=>'m1', Activation=>'5+4', AlarmInput=>'', AlarmOutput=>''} ]);
sleep(1);
zmx10_event('A 5 ON');            # t=0, expires t=4
sleep(2.2);
zmx10_event('A 5 ON');            # t=2.2, should now expire t=6.2
sleep(3);                         # t=5.2
check('monitor enabled twice', zmx10_calls('ENABLE') == 2);
check('first timer does not disable the monitor early', zmx10_calls('DISABLE') == 0);
sleep(4);                         # t=9.2
check('monitor disabled exactly once after the extended timer', zmx10_calls('DISABLE') == 1);
finish();
