#!/usr/bin/perl
#
# ZoneMinder::Control::PelcoD open() must report a serial port that cannot be
# opened instead of dying.
#
# Device::SerialPort->new returns undef for a missing or busy device, and
# open() went on to call baudrate() on it, which dies and takes the zmcontrol
# server down. close() had the same call.
#
# Run as: perl tests/perl/test_pelcod_open_failure.pl
#
use strict;
use warnings;
use FindBin;
use lib (($ENV{ZM_TEST_ROOT} // "$FindBin::Bin/../..") . '/scripts/ZoneMinder/lib');

BEGIN {
  $INC{'Device/SerialPort.pm'} = 1;
  no warnings 'once';
  *Device::SerialPort::new = sub { return undef; };   # the port can not be opened
}

use ZoneMinder::Control::PelcoD;

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

my $control = bless {
  Monitor => {
    Id => 1,
    ControlDevice => '/dev/zm-test-missing',
    ControlAddress => 1,
    AutoStopTimeout => 0,
  },
}, 'ZoneMinder::Control::PelcoD';

my $result = 'not run';
my $err = '';
{
  local $SIG{__WARN__} = sub { };
  $result = eval { $control->open() } // 'undef';
  $err = $@;
}
check('open does not die', $err ? 'died' : 'ok', 'ok');
check('open returns false', $result eq 'undef' ? 'false' : ($result ? 'true' : 'false'), 'false');
check('open leaves the control closed', $control->{state} // 'unset', 'unset');

$err = '';
eval { $control->close(); 1 } or $err = $@;
check('close does not die without a port', $err ? 'died' : 'ok', 'ok');
check('close marks the control closed', $control->{state}, 'closed');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
