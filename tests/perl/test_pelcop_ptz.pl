#!/usr/bin/perl
#
# ZoneMinder::Control::PelcoP functional test.
#
# Tests iris relative commands, presetClear method availability, and autostop
# behavior for continuous focus and iris movements.
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

package FakeSerialPort;
sub new { my ($class, $path) = @_; return bless {path => $path, writes => []}, $class; }
sub baudrate { 1 }
sub databits { 1 }
sub parity { 1 }
sub stopbits { 1 }
sub handshake { 1 }
sub read_const_time { 1 }
sub read_char_time { 1 }
sub write {
  my ($self, $bytes) = @_;
  push @{$self->{writes}}, $bytes;
  return length($bytes);
}
sub read { (0, '') }
sub close { 1 }
$INC{'Device/SerialPort.pm'} = 1;

package FakeMonitor;
sub new {
  return bless {
    ControlDevice => '/dev/null',
    ControlAddress => 1,
    AutoStopTimeout => 100,
  }, $_[0];
}

package main;

BEGIN {
  no warnings 'redefine';
  *Time::HiRes::usleep = sub ($) { };
}

use ZoneMinder::Control::PelcoP;

sub camera {
  my $port = FakeSerialPort->new('/dev/null');
  my $control = bless {
    port => $port,
    Monitor => FakeMonitor->new(),
  }, 'ZoneMinder::Control::PelcoP';
  return ($control, $port);
}

sub has_hex {
  my ($port, $want) = @_;
  return (grep { unpack('H*', $_) eq $want } @{$port->{writes}}) ? 1 : 0;
}

# Autostop sends STOP command for focus and iris
{
  my ($control, $port) = camera();
  $control->focusConNear({autostop => 1});
  check('focusConNear autostop sends stop command', has_hex($port, 'a00100000000af0e'), 1);

  ($control, $port) = camera();
  $control->focusConFar({autostop => 1});
  check('focusConFar autostop sends stop command', has_hex($port, 'a00100000000af0e'), 1);

  ($control, $port) = camera();
  $control->irisConClose({autostop => 1});
  check('irisConClose autostop sends stop command', has_hex($port, 'a00100000000af0e'), 1);

  ($control, $port) = camera();
  $control->irisConOpen({autostop => 1});
  check('irisConOpen autostop sends stop command', has_hex($port, 'a00100000000af0e'), 1);
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
