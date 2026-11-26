#!/usr/bin/perl
#
# ZoneMinder::Control::Uniview must register its retry credentials under the key
# LWP looks them up by.
#
# After a 401 PutCmd() builds a new user agent and registers the credentials
# under "$host:$port". LWP looks them up by the url's host_port, which brackets
# an IPv6 host, so for a camera at [fe80::1] the key fe80::1:80 never matched
# and the retry was refused again.
#
# Run as: perl tests/perl/test_uniview_ipv6.pl
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

use LWP::UserAgent;
use URI;
use ZoneMinder::Control::Uniview;

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
sub new { my ($class, $code) = @_; return bless {code => $code}, $class; }
sub is_success { $_[0]{code} == 200 }
sub code { $_[0]{code} }
sub content { '' }
sub status_line { $_[0]{code} == 200 ? '200 OK' : '401 Unauthorized' }

package FakeUA;
sub new { return bless {code => 401}, shift; }
sub request { return FakeResponse->new($_[0]{code}); }
sub cookie_jar {}

package main;

# The user agent PutCmd builds after the 401 is the real class, so give it a
# real credentials store and ask LWP what it would send for the camera url.
sub retry_credentials {
  my ($address, $url) = @_;
  my $retry;
  {
    no warnings 'redefine';
    local *LWP::UserAgent::request = sub { $retry = $_[0]; return FakeResponse->new(200); };
    my $c = bless {
      ua => FakeUA->new(),
      Monitor => {Id => 1, ControlAddress => "admin:pw\@$address"},
      BaseURL => "http://$address/",
      host => URI->new("http://$address/")->host,
      port => 80,
      address => URI->new("http://$address/")->host_port,
      realm => 'r',
      username => 'admin',
      password => 'pw',
    }, 'ZoneMinder::Control::Uniview';
    $c->PutCmd('/LAPI/V1.0/x', '<a/>');
  }
  return join(',', $retry ? $retry->get_basic_credentials('r', URI->new($url)) : ());
}

check('IPv6 camera finds the credentials after a 401',
  retry_credentials('[fe80::1]', 'http://[fe80::1]/LAPI/V1.0/x'), 'admin,pw');
check('IPv4 camera finds the credentials after a 401',
  retry_credentials('192.168.1.50', 'http://192.168.1.50/LAPI/V1.0/x'), 'admin,pw');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
