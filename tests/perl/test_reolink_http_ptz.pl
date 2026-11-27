#!/usr/bin/perl
#
# ZoneMinder::Control::Reolink_HTTP request shapes and session handling.
#
# SetPtzPreset takes its arguments under a PtzPreset key; sent flat the camera
# answers with an error, so presetSet never stored anything.  And when the
# camera drops the session (reboot, session limit) it answers 'please login
# first' (rspCode -6); the module kept using the dead token until its own lease
# clock ran out instead of logging in again.
#
# Run as: perl tests/perl/test_reolink_http_ptz.pl
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

use JSON::MaybeXS qw(decode_json);
use ZoneMinder::Control::Reolink_HTTP;

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
sub new { my ($class, $body) = @_; return bless {body => $body}, $class; }
sub is_success { 1 }
sub is_redirect { 0 }
sub status_line { '200 OK' }
sub decoded_content { $_[0]->{body} }
sub header { undef }

# A camera that rejects the first token it is shown, if told to.
package FakeUA;
sub new { my ($class, $reject) = @_; return bless {reqs => [], reject => $reject}, $class; }
sub request {
  my ($self, $req) = @_;
  push @{$self->{reqs}}, $req;
  my $uri = $req->uri.'';
  if ($uri =~ /cmd=Login/) {
    $self->{logins}++;
    return FakeResponse->new('[{"cmd":"Login","code":0,"value":{"Token":{"leaseTime":3600,"name":"tok'.$self->{logins}.'"}}}]');
  }
  if ($self->{reject} and $uri =~ /token=tok0\b/) {
    return FakeResponse->new('[{"cmd":"PtzCtrl","code":1,"error":{"detail":"please login first","rspCode":-6}}]');
  }
  return FakeResponse->new('[{"cmd":"PtzCtrl","code":0,"value":{"rspCode":200}}]');
}

package FakeMonitor;
sub new { return bless {ModectDuringPTZ => 1, ControlDevice => 0}, $_[0]; }
sub suspendMotionDetection {}
sub resumeMotionDetection {}

package CountingMonitor;
sub new { return bless {ModectDuringPTZ => 0, ControlDevice => 0, calls => $_[1]}, $_[0]; }
sub suspendMotionDetection { $_[0]->{calls}{suspend}++ }
sub resumeMotionDetection { $_[0]->{calls}{resume}++ }

package main;

sub camera {
  my ($reject) = @_;
  my $ua = FakeUA->new($reject);
  my $control = bless {
    ua => $ua, Monitor => FakeMonitor->new,
    host => '192.0.2.1', port => 80, protocol => 'http', channel => 2,
    username => 'admin', password => 'x',
    token => 'tok0', token_expiry => time() + 3000, token_lease_time => 3600,
  }, 'ZoneMinder::Control::Reolink_HTTP';
  return ($control, $ua);
}

# presetSet
{
  my ($control, $ua) = camera(0);
  $control->presetSet({preset => 5});
  my $body = decode_json($ua->{reqs}[0]->content)->[0];
  check('presetSet sends SetPtzPreset', $body->{cmd}, 'SetPtzPreset');
  my $preset = $body->{param}{PtzPreset};
  check('presetSet nests its arguments under PtzPreset', ref($preset), 'HASH');
  check('presetSet preset id', $preset->{id}, 5);
  check('presetSet channel', $preset->{channel}, 2);
  check('presetSet enables the preset', $preset->{enable}, 1);
}

# The Controls row has CanFocusCon, so the focus button sends focusStop.
package FocusMonitor;
sub new { return bless {ModectDuringPTZ => 0, ControlDevice => 0, calls => $_[1]}, $_[0]; }
sub suspendMotionDetection { $_[0]->{calls}{suspend}++ }
sub resumeMotionDetection { $_[0]->{calls}{resume}++ }

package main;
{
  my ($control, $ua) = camera(0);
  my %calls;
  $control->{Monitor} = FocusMonitor->new(\%calls);
  check('focusStop is implemented', $control->can('focusStop') ? 1 : 0, 1);
  $control->focusConNear({speed => 10});
  $control->focusStop() if $control->can('focusStop');
  my $body = @{$ua->{reqs}} > 1 ? decode_json($ua->{reqs}[1]->content)->[0] : {};
  check('focusStop op', $body->{param}{op}, 'Stop');
  check('focusStop channel', $body->{param}{channel}, 2);
  check('focusStop resumes motion detection', $calls{resume}, 1);
}

# A move still goes out flat and with the speed.
{
  my ($control, $ua) = camera(0);
  $control->moveConLeft({panspeed => 12});
  my $body = decode_json($ua->{reqs}[0]->content)->[0];
  check('moveConLeft op', $body->{param}{op}, 'Left');
  check('moveConLeft speed', $body->{param}{speed}, 12);
}

# Zoom stop gives motion detection back after a zoom suspended it.
{
  my ($control, $ua) = camera(0);
  my %calls;
  $control->{Monitor} = CountingMonitor->new(\%calls);
  $control->zoomConTele({speed => 10});
  $control->zoomStop();
  check('zoomConTele suspends motion detection', $calls{suspend}, 1);
  check('zoomStop resumes motion detection', $calls{resume}, 1);
}

# Token dropped by the camera
{
  my ($control, $ua) = camera(1);
  $control->moveConUp({tiltspeed => 7});
  my @urls = map { $_->uri.'' } @{$ua->{reqs}};
  check('rejected token: three requests (move, login, move)', scalar(@urls), 3);
  check('rejected token: second request logs in', ($urls[1] // '') =~ /cmd=Login/ ? 1 : 0, 1);
  check('rejected token: move is repeated with the new token', ($urls[2] // '') =~ /cmd=PtzCtrl&token=tok1$/ ? 1 : 0, 1);
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
