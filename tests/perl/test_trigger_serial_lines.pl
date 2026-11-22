#!/usr/bin/perl
#
# Tests that lines read from the serial trigger channel stay separate messages.
# Device::SerialPort::lookfor returns a line without its terminator, so the
# channel has to put it back or two quick lines run together.
#
# Run as: perl tests/perl/test_trigger_serial_lines.pl
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

BEGIN {
  $INC{'ZoneMinder/Base.pm'} = $INC{'ZoneMinder/Logger.pm'} = $INC{'Device/SerialPort.pm'} = 1;
  package ZoneMinder::Base;
  our $VERSION = '1';
  package ZoneMinder::Logger;
  sub import { no strict 'refs'; my $c = caller; *{"${c}::$_"} = sub {} for qw(Debug Info Warning Error Fatal); }
  package Device::SerialPort;
  sub lookfor { my $s = shift; return shift @{$s->{lines}}; }
}

use ZoneMinder::Trigger::Channel::Serial;
use ZoneMinder::Trigger::Connection;

my $channel = ZoneMinder::Trigger::Channel::Serial->new(path => '/dev/null');
$channel->{device} = bless { lines => ['1|on|1|a', '2|cancel'] }, 'Device::SerialPort';
my $conn = ZoneMinder::Trigger::Connection->new(name => 't', mode => 'r', channel => $channel);

my @out;
for (1 .. 3) {
  my $m = $conn->getMessages();
  push @out, @$m if $m;
}
my $got = join(',', @out);
if ($got eq '1|on|1|a,2|cancel') {
  print "ok - serial lines stay separate\n";
  exit 0;
}
print "NOT OK - serial lines: got '$got' want '1|on|1|a,2|cancel'\n";
exit 1;
