#!/usr/bin/perl
#
# ZoneMinder::Control::HiSilicon_Hi3510_CGI must keep the brackets of an IPv6
# Control Address.
#
# sendCmd built the url from the host that URI hands back, which has no
# brackets for an IPv6 literal, so a camera at [fe80::1]:8080 was sent to the
# unparseable http://fe80::1:8080/cgi-bin/hi3510/... and no command reached it.
#
# Run as: perl tests/perl/test_hi3510_ipv6.pl
#
use strict;
use warnings;
use FindBin;
use lib (($ENV{ZM_TEST_ROOT} // "$FindBin::Bin/../..") . '/scripts/ZoneMinder/lib');

use ZoneMinder::Control::HiSilicon_Hi3510_CGI;

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
sub is_success { 1 }

package main;

my $url;
{
  no warnings qw(redefine once);
  *LWP::UserAgent::request = sub { $url = $_[1]->uri->as_string; return FakeResponse->new(); };
  *ZoneMinder::Control::HiSilicon_Hi3510_CGI::loadMonitor = sub { };
}

sub url_for {
  my ($address) = @_;
  my $c = bless {Monitor => {ControlAddress => $address}}, 'ZoneMinder::Control::HiSilicon_Hi3510_CGI';
  $c->open();
  $url = undef;
  $c->sendCmd('ptzctrl.cgi?-step=0&-act=stop');
  return $url;
}

check('IPv6 address keeps its brackets and port',
  url_for('admin:pw@[fe80::1]:8080'),
  'http://[fe80::1]:8080/cgi-bin/hi3510/ptzctrl.cgi?-step=0&-act=stop&usr=admin&pwd=pw');
check('IPv4 address is unchanged',
  url_for('admin:pw@192.168.1.10:8080'),
  'http://192.168.1.10:8080/cgi-bin/hi3510/ptzctrl.cgi?-step=0&-act=stop&usr=admin&pwd=pw');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
