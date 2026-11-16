#!/usr/bin/perl
#
# ZoneMinder::Control::IPCC7210W PTZ control tests.
#
# Controls row advertises CanReset = 1, but IPCC7210W only defined cameraReset,
# causing zmcontrol to reject the standard 'reset' command.
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

use ZoneMinder::Control::IPCC7210W;

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

package FakeResponse;
sub new { return bless {ok => 1}, $_[0]; }
sub is_success { 1 }
sub status_line { '200 OK' }

package FakeUA;
sub new { return bless {reqs => []}, $_[0]; }
sub request {
  my ($self, $req) = @_;
  push @{$self->{reqs}}, $req;
  return FakeResponse->new();
}
sub agent {}

package FakeMonitor;
sub new {
  return bless {
    Id => 1,
    ControlAddress => '192.0.2.1',
    ControlDevice => '',
    AutoStopTimeout => 0,
  }, $_[0];
}

package main;

sub camera {
  my $ua = FakeUA->new();
  my $control = bless {
    ua => $ua,
    Monitor => FakeMonitor->new(),
    state => 'open',
  }, 'ZoneMinder::Control::IPCC7210W';
  return ($control, $ua);
}

# 1. IPCC7210W implements reset method
{
  my ($control, $ua) = camera();
  check('IPCC7210W implements reset method', $control->can('reset') ? 1 : 0, 1);
  if ($control->can('reset')) {
    $control->reset();
    my $uri = $ua->{reqs}[0]->uri.'';
    check('reset sends reboot.cgi command', $uri, 'http://192.0.2.1/reboot.cgi?');
  } else {
    check('reset sends reboot.cgi command', 0, 1);
  }
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
