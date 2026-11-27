#!/usr/bin/perl
#
# ZoneMinder::Control::Netcat must keep the brackets of an IPv6 camera address.
#
# parseControlAddress split the address at its first colon, so [fe80::1]:8899
# became address "[fe80" and port ":1]:8899"; a camera given in a Path got the
# host that URI hands back, without the brackets a URL and a Host header need.
#
# Run as: perl tests/perl/test_netcat_ipv6.pl
#
use strict;
use warnings;
use FindBin;

our $root;
BEGIN { $root = $ENV{ZM_TEST_ROOT} || "$FindBin::Bin/../.."; }
use lib "$root/scripts/ZoneMinder/lib";

use ZoneMinder::Control::Netcat;

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

sub reset_globals {
  $ZoneMinder::Control::Netcat::port = 80;
  $ZoneMinder::Control::Netcat::address = undef;
  $ZoneMinder::Control::Netcat::username = undef;
  $ZoneMinder::Control::Netcat::password = undef;
}

reset_globals();
ZoneMinder::Control::Netcat::parseControlAddress('[fe80::1]:8899');
check('bracketed IPv6 with a port keeps its brackets', $ZoneMinder::Control::Netcat::address, '[fe80::1]');
check('bracketed IPv6 with a port sets the port', $ZoneMinder::Control::Netcat::port, 8899);

reset_globals();
ZoneMinder::Control::Netcat::parseControlAddress('admin:secret@[fe80::1]');
check('bracketed IPv6 without a port keeps its brackets', $ZoneMinder::Control::Netcat::address, '[fe80::1]');
check('bracketed IPv6 without a port keeps port 80', $ZoneMinder::Control::Netcat::port, 80);
check('bracketed IPv6 with credentials sets username', $ZoneMinder::Control::Netcat::username, 'admin');

reset_globals();
ZoneMinder::Control::Netcat::parseControlAddress('192.0.2.1:8899');
check('IPv4 address is unchanged', $ZoneMinder::Control::Netcat::address, '192.0.2.1');
check('IPv4 port is unchanged', $ZoneMinder::Control::Netcat::port, 8899);

# A camera given in the Path: open() asks the camera at address:port.
{
  no warnings qw(redefine once);
  *ZoneMinder::Control::Netcat::loadMonitor = sub { };
  *ZoneMinder::Control::Netcat::detectRealm = sub { };
}
my @asked;
{
  no warnings qw(redefine once);
  *ZoneMinder::Control::Netcat::get = sub { push @asked, $_[1]; return undef; };
}
sub asked_for {
  my ($path) = @_;
  reset_globals();
  @asked = ();
  my $c = bless {Monitor => {ControlAddress => '', ControlDevice => '', Path => $path}}, 'ZoneMinder::Control::Netcat';
  $c->open();
  return $asked[0];
}
check('IPv6 Path asks the bracketed address',
  asked_for('rtsp://admin:pw@[fe80::1]:554/stream'), 'http://[fe80::1]:80/onvif/device_service');
check('IPv4 Path is unchanged',
  asked_for('rtsp://admin:pw@192.0.2.1:554/stream'), 'http://192.0.2.1:80/onvif/device_service');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
