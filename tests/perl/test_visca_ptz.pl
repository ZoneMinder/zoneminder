#!/usr/bin/perl
#
# ZoneMinder::Control::Visca pan/tilt speed limits test.
#
# VISCA protocol limits pan speed to 0x01-0x18 and tilt speed to 0x01-0x14.
# Visca.pm seeded default speeds at 0x40 and sent values up to MaxPanSpeed (50)
# or TurboPanSpeed (254) without clamping.
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

use ZoneMinder::Control::Visca;

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

package FakePort;
sub new { return bless {written => []}, $_[0]; }
sub write {
  my ($self, $msg) = @_;
  push @{$self->{written}}, $msg;
  return length($msg);
}

package main;

my $port = FakePort->new();
my $control = bless {
  Monitor => {
    Id => 1,
    ControlAddress => 1,
    AutoStopTimeout => 0,
  },
  state => 'open',
  port => $port,
}, 'ZoneMinder::Control::Visca';

sub run_cmd_hex {
  my ($method, $params) = @_;
  $port->{written} = [];
  $control->$method($params);
  return join(' ', map { sprintf('%02x', ord($_)) } split(//, $port->{written}[0] // ''));
}

# 1. Default speed seed tests (previously 0x40)
check('moveConUp defaults tilt speed to 0x14 (clamped from 0x40 seed)',
  run_cmd_hex('moveConUp', {}),
  '81 01 06 01 00 14 03 01 ff');

check('moveConRight defaults pan speed to 0x18 (clamped from 0x40 seed)',
  run_cmd_hex('moveConRight', {}),
  '81 01 06 01 18 00 02 03 ff');

# 2. Speeds above limits (Controls table specifies MaxPanSpeed=50, Turbo=254)
check('moveConUpLeft clamps pan speed to 0x18 and tilt speed to 0x14 when sent 50 (0x32)',
  run_cmd_hex('moveConUpLeft', {panspeed => 50, tiltspeed => 50}),
  '81 01 06 01 18 14 01 01 ff');

check('moveConRight clamps turbo pan speed 254 (0xfe) to 0x18',
  run_cmd_hex('moveConRight', {panspeed => 254}),
  '81 01 06 01 18 00 02 03 ff');

# 3. Speeds below minimum (0x01)
check('moveConDown clamps tilt speed 0 to 0x01',
  run_cmd_hex('moveConDown', {tiltspeed => 0}),
  '81 01 06 01 00 01 03 02 ff');

check('moveConLeft clamps pan speed 0 to 0x01',
  run_cmd_hex('moveConLeft', {panspeed => 0}),
  '81 01 06 01 01 00 01 03 ff');

# 4. Valid speeds preserved
check('moveConRight preserves valid pan speed 10 (0x0a)',
  run_cmd_hex('moveConRight', {panspeed => 10}),
  '81 01 06 01 0a 00 02 03 ff');

check('moveConUp preserves valid tilt speed 10 (0x0a)',
  run_cmd_hex('moveConUp', {tiltspeed => 10}),
  '81 01 06 01 00 0a 03 01 ff');

# 5. Relative moves clamp pan and tilt speed
check('moveRelUpLeft clamps speeds above limits',
  run_cmd_hex('moveRelUpLeft', {panstep => 100, tiltstep => 100, panspeed => 50, tiltspeed => 50}),
  '81 01 06 03 18 14 0f 0f 09 0c 00 00 06 04 ff');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
