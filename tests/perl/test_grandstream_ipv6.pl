#!/usr/bin/perl
#
# ZoneMinder::Control::Grandstream must keep the brackets of an IPv6 Control Address.
#
# open() built the base url from the host that URI hands back, which has no
# brackets for an IPv6 literal, so a camera at [fe80::1] was logged in to at the
# unparseable https://fe80::1/goform/login and the module never opened.
#
# No network: get() is stubbed and records the urls it is asked for.
#
# Run as: perl tests/perl/test_grandstream_ipv6.pl
#
use strict;
use warnings;
use FindBin;
use lib ($ENV{ZM_LIB_DIR} // "$FindBin::Bin/../../scripts/ZoneMinder/lib");

BEGIN {
  # Optional CPAN modules; only the config helpers use them.
  if (!eval { require URI::Encode; 1 }) {
    delete $INC{'URI/Encode.pm'};
    $INC{'URI/Encode.pm'} = __FILE__;
    no strict 'refs';
    *{'URI::Encode::uri_encode'} = sub { return $_[0]; };
  }
  if (!eval { require XML::LibXML; 1 }) {
    delete $INC{'XML/LibXML.pm'};
    $INC{'XML/LibXML.pm'} = __FILE__;
  }
}

use ZoneMinder::Control::Grandstream;

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

package FakeResponse;
sub new { return bless {}, shift; }
sub is_success { 0 }
sub status_line { '500 stub' }
sub content { '' }

package main;

my @urls;
{
  no warnings qw(redefine once);
  *ZoneMinder::Control::Grandstream::loadMonitor = sub { };
  *ZoneMinder::Control::Grandstream::get = sub { push @urls, $_[1]; return FakeResponse->new(); };
}

sub login_url {
  my ($address) = @_;
  @urls = ();
  $ZoneMinder::Control::Grandstream::PROTOCOL = 'https://';
  my $c = bless {Monitor => {Id => 1, Name => 'cam', ControlAddress => $address}},
    'ZoneMinder::Control::Grandstream';
  $c->open();
  return $urls[0];
}

check('IPv6 address keeps its brackets',
  login_url('admin:pw@[fe80::1]'), 'https://[fe80::1]/goform/login?cmd=login&type=0&user=admin');
check('IPv4 address is unchanged',
  login_url('admin:pw@192.168.1.10'), 'https://192.168.1.10/goform/login?cmd=login&type=0&user=admin');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
