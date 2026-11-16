#!/usr/bin/perl
#
# ZoneMinder::Control::Ncs370 functional test.
#
# Tests presetGoto method implementation and printMsg method dispatch.
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

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
sub status_line { '200 OK' }
sub content { $_[0]->{body} }

package FakeUA;
sub new { return bless {reqs => []}, $_[0]; }
sub agent {}
sub request {
  my ($self, $req) = @_;
  push @{$self->{reqs}}, $req;
  return FakeResponse->new('OK');
}

package FakeMonitor;
sub new {
  my ($class) = @_;
  return bless {
    ControlAddress => '192.168.1.50',
    AutoStopTimeout => 0,
  }, $class;
}

package main;

our $active_ua = FakeUA->new();
BEGIN {
  no warnings 'redefine';
  require LWP::UserAgent;
  *LWP::UserAgent::new = sub { return $active_ua; };
}

use ZoneMinder::Control::Ncs370;

sub camera {
  $active_ua = FakeUA->new();
  my $control = bless {
    ua => $active_ua,
    Monitor => FakeMonitor->new(),
  }, 'ZoneMinder::Control::Ncs370';
  return ($control, $active_ua);
}

# 1. Advertised presetGoto method
{
  check('Ncs370 implements presetGoto', ZoneMinder::Control::Ncs370->can('presetGoto') ? 1 : 0, 1);
  if (ZoneMinder::Control::Ncs370->can('presetGoto')) {
    my ($control, $ua) = camera();
    $control->presetGoto({preset => 3});
    my $req = $ua->{reqs}[-1];
    check('presetGoto POSTs to PANTILTCONTROL.CGI', $req ? $req->uri . '' : '', 'http://192.168.1.50/PANTILTCONTROL.CGI');
    check('presetGoto sends PanTiltPresetPositionMove', $req ? $req->content : '', 'PanTiltPresetPositionMove=3');
  }
}

# 2. printMsg method dispatch
{
  my ($control, $ua) = camera();
  my $logged_msg;
  no warnings 'redefine';
  local *ZoneMinder::Control::Ncs370::printMsg = sub {
    my ($self, $msg, $prefix) = @_;
    $logged_msg = $msg;
  };
  $control->moveConUp();
  check('sendCmd calls printMsg with command content', $logged_msg, 'PanSingleMoveDegree=1&TiltSingleMoveDegree=1&PanTiltSingleMove=1');
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
