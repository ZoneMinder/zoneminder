#!/usr/bin/perl
#
# ZoneMinder::Control::PanasonicIP audit tests
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

use ZoneMinder::Control::PanasonicIP;

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
sub new {
  my ($class, %args) = @_;
  return bless {
    code => $args{code} // 200,
    content => $args{content} // '',
    status_line => $args{status_line} // '200 OK',
  }, $class;
}
sub is_success { 1 }
sub code { $_[0]->{code} }
sub content { $_[0]->{content} }
sub status_line { $_[0]->{status_line} }

package FakeUA;
sub new {
  my ($class) = @_;
  return bless { reqs => [] }, $class;
}
sub request {
  my ($self, $req) = @_;
  push @{$self->{reqs}}, $req;
  return FakeResponse->new();
}
sub agent {}

package FakeMonitor;
sub new {
  my ($class) = @_;
  return bless {
    Id => 1,
    Name => 'TestPanasonic',
    ControlAddress => '192.168.1.50',
    Width => 640,
    Height => 480,
  }, $class;
}

package main;

sub panasonic_control {
  my $ua = FakeUA->new();
  my $c = bless {
    ua => $ua,
    Monitor => FakeMonitor->new(),
  }, 'ZoneMinder::Control::PanasonicIP';
  return ($c, $ua);
}

# 1. sendCmd dispatches printMsg as a method on $self
{
  my ($c, $ua) = panasonic_control();
  my $captured_invocant;
  my $captured_cmd;
  my $captured_type;
  no warnings 'redefine';
  local *ZoneMinder::Control::PanasonicIP::printMsg = sub {
    my ($invocant, $msg, $type) = @_;
    $captured_invocant = $invocant;
    $captured_cmd = $msg;
    $captured_type = $type;
  };
  $c->sendCmd("testCmd");
  check('sendCmd dispatches printMsg with self as invocant',
    ref($captured_invocant), 'ZoneMinder::Control::PanasonicIP');
  check('sendCmd passes command as first argument to printMsg',
    $captured_cmd, 'testCmd');
  check('sendCmd passes Tx as type to printMsg',
    $captured_type, 'Tx');
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
