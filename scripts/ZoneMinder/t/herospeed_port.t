use strict;
use warnings;
use Test::More tests => 7;

# The vendor api must be reached on the port the operator put in the control
# address, not always on port 80.

require_ok('ZoneMinder::Control::Herospeed');

package FakeRes;
sub new { return bless {}, shift }
sub is_success { 1 }
sub decoded_content { '{"code":0}' }
package FakeUA;
sub new { return bless { urls => [] }, shift }
sub credentials {}
sub post { my ($self, $url) = @_; push @{$self->{urls}}, $url; return FakeRes->new }
package main;

sub url_for {
  my (%monitor) = @_;
  my $ua = FakeUA->new;
  my $self = bless { Monitor => {%monitor}, ua => $ua }, 'ZoneMinder::Control::Herospeed';
  $self->guess_credentials();
  $self->api('/session/login-capabilities', 'get', undef);
  return $ua->{urls}[0];
}

is(url_for(ControlAddress => 'admin:pw@192.0.2.1:8080'),
  'http://192.0.2.1:8080/api/session/login-capabilities',
  'a port in the control address is used');
like(url_for(ControlAddress => 'admin:pw@192.0.2.1'),
  qr{^http://192\.0\.2\.1(?::80)?/api/session/login-capabilities$},
  'no port means port 80');
like(url_for(ControlAddress => 'admin:pw@192.0.2.1:80'),
  qr{^http://192\.0\.2\.1(?::80)?/api/session/login-capabilities$},
  'an explicit port 80 stays 80');
is(url_for(ControlAddress => 'admin:pw@[2001:db8::1]:8080'),
  'http://[2001:db8::1]:8080/api/session/login-capabilities',
  'an IPv6 address is bracketed with its port');
like(url_for(ControlAddress => '', Path => 'rtsp://admin:pw@192.0.2.1:554/stream'),
  qr{^http://192\.0\.2\.1(?::80)?/api/session/login-capabilities$},
  'the stream port of the Path is not used for the api');
is(url_for(ControlAddress => '', Path => 'http://admin:pw@192.0.2.1:8080/stream'),
  'http://192.0.2.1:8080/api/session/login-capabilities',
  'an http Path keeps its port');
