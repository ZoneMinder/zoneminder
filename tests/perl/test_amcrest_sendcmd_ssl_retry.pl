#!/usr/bin/perl
#
# ZoneMinder::Control::Amcrest_HTTP sendCmd on an https camera with a
# self-signed certificate.
#
# When a request fails, sendCmd retries once with a fresh user agent. That agent
# verifies certificates again, but the control object still remembered that
# verification had been turned off, so get() never turned it off for the new
# agent: the retry failed with a certificate error, and so did every later
# command on that agent. The flag is now cleared with the agent.
#
# Run as: perl tests/perl/test_amcrest_sendcmd_ssl_retry.pl
# ZM_CONTROL_LIB may name another scripts/ZoneMinder/lib to test instead.
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";
use lib ($ENV{ZM_CONTROL_LIB} // ());

BEGIN {
  # Optional CPAN modules that are only used by the config helpers.
  if (!eval { require URI::Encode; 1 }) {
    delete $INC{'URI/Encode.pm'};
    $INC{'URI/Encode.pm'} = __FILE__;
    no strict 'refs';
    *{'URI::Encode::uri_encode'} = sub { return $_[0]; };
  }
}

use LWP::UserAgent;
use ZoneMinder::Control::Amcrest_HTTP;

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
sub new { return bless {status => $_[1]}, $_[0]; }
sub is_success { $_[0]->{status} =~ /^2/ ? 1 : 0 }
sub status_line { $_[0]->{status} }
sub content { '' }
sub decoded_content { '' }

# A camera with a self-signed certificate: a fresh agent is refused until it has
# been told not to verify. A 'broken' agent stands for the transient failure.
package FakeUserAgent;
our $created = 0;
sub new {
  my ($class, %args) = @_;
  return bless {verify => 1, broken => $args{broken}}, $class;
}
sub get {
  my ($self, $url) = @_;
  return FakeResponse->new('500 Can\'t connect to 192.0.2.1:443') if $self->{broken};
  return FakeResponse->new('500 SSL certificate verify failed') if $self->{verify};
  return FakeResponse->new('200 OK');
}
sub ssl_opts {
  my ($self, %opts) = @_;
  $self->{verify} = 0 if exists $opts{verify_hostname} and !$opts{verify_hostname};
}
sub credentials {}
sub cookie_jar {}
sub agent {}

package main;

{
  no warnings 'redefine';
  *LWP::UserAgent::new = sub { $FakeUserAgent::created++; return FakeUserAgent->new(); };
}

sub control {
  # open() already fell back to no verification, then the camera hiccupped
  return bless {
    Monitor => {ControlAddress => 'https://192.0.2.1', ModectDuringPTZ => 1},
    BaseURL => 'https://192.0.2.1/',
    address => '192.0.2.1:443',
    ua => FakeUserAgent->new(broken => 1),
    ssl_verify_disabled => 1,
  }, 'ZoneMinder::Control::Amcrest_HTTP';
}

my $c = control();
my $result = $c->sendCmd('cgi-bin/ptz.cgi?action=start&code=Up&channel=0&arg1=0&arg2=1&arg3=0');
check('sendCmd retried with a fresh agent', $FakeUserAgent::created, 1);
check('the retry on the fresh agent succeeds', $result ? 1 : 0, 1);

my $next = $c->sendCmd('cgi-bin/ptz.cgi?action=stop&code=Up&channel=0&arg1=0&arg2=1&arg3=0');
check('the next command works on the same agent', $next ? 1 : 0, 1);
check('no further agent was needed', $FakeUserAgent::created, 1);

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
