#!/usr/bin/perl
#
# ZoneMinder::Control::AMLink must keep the brackets of an IPv6 Control Address.
#
# open() built the RPC url from the host that URI hands back, which has no
# brackets for an IPv6 literal, so a camera at [fe80::1]:8080 was sent to the
# unparseable http://fe80::1:8080/Onvif/device_service and the login failed.
#
# Run as: perl tests/perl/test_amlink_ipv6.pl
#
use strict;
use warnings;
use FindBin;
use lib (($ENV{ZM_TEST_ROOT} // "$FindBin::Bin/../..") . '/scripts/ZoneMinder/lib');

BEGIN {
  # AMLink only needs these for the login handshake, which this test replaces.
  foreach my $module ('Crypt::PK::RSA', 'Crypt::Mode::CBC') {
    next if eval "require $module; 1";
    (my $file = "$module.pm") =~ s{::}{/}g;
    $INC{$file} = 1;
  }
}

use ZoneMinder::Control::AMLink;

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

{
  no warnings qw(redefine once);
  *ZoneMinder::Control::AMLink::loadMonitor = sub { };
  *ZoneMinder::Control::AMLink::login = sub { 1 };
}

sub rpc_base {
  my ($address) = @_;
  my $c = bless {Monitor => {ControlAddress => $address}}, 'ZoneMinder::Control::AMLink';
  $c->open();
  return $c->{RPCBase};
}

check('IPv6 address keeps its brackets and port',
  rpc_base('admin:pw@[fe80::1]:8080'), 'http://[fe80::1]:8080/Onvif/device_service');
check('IPv6 address without a port uses 80',
  rpc_base('admin:pw@[fe80::1]'), 'http://[fe80::1]:80/Onvif/device_service');
check('IPv4 address is unchanged',
  rpc_base('admin:pw@192.168.1.10:8080'), 'http://192.168.1.10:8080/Onvif/device_service');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
