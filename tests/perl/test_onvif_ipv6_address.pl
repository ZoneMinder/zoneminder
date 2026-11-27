#!/usr/bin/perl
#
# ZoneMinder::Control::ONVIF with an IPv6 camera.
#
# URI returns the host of http://[fe80::1]:8080 without its brackets, and
# ONVIF.pm joined it to the port as it was, so the request went to
# http://fe80::1:8080/ and the Host header was fe80::1:8080.
#
# Run as: perl tests/perl/test_onvif_ipv6_address.pl
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

BEGIN {
  if (!eval { require DateTime; 1 }) {
    $INC{'DateTime.pm'} = 1;
  }
}

use ZoneMinder::Control::ONVIF;

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
sub new { return bless {}, $_[0]; }
sub is_success { 1 }
sub status_line { '200 OK' }

package FakeUA;
sub new { return bless {reqs => []}, $_[0]; }
sub agent {}
sub ssl_opts {}
sub credentials {}
sub request { push @{$_[0]->{reqs}}, $_[1]; return FakeResponse->new(); }

package main;

# open() sends one GetSystemDateAndTime to the device service
sub opened {
  my ($address, $path) = @_;
  my $control = bless {
    Monitor => {ControlAddress => $address, Path => $path, ControlDevice => 'prof0'},
  }, 'ZoneMinder::Control::ONVIF';
  my $ua = FakeUA->new();
  {
    no warnings 'redefine';
    local *LWP::UserAgent::new = sub { return $ua; };
    $control->open();
  }
  return ($control, $ua->{reqs}[0]);
}

my ($c, $req) = opened('[fe80::1]:8080');
check('IPv6 ControlAddress BaseURL', $$c{BaseURL}, 'http://[fe80::1]:8080');
check('IPv6 ControlAddress request URL', $req->uri->as_string, 'http://[fe80::1]:8080/onvif/device_service');
check('IPv6 ControlAddress Host header', $req->header('Host'), '[fe80::1]:8080');

($c, $req) = opened('admin:secret@[2001:db8::5]/onvif/PTZ');
check('IPv6 ControlAddress default port request URL', $req->uri->as_string, 'http://[2001:db8::5]:80/onvif/device_service');
check('IPv6 ControlAddress keeps the service path', $$c{onvif_path}, '/onvif/PTZ');

($c, $req) = opened('', 'rtsp://admin:pw@[fe80::1]/stream');
check('IPv6 Path BaseURL', $$c{BaseURL}, 'http://[fe80::1]:80');
check('IPv6 Path request URL', $req->uri->as_string, 'http://[fe80::1]:80/onvif/device_service');

($c, $req) = opened('admin:secret@192.168.1.10:8080/onvif/PTZ');
check('IPv4 ControlAddress BaseURL', $$c{BaseURL}, 'http://192.168.1.10:8080');
check('IPv4 ControlAddress Host header', $req->header('Host'), '192.168.1.10:8080');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
