#!/usr/bin/perl
#
# ZoneMinder::Control::Floureon PTZ control tests.
#
# 1. Controls row advertises CanZoomCon, CanFocusCon, CanIrisCon, but Floureon
#    lacked zoomStop, focusStop, and irisStop methods, causing zmcontrol to
#    reject stop commands from web UI.
# 2. autoStop unconditionally sent Stop=0 even when timeout was 0/undefined,
#    and zoomCon*/focusCon*/irisCon* called $self->autoStop() with no timeout,
#    immediately stopping operations upon start.
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

use ZoneMinder::Control::Floureon;

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
  my ($class, $timeout) = @_;
  return bless {
    Id => 1,
    ControlAddress => '192.0.2.1',
    ControlDevice => 'user=admin&pwd=secret',
    AutoStopTimeout => $timeout // 0,
  }, $class;
}

package main;

sub camera {
  my ($timeout) = @_;
  my $ua = FakeUA->new();
  my $control = bless {
    ua => $ua,
    Monitor => FakeMonitor->new($timeout),
    state => 'open',
  }, 'ZoneMinder::Control::Floureon';
  return ($control, $ua);
}

# 1. Implements stop methods advertised by CanZoomCon, CanFocusCon, CanIrisCon
{
  my ($control, $ua) = camera(0);
  check('Floureon implements zoomStop', $control->can('zoomStop') ? 1 : 0, 1);
  check('Floureon implements focusStop', $control->can('focusStop') ? 1 : 0, 1);
  check('Floureon implements irisStop', $control->can('irisStop') ? 1 : 0, 1);

  if ($control->can('zoomStop')) {
    $control->zoomStop();
    check('zoomStop sends Stop=0',
      $ua->{reqs}[0]->uri.'',
      'http://192.0.2.1/cgi/ptz_set?Channel=1&Group=PTZCtrlInfo&Stop=0&user=admin&pwd=secret');
  } else {
    check('zoomStop sends Stop=0', 0, 1);
  }
}

# 2. zoomConTele does not immediately auto-stop when AutoStopTimeout is 0
{
  my ($control, $ua) = camera(0);
  $control->zoomConTele();
  check('zoomConTele sends only 1 request when AutoStopTimeout is 0',
    scalar(@{$ua->{reqs}}), 1);
  my $uri = @{$ua->{reqs}} ? $ua->{reqs}[0]->uri.'' : '';
  check('zoomConTele request is zoom start',
    $uri,
    'http://192.0.2.1/cgi/ptz_set?Channel=1&Group=PTZCtrlInfo&Zoom=1&user=admin&pwd=secret');
}

# 3. zoomConTele auto-stops when AutoStopTimeout is set
{
  my ($control, $ua) = camera(2);
  $control->zoomConTele();
  check('zoomConTele sends 2 requests when AutoStopTimeout is set',
    scalar(@{$ua->{reqs}}), 2);
  my $stop_uri = @{$ua->{reqs}} > 1 ? $ua->{reqs}[1]->uri.'' : '';
  check('zoomConTele second request is auto-stop',
    $stop_uri,
    'http://192.0.2.1/cgi/ptz_set?Channel=1&Group=PTZCtrlInfo&Stop=0&user=admin&pwd=secret');
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
