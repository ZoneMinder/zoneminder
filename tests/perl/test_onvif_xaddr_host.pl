#!/usr/bin/perl
#
# interpret_messages must only accept an XAddr whose host is the IPv4 address,
# not one that has the address as its user name (http://1.2.3.4:80@host/).
# Set ONVIF_IN to another ONVIF.pm.in to test it.
#
# Run as: perl tests/perl/test_onvif_xaddr_host.pl
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

BEGIN {
  $INC{'ZoneMinder/Base.pm'} = 1;
  $INC{'ONVIF/Client.pm'} = 1;
  $INC{'WSDiscovery10/Interfaces/WSDiscovery/WSDiscoveryPort.pm'} = 1;
  $INC{'WSDiscovery10/Elements/Header.pm'} = 1;
  $INC{'WSDiscovery10/Elements/Types.pm'} = 1;
  $INC{'WSDiscovery10/Elements/Scopes.pm'} = 1;
  $INC{'WSDiscovery/TransportUDP.pm'} = 1;
  $INC{'XML/LibXML.pm'} = 1;
  $INC{'Data/UUID.pm'} = 1;
}

require($ENV{ONVIF_IN} || "$FindBin::Bin/../../scripts/ZoneMinder/lib/ZoneMinder/ONVIF.pm.in");

no warnings 'once';
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


my $mock_svc = bless {}, 'MockSvc';
*MockSvc::get_soap_version = sub { '1.2' };
*MockProbeResult::get_ProbeMatch = sub { $_[0]->{pm} };
*MockProbeMatch::get_Scopes = sub { '' };
my $xaddrs;
*MockProbeMatch::get_XAddrs = sub { $xaddrs };
my $mock_result = bless { pm => bless({}, 'MockProbeMatch') }, 'MockProbeResult';
{ no warnings "redefine"; *ZoneMinder::ONVIF::deserialize_message = sub { return $mock_result; }; }

sub accepted {
  $xaddrs = shift;
  my %services;
  open(my $null, '>', '/dev/null');
  open(my $saveout, '>&', \*STDOUT);
  open(my $saveerr, '>&', \*STDERR);
  open(STDOUT, '>&', $null);
  open(STDERR, '>&', $null);
  my @r = ZoneMinder::ONVIF::interpret_messages($mock_svc, \%services, 'x');
  open(STDOUT, '>&', $saveout);
  open(STDERR, '>&', $saveerr);
  return @r ? $r[0]->{xaddr} : 'none';
}

check('IPv4 with port', accepted('http://192.168.1.50:80/onvif/device_service'), 'http://192.168.1.50:80/onvif/device_service');
check('IPv4 without port', accepted('http://192.168.1.50/onvif/device_service'), 'http://192.168.1.50/onvif/device_service');
check('https IPv4', accepted('https://192.168.1.50:443/onvif/device_service'), 'https://192.168.1.50:443/onvif/device_service');
check('address used as the user name is refused', accepted('http://1.2.3.4:80@attacker.example/onvif/device_service'), 'none');
check('address used as a user name with no port is refused', accepted('http://1.2.3.4@attacker.example/x'), 'none');
check('host that merely starts with an address is refused', accepted('http://1.2.3.4.attacker.example/x'), 'none');
check('address in the path is refused', accepted('http://attacker.example/x//1.2.3.4/'), 'none');
check('falls through to the next valid XAddr', accepted('http://1.2.3.4:80@attacker.example/x http://10.0.0.5:80/onvif/device_service'), 'http://10.0.0.5:80/onvif/device_service');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
