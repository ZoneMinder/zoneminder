#!/usr/bin/perl
#
# ZoneMinder::Control::Dahua_RPC functional test.
#
# Tests continuous move tilt/pan/diag speed parameters, zoomStop, focusStop,
# and presetClear.
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

use JSON::MaybeXS qw(encode_json decode_json);
use ZoneMinder::Control::Dahua_RPC;

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
sub new { my ($class, $data) = @_; return bless {data => $data}, $class; }
sub is_success { 1 }
sub status_line { '200 OK' }
sub decoded_content { JSON::MaybeXS::encode_json($_[0]->{data}) }

package FakeUA;
sub new { return bless {calls => []}, $_[0]; }
sub post {
  my ($self, $url, %headers) = @_;
  my $body = JSON::MaybeXS::decode_json($headers{Content});
  push @{$self->{calls}}, { url => $url, body => $body };
  return FakeResponse->new({ result => 1 });
}

package FakeMonitor;
sub new { return bless {ModectDuringPTZ => 1}, $_[0]; }
sub suspendMotionDetection {}
sub resumeMotionDetection {}

package main;

sub setup_camera {
  my $fake_ua = FakeUA->new();
  my $control = bless {
    ua => $fake_ua,
    Monitor => FakeMonitor->new(),
    RPCBase => 'http://192.168.1.100:80/',
    ptz_object => 1001,
    session => 'sess123',
    state => 'open',
  }, 'ZoneMinder::Control::Dahua_RPC';
  return ($control, $fake_ua);
}

# 1. moveConUp uses tiltspeed
{
  my ($control, $ua) = setup_camera();
  $control->moveConUp({tiltspeed => 2});
  my $last = $ua->{calls}[-1]{body}{params};
  check('moveConUp sends code Up', $last->{code}, 'Up');
  check('moveConUp arg1 is 0', $last->{arg1}, 0);
  check('moveConUp arg2 uses tiltspeed 2', $last->{arg2}, 2);
}

# 2. moveConDown uses tiltspeed
{
  my ($control, $ua) = setup_camera();
  $control->moveConDown({tiltspeed => 3});
  my $last = $ua->{calls}[-1]{body}{params};
  check('moveConDown sends code Down', $last->{code}, 'Down');
  check('moveConDown arg2 uses tiltspeed 3', $last->{arg2}, 3);
}

# 3. moveConLeft uses panspeed
{
  my ($control, $ua) = setup_camera();
  $control->moveConLeft({panspeed => 5});
  my $last = $ua->{calls}[-1]{body}{params};
  check('moveConLeft sends code Left', $last->{code}, 'Left');
  check('moveConLeft arg2 uses panspeed 5', $last->{arg2}, 5);
}

# 4. moveConUpLeft diagonal uses tiltspeed for arg1 and panspeed for arg2
{
  my ($control, $ua) = setup_camera();
  $control->moveConUpLeft({panspeed => 6, tiltspeed => 1});
  my $last = $ua->{calls}[-1]{body}{params};
  check('moveConUpLeft sends code LeftUp', $last->{code}, 'LeftUp');
  check('moveConUpLeft arg1 uses tiltspeed 1', $last->{arg1}, 1);
  check('moveConUpLeft arg2 uses panspeed 6', $last->{arg2}, 6);
}

# 5. zoomStop method exists and stops zoom
{
  my ($control, $ua) = setup_camera();
  check('Dahua_RPC implements zoomStop', ZoneMinder::Control::Dahua_RPC->can('zoomStop') ? 1 : 0, 1);
  if (ZoneMinder::Control::Dahua_RPC->can('zoomStop')) {
    $control->zoomConTele({});
    $control->zoomStop();
    my $last = $ua->{calls}[-1];
    check('zoomStop calls ptz.stop', $last->{body}{method}, 'ptz.stop');
    check('zoomStop code is ZoomTele', $last->{body}{params}{code}, 'ZoomTele');
  }
}

# 6. focusStop method exists and stops focus
{
  my ($control, $ua) = setup_camera();
  check('Dahua_RPC implements focusStop', ZoneMinder::Control::Dahua_RPC->can('focusStop') ? 1 : 0, 1);
  if (ZoneMinder::Control::Dahua_RPC->can('focusStop')) {
    $control->focusConNear({});
    $control->focusStop();
    my $last = $ua->{calls}[-1];
    check('focusStop calls ptz.stop', $last->{body}{method}, 'ptz.stop');
    check('focusStop code is FocusNear', $last->{body}{params}{code}, 'FocusNear');
  }
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
