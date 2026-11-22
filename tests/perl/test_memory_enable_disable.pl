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

my %real = map { $_ => \&{"ZoneMinder::Memory::$_"} } qw(zmMemVerify zmMemRead zmMemWrite);

my $action = 0;
{
  no warnings qw(redefine once);
  *ZoneMinder::Memory::zmMemVerify = sub { return 1; };
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

# A monitor whose memory is not attached yet, as zmx10 leaves every monitor
# after reloading its tasks. As with the real backends, reading or writing a
# detached monitor fails and logs an error.
my $shm = '';
my @errors;
{
  no warnings qw(redefine once);
  *ZoneMinder::Memory::zmMemVerify = $real{zmMemVerify};
  *ZoneMinder::Memory::zmMemRead = $real{zmMemRead};
  *ZoneMinder::Memory::zmMemWrite = $real{zmMemWrite};
  *ZoneMinder::Memory::zmMemKey = sub { return $_[0]{attached}; };
  *ZoneMinder::Memory::zmMemAttach = sub { $_[0]{attached} = 1; return 1; };
  *ZoneMinder::Memory::zmMemDetach = sub { delete $_[0]{attached}; };
  *ZoneMinder::Memory::zmMemGet = sub {
    my ($m, $offset, $size) = @_;
    return substr($shm, $offset, $size) if $m->{attached};
    ZoneMinder::Memory::Error("Can't read from memory for monitor $$m{Id}");
    return undef;
  };
  *ZoneMinder::Memory::zmMemPut = sub {
    my ($m, $offset, $size, $data) = @_;
    return undef if !$m->{attached};
    substr($shm, $offset, $size) = $data;
    return 1;
  };
  *ZoneMinder::Memory::Error = sub { push @errors, $_[0]; return 1; };
}

foreach my $case (
  [ 'enable', \&ZoneMinder::Memory::zmMonitorEnable, RESUME ],
  [ 'disable', \&ZoneMinder::Memory::zmMonitorDisable, SUSPEND ],
  [ 'suspend', \&ZoneMinder::Memory::zmMonitorSuspend, SUSPEND ],
  [ 'resume', \&ZoneMinder::Memory::zmMonitorResume, RESUME ],
) {
  my ($name, $call, $bit) = @$case;
  # zmc is running and has a reload request (0x04) pending
  {
    no warnings 'once';
    $shm = "\0" x $ZoneMinder::Memory::mem_size;
  }
  my $writer = {Id => 1, Name => 'Cam', attached => 1};
  ZoneMinder::Memory::zmMemWrite($writer, {
    'shared_data:size' => $ZoneMinder::Memory::mem_data{shared_data}{size},
    'trigger_data:size' => $ZoneMinder::Memory::mem_data{trigger_data}{size},
    'shared_data:valid' => 1,
    'shared_data:action' => 0x04,
  }, 1);
  @errors = ();
  $call->({Id => 1, Name => 'Cam'});
  check("$name on a detached monitor logs no error", join(' | ', @errors), '');
  check("$name on a detached monitor keeps the pending reload",
    ZoneMinder::Memory::zmMemRead($writer, 'shared_data:action'), 0x04 | $bit);
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
