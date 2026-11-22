#!/usr/bin/perl
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

BEGIN {
  $INC{'ZoneMinder/Base.pm'} = 1;
  $INC{'ZoneMinder/Config.pm'} = 1;
  $INC{'ZoneMinder/Logger.pm'} = 1;
  $INC{'ZoneMinder/Memory.pm'} = 1;
  package ZoneMinder::Base;
  our $VERSION = '0';
  package ZoneMinder::Config;
  our %Config;
  sub import { no strict 'refs'; *{caller().'::Config'} = \%Config; }
  package ZoneMinder::Logger;
  sub INFO { return 1; }
  sub logLevel { return 0; }
  sub Debug { return 1; }
  sub Info { return 1; }
  sub Warning { return 1; }
  sub Error { return 1; }
  sub Fatal { return 1; }
  sub import {
    no strict 'refs';
    my $caller = caller();
    *{$caller.'::'.$_} = \&{$_} for qw(Debug Info Warning Error Fatal INFO);
  }
  package ZoneMinder::Memory;
  sub zmMemVerify { return 1; }
  sub zmMemKey { return undef; }
  sub zmMemInvalidate { return 1; }
}

use ZoneMinder::Monitor;

my $failures = 0;
my $passes = 0;

sub check {
  my ($name, $got, $want) = @_;
  if ((defined $got ? $got : '') eq (defined $want ? $want : '')) {
    $passes++;
    print "ok - $name\n";
  } else {
    $failures++;
    print "FAIL - $name\n";
    print '  got:  '.(defined $got ? $got : '(undef)')."\n";
    print '  want: '.(defined $want ? $want : '(undef)')."\n";
  }
}

# What zmc keeps in shared_data->analysing: it sets ANALYSING_NONE (1) on a
# suspend and puts the monitor's own setting, ANALYSING_ALWAYS (2), back on a
# resume.
my $analysing = 2;
my @sent;
{
  no warnings qw(redefine once);
  *ZoneMinder::Memory::zmMemRead = sub { return $analysing; };
  *ZoneMinder::Memory::zmMonitorSuspend = sub { push @sent, 'suspend'; $analysing = 1; };
  *ZoneMinder::Memory::zmMonitorResume = sub { push @sent, 'resume'; $analysing = 2; };
  *ZoneMinder::Monitor::usleep = sub ($) { return; };
}

my $monitor = bless {Id => 1, Name => 'test', Capturing => 'Always', Analysing => 'Always'}, 'ZoneMinder::Monitor';

$monitor->suspendMotionDetection();
check('suspend is sent to a running monitor', join(',', @sent), 'suspend');
check('the monitor is suspended', $analysing, 1);

@sent = ();
$monitor->resumeMotionDetection();
check('resume is sent to a suspended monitor', join(',', @sent), 'resume');
check('the monitor is analysing again', $analysing, 2);

@sent = ();
$monitor->resumeMotionDetection();
check('resume is not sent to a monitor that is already analysing', join(',', @sent), '');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
