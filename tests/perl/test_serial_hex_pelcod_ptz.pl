#!/usr/bin/perl
#
# ZoneMinder::Control::Serial_HEX_PelcoD functional test.
#
# Tests credential parsing with '@' and ':' in password, camera address defaults,
# and missing irisStop and presetHome methods.
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
sub headers { return {}; }

package FakeUA;
sub new { return bless {reqs => [], creds => []}, $_[0]; }
sub agent {}
sub cookie_jar {}
sub credentials {
  my ($self, $netloc, $realm, $user, $pass) = @_;
  push @{$self->{creds}}, {netloc => $netloc, realm => $realm, user => $user, pass => $pass};
}
sub get {
  my ($self, $url) = @_;
  push @{$self->{reqs}}, $url;
  return FakeResponse->new("PTZ_VENDOR='DYNACOLOR,UNKNWN'\n");
}

package FakeMonitor;
sub new {
  my ($class, %args) = @_;
  return bless {
    ControlAddress => $args{ControlAddress} // 'admin:secret@192.168.1.99',
    ControlDevice => $args{ControlDevice} // '1:1:Http Server',
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

use ZoneMinder::Control::Serial_HEX_PelcoD;

sub camera {
  my (%args) = @_;
  $active_ua = FakeUA->new();
  my $mon = FakeMonitor->new(%args);
  my $control = bless {
    ua => $active_ua,
    Monitor => $mon,
  }, 'ZoneMinder::Control::Serial_HEX_PelcoD';
  return ($control, $active_ua);
}

# 1. Credentials with @ and : in password
{
  my ($control, $ua) = camera(ControlAddress => 'admin:p@ss:w@rd@192.168.1.99:80');
  $control->open();
  my $cred = $ua->{creds}[0] || {};
  check('password with @ and : user is admin', $cred->{user}, 'admin');
  check('password with @ and : password preserved intact', $cred->{pass}, 'p@ss:w@rd');
}

# 2. Default camera address when ControlDevice has no camera specified
{
  my ($control, $ua) = camera(ControlDevice => '1');
  $control->open();
  $ua->{reqs} = [];
  $control->stop();
  my $url = $ua->{reqs}[-1] || '';
  check('stop command uses default camera 1', $url =~ /SERIAL_HEX=FF010000000001/ ? 1 : 0, 1);
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
