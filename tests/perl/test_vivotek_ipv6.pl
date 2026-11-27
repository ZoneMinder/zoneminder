#!/usr/bin/perl
#
# ZoneMinder::Control::Vivotek_ePTZ must keep the brackets of an IPv6 camera
# address taken from the monitor Path.
#
# open() used the host that URI hands back, which has no brackets for an IPv6
# literal, and took its colons for a port, so a camera at rtsp://u:p@[fe80::1]/x
# was requested at http://u:p@fe80::1/cgi-bin/viewer/getparam.cgi.
#
# Run as: perl tests/perl/test_vivotek_ipv6.pl
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";
BEGIN { unshift @INC, $ENV{ZM_CONTROL_LIB} if $ENV{ZM_CONTROL_LIB}; }

use ZoneMinder::Control::Vivotek_ePTZ;

my $failures = 0;
my $passes = 0;

sub check {
  my ($name, $got, $want) = @_;
  if ((defined $got ? $got : '(undef)') eq (defined $want ? $want : '(undef)')) {
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

package FakeUA;
sub new { return bless {urls => []}, shift; }
sub get { my ($self, $url) = @_; push @{$self->{urls}}, $url; return FakeResponse->new(); }
sub credentials {}
sub agent {}

package main;

sub vivotek_open {
  my ($addr) = @_;
  my $ua = FakeUA->new();
  no warnings 'redefine';
  local *LWP::UserAgent::new = sub { return $ua; };
  $ZoneMinder::Control::Vivotek_ePTZ::PROTOCOL = 'http://';
  $ZoneMinder::Control::Vivotek_ePTZ::USERNAME = '';
  $ZoneMinder::Control::Vivotek_ePTZ::PASSWORD = '';
  $ZoneMinder::Control::Vivotek_ePTZ::ADDRESS = '';
  my $c = bless {
    Monitor => { Id => 1, ControlAddress => '', Path => $addr },
  }, 'ZoneMinder::Control::Vivotek_ePTZ';
  no warnings 'once';
  local *ZoneMinder::Control::Vivotek_ePTZ::loadMonitor = sub {};
  $c->open();
  return @{$ua->{urls}} ? $ua->{urls}[0] : '';
}

my $path = '/cgi-bin/viewer/getparam.cgi';
my @cases = (
  ['rtsp://user:pass@[fe80::1]/live',      "http://user:pass\@[fe80::1]:80$path"],
  ['rtsp://user:pass@[2001:db8::5]:554/x', "http://user:pass\@[2001:db8::5]:80$path"],
  ['rtsp://user:pass@cam/live',            "http://user:pass\@cam:80$path"],
);
for my $case (@cases) {
  check("Path $case->[0]", vivotek_open($case->[0]), $case->[1]);
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
