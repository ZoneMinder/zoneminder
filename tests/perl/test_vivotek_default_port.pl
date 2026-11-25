#!/usr/bin/perl
#
# ZoneMinder::Control::Vivotek_ePTZ open() builds its base url from the
# ControlAddress. An address with no port gets the default port of its scheme:
# 80 for http and 443 for https. A port written in the address is kept.
#
# Run as: perl tests/perl/test_vivotek_default_port.pl
# Set ZM_CONTROL_LIB to another copy of scripts/ZoneMinder/lib to test that.
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
    Monitor => { Id => 1, ControlAddress => $addr, Path => '' },
  }, 'ZoneMinder::Control::Vivotek_ePTZ';
  no warnings 'once';
  local *ZoneMinder::Control::Vivotek_ePTZ::loadMonitor = sub {};
  $c->open();
  return @{$ua->{urls}} ? $ua->{urls}[0] : '';
}

my $path = '/cgi-bin/viewer/getparam.cgi';
my @cases = (
  ['user:pass@cam',                  "http://user:pass\@cam:80$path"],
  ['user:pass@cam:8080',             "http://user:pass\@cam:8080$path"],
  ['http://user:pass@cam',           "http://user:pass\@cam:80$path"],
  ['https://user:pass@cam',          "https://user:pass\@cam:443$path"],
  ['https://user:pass@cam:8443',     "https://user:pass\@cam:8443$path"],
  ['cam',                            "http://cam:80$path"],
  ['cam:8080',                       "http://cam:8080$path"],
  ['https://cam',                    "https://cam:443$path"],
  ['user:p@ss@cam',                  "http://user:p\@ss\@cam:80$path"],
  ['https://user:p@ss@cam',          "https://user:p\@ss\@cam:443$path"],
);
for my $case (@cases) {
  check("ControlAddress $case->[0]", vivotek_open($case->[0]), $case->[1]);
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
