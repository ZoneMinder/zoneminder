#!/usr/bin/perl
#
# ZoneMinder::Control::Dahua open() URL for each ControlAddress shape.
#
# The default port follows the scheme (80 for http, 443 for https) and an
# explicit port is kept. The request is captured with a fake user agent, so
# nothing connects to a camera.
#
# Run as: perl tests/perl/test_dahua_default_port.pl
# ZM_CONTROL_LIB may name another scripts/ZoneMinder/lib to test instead.
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";
use lib ($ENV{ZM_CONTROL_LIB} // ());

use ZoneMinder::Control::Dahua;

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
sub new { return bless {}, $_[0]; }
sub is_success { 1 }
sub status_line { '200 OK' }

package FakeUA;
sub new { return bless {reqs => []}, $_[0]; }
sub request {
  my ($self, $req) = @_;
  push @{$self->{reqs}}, $req;
  return FakeResponse->new();
}
sub credentials {}
sub agent {}
sub ssl_opts {}

package main;

sub open_url {
  my ($addr) = @_;
  my $ua = FakeUA->new();
  no warnings 'redefine';
  local *LWP::UserAgent::new = sub { return $ua; };
  $ZoneMinder::Control::Dahua::PROTOCOL = 'http://';
  $ZoneMinder::Control::Dahua::ADDRESS = '';
  $ZoneMinder::Control::Dahua::USERNAME = '';
  $ZoneMinder::Control::Dahua::PASSWORD = '';
  my $c = bless {
    Monitor => { Id => 1, ControlAddress => $addr, Path => '' },
  }, 'ZoneMinder::Control::Dahua';
  $c->open();
  return @{$ua->{reqs}} ? $ua->{reqs}[0]->uri()->as_string() : '';
}

my $cgi = '/cgi-bin/configManager.cgi?action=getConfig&name=Ptz';
my @cases = (
  ['cam',                              "http://cam:80$cgi"],
  ['user:pass@cam',                    "http://cam:80$cgi"],
  ['user:pass@cam:8080',               "http://cam:8080$cgi"],
  ['http://user:pass@cam',             "http://cam:80$cgi"],
  ['http://cam',                       "http://cam:80$cgi"],
  ['https://user:pass@cam',            "https://cam:443$cgi"],
  ['https://cam',                      "https://cam:443$cgi"],
  ['https://user:pass@cam:8443',       "https://cam:8443$cgi"],
  ['http://user:pass@cam:8080',        "http://cam:8080$cgi"],
  ['https://user:p@ss@cam',            "https://cam:443$cgi"],
  ['https://user:pa:ss@cam',           "https://cam:443$cgi"],
  ['user:p@ss@cam',                    "http://cam:80$cgi"],
);
foreach my $case (@cases) {
  check("url for $case->[0]", open_url($case->[0]), $case->[1]);
}

open_url('https://user:p@ss@cam');
check('password containing @ is kept whole', $ZoneMinder::Control::Dahua::PASSWORD, 'p@ss');
open_url('https://user:pa:ss@cam');
check('password containing : is kept whole', $ZoneMinder::Control::Dahua::PASSWORD, 'pa:ss');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
