#!/usr/bin/perl
#
# ZoneMinder::Control::FoscamHD must keep the brackets of an IPv6 Control Address.
#
# _base_url() built the camera url from the host that URI hands back, which has
# no brackets for an IPv6 literal, so a camera at [fe80::1]:88 was sent to the
# unparseable http://fe80::1:88/ and every command failed.
#
# Run as: perl tests/perl/test_foscamhd_ipv6.pl
#
use strict;
use warnings;
use FindBin;
use lib ($ENV{ZM_LIB_DIR} // "$FindBin::Bin/../../scripts/ZoneMinder/lib");

use ZoneMinder::Control::FoscamHD;

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

sub base_url {
  my ($address) = @_;
  my $c = bless {Monitor => {ControlAddress => $address}}, 'ZoneMinder::Control::FoscamHD';
  return $c->_base_url();
}

check('IPv6 address keeps its brackets and port',
  base_url('admin:pw@[fe80::1]:88'), 'http://[fe80::1]:88');
check('IPv6 address without a port uses the default',
  base_url('admin:pw@[fe80::1]'), 'http://[fe80::1]');
check('IPv4 address is unchanged',
  base_url('admin:pw@192.168.1.10:88'), 'http://192.168.1.10:88');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
