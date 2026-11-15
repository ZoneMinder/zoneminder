#!/usr/bin/perl
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

BEGIN {
  $INC{'ZoneMinder/Base.pm'} = 1;
  $INC{'ZoneMinder/Config.pm'} = 1;
  $INC{'ZoneMinder/Logger.pm'} = 1;
  package ZoneMinder::Base;
  our $VERSION = '0';
  package ZoneMinder::Config;
  our %Config = (ZM_PATH_UNAME => 'uname');
  sub import { no strict 'refs'; *{caller().'::Config'} = \%Config; }
  package ZoneMinder::Logger;
  sub import {
    no strict 'refs';
    my $caller = caller();
    *{$caller.'::'.$_} = sub { return 1; } for qw(Debug Info Warning Error Fatal);
  }
}

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

# Memory.pm is generated from Memory.pm.in, so build it from the template.
open(my $fh, '<', "$FindBin::Bin/../../scripts/ZoneMinder/lib/ZoneMinder/Memory.pm.in") or die "Can't open Memory.pm.in: $!";
my $source = do { local $/; <$fh> };
close($fh);
$source =~ s/\@ENABLE_MMAP\@/no/g;
eval "$source; 1" or die $@;

# The bits zmc acts on in shared_data->action (Monitor::Action in zm_monitor.h).
use constant SUSPEND => 0x10;
use constant RESUME => 0x20;

my $action = 0;
{
  no warnings qw(redefine once);
  *ZoneMinder::Memory::zmMemRead = sub { return $action; };
  *ZoneMinder::Memory::zmMemWrite = sub { $action = $_[1]->{'shared_data:action'}; return 1; };
}
my $monitor = {Id => 1};

$action = 0;
ZoneMinder::Memory::zmMonitorEnable($monitor);
check('enable asks zmc to resume the monitor', $action, RESUME);

$action = 0;
ZoneMinder::Memory::zmMonitorDisable($monitor);
check('disable asks zmc to suspend the monitor', $action, SUSPEND);

$action = 0;
ZoneMinder::Memory::zmMonitorSuspend($monitor);
check('suspend asks zmc to suspend the monitor', $action, SUSPEND);

$action = 0;
ZoneMinder::Memory::zmMonitorResume($monitor);
check('resume asks zmc to resume the monitor', $action, RESUME);

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
