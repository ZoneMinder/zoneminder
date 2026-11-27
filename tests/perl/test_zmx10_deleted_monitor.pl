#!/usr/bin/perl
# Monitors with Deleted = 1 must not be loaded, so their AlarmOutput devices
# must never be switched even when the shared memory state says ALARM.
use strict;
use warnings;
use FindBin;
use Cwd;
use vars qw($Dir);
require "$FindBin::Bin/zmx10_harness.pl";

zmx10_start(rows => [
  {Id=>1, Name=>'live', Deleted=>0, Activation=>'', AlarmInput=>'', AlarmOutput=>'7'},
  {Id=>2, Name=>'gone', Deleted=>1, Activation=>'', AlarmInput=>'', AlarmOutput=>'8'},
]);
sleep(1.5);                       # first poll records the initial state
zmx10_set_state(3); sleep(1.5);   # ALARM for every monitor in the stub

check('live monitor switched its appliance on', zmx10_calls('APPLIANCE_ON') >= 1);
my $calls = read_file("$Dir/calls.log");
check('deleted monitor appliance was never switched on',
  scalar(grep { /^APPLIANCE_ON 8$/ } split(/\n/, $calls)) == 0);
finish();
