#!/usr/bin/perl
#
# A serial channel whose device could not be opened is polled by zmtrigger on
# every pass.  read() must report "nothing to read" instead of calling lookfor
# on a missing device, which dies and takes the whole trigger daemon down.
#
# Run as: perl tests/perl/test_zmtrigger_serial_closed.pl
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";
BEGIN {
  require "$FindBin::Bin/zmtrigger_stubs.pm"; ZmtStubs::install();
  $INC{'Device/SerialPort.pm'} = 1;
  *Device::SerialPort::new = sub { return undef; };   # the port can not be opened
}
use ZoneMinder::Trigger::Channel::Serial;
use ZoneMinder::Trigger::Connection;

my ($passes, $failures) = (0, 0);
sub check {
  my ($name, $got, $want) = @_;
  if ($got eq $want) { $passes++; print "ok - $name\n"; }
  else { $failures++; print "FAIL - $name\n  got:  $got\n  want: $want\n"; }
}

my $conn = ZoneMinder::Trigger::Connection->new(name => 'serial', mode => 'rw',
  channel => ZoneMinder::Trigger::Channel::Serial->new(path => '/dev/nonexistent'));
$conn->open();
check('channel stays closed', $conn->isConnected() ? 1 : 0, 0);

my $messages = eval { $conn->getMessages() };
check('polling a closed serial channel does not die', $@ ? "died: $@" : 'ok', 'ok');
check('and has no messages', defined($messages) ? 'defined' : 'undef', 'undef');
check('closing it does not die', eval { $conn->close(); 1 } ? 'ok' : "died: $@", 'ok');

print "$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
