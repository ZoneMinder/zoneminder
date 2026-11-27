#!/usr/bin/perl
#
# ZoneMinder::Control::TapoC520WS_ONVIF must keep the brackets of an IPv6
# Control Address.
#
# parseControlAddress built BaseURL and the Host header from URI->host, which
# returns an IPv6 literal without its brackets, so a camera at [fe80::1]:2020
# was sent to the unparseable http://fe80::1:2020 and every command failed.
#
# Run as: perl tests/perl/test_tapo_onvif_ipv6.pl
#
use strict;
use warnings;
use FindBin;
use lib (($ENV{ZM_TEST_ROOT} // "$FindBin::Bin/../..") . '/scripts/ZoneMinder/lib');

BEGIN {
  if (!eval { require DateTime; 1 }) {
    $INC{'DateTime.pm'} = 1;
  }
}

use ZoneMinder::Control::TapoC520WS_ONVIF;

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
sub status_line { '200 OK' }
sub content { '' }
sub decoded_content { '' }

package main;

my $request;
{
  no warnings qw(redefine once);
  *LWP::UserAgent::request = sub { $request = $_[1]; return FakeResponse->new(); };
}

sub send_to {
  my ($address) = @_;
  my $c = bless {ua => LWP::UserAgent->new()}, 'ZoneMinder::Control::TapoC520WS_ONVIF';
  $c->parseControlAddress($address);
  $request = undef;
  $c->sendCmd('stop', '<s:Body/>', 'application/soap+xml');
  return ($c, $request);
}

my ($c, $req) = send_to('http://[fe80::1]:2020');
check('IPv6 base url keeps the brackets', $$c{BaseURL}, 'http://[fe80::1]:2020');
check('IPv6 request goes to the bracketed url', $req ? $req->uri->as_string : undef, 'http://[fe80::1]:2020/onvif/PTZ');
check('IPv6 Host header keeps the brackets', $req ? $req->header('Host') : undef, '[fe80::1]:2020');

($c, $req) = send_to('http://192.168.1.10:2020');
check('IPv4 base url is unchanged', $$c{BaseURL}, 'http://192.168.1.10:2020');
check('IPv4 Host header is unchanged', $req ? $req->header('Host') : undef, '192.168.1.10:2020');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
