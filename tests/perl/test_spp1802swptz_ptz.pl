#!/usr/bin/perl
#
# ZoneMinder::Control::SPP1802SWPTZ PTZ control tests.
#
# moveConDown was missing autoStop($self->{Monitor}->{AutoStopTimeout}),
#    leaving continuous down moving indefinitely when timeout is configured.
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

use ZoneMinder::Control::SPP1802SWPTZ;

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
    ControlDevice => 'user=admin&pwd=secret',
    AutoStopTimeout => 100,
  }, $_[0];
}

package main;

sub camera {
  my $ua = FakeUA->new();
  my $control = bless {
    ua => $ua,
    Monitor => FakeMonitor->new(),
    state => 'open',
  }, 'ZoneMinder::Control::SPP1802SWPTZ';
  return ($control, $ua);
}

# moveConDown executes autoStop when AutoStopTimeout is configured
{
  my ($control, $ua) = camera();
  $control->moveConDown();
  check('moveConDown triggers 2 requests (down + stop) when AutoStopTimeout is set',
    scalar(@{$ua->{reqs}}), 2);
  my $stop_uri = @{$ua->{reqs}} > 1 ? $ua->{reqs}[1]->uri.'' : '';
  check('moveConDown sends stop command',
    $stop_uri,
    'http://192.0.2.1/cgi-bin/hi3510/ptzctrl.cgi?&-chn=0&-act=stop&user=admin&pwd=secret');
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
