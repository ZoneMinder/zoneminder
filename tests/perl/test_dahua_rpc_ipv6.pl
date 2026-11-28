#!/usr/bin/perl
#
# ZoneMinder::Control::Dahua_RPC must keep the brackets of an IPv6 Control Address.
#
# open() built the RPC url from the host that URI hands back, which has no
# brackets for an IPv6 literal, so a camera at [fe80::1]:8080 was sent to the
# unparseable http://fe80::1:8080/ and the login failed.
#
# Run as: perl tests/perl/test_dahua_rpc_ipv6.pl
#
use strict;
use warnings;
use FindBin;
use lib (($ENV{ZM_TEST_ROOT} // "$FindBin::Bin/../..") . '/scripts/ZoneMinder/lib');

use ZoneMinder::Control::Dahua_RPC;

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
  *ZoneMinder::Control::Dahua_RPC::loadMonitor = sub { };
  *ZoneMinder::Control::Dahua_RPC::login = sub { 1 };
}

sub rpc_base {
  my ($address) = @_;
  my $c = bless {Monitor => {ControlAddress => $address}}, 'ZoneMinder::Control::Dahua_RPC';
  $c->open();
  return $c->{RPCBase};
}

check('IPv6 address keeps its brackets and port',
  rpc_base('admin:pw@[fe80::1]:8080'), 'http://[fe80::1]:8080/');
check('IPv6 address without a port uses 80',
  rpc_base('admin:pw@[fe80::1]'), 'http://[fe80::1]:80/');
check('IPv4 address is unchanged',
  rpc_base('admin:pw@192.168.1.10:8080'), 'http://192.168.1.10:8080/');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
