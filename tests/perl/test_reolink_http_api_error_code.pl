#!/usr/bin/perl
#
# ZoneMinder::Control::Reolink_HTTP sendApiCmd return value.
#
# sendApiCmd used to hand back the response body even when the first element of
# the JSON reply carried a non-zero `code`, so every caller treated a rejected
# command as a success.  It must return false for an error reply and the body
# only for a successful one.
#
# Run as: perl tests/perl/test_reolink_http_api_error_code.pl
# Point at another tree (e.g. an unfixed checkout) with ZM_TEST_ROOT=/path.
#
use strict;
use warnings;
use FindBin;

BEGIN {
  my $root = $ENV{ZM_TEST_ROOT} || "$FindBin::Bin/../..";
  unshift @INC, "$root/scripts/ZoneMinder/lib";
}

use ZoneMinder::Control::Reolink_HTTP;

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
sub new { my ($class, $body) = @_; return bless {body => $body}, $class; }
sub is_success { 1 }
sub is_redirect { 0 }
sub status_line { '200 OK' }
sub decoded_content { $_[0]->{body} }
sub header { undef }

# Answers every request with the canned body and records it.  A pre-seeded
# token means ensureToken does not log in, so no Login reply is needed.
package FakeUA;
sub new { my ($class, $body) = @_; return bless {reqs => [], body => $body}, $class; }
sub request {
  my ($self, $req) = @_;
  push @{$self->{reqs}}, $req;
  return FakeResponse->new($self->{body});
}

package FakeMonitor;
sub new { return bless {ModectDuringPTZ => 0, ControlDevice => 0}, $_[0]; }
sub suspendMotionDetection {}
sub resumeMotionDetection {}

package main;

sub camera {
  my ($body) = @_;
  my $ua = FakeUA->new($body);
  my $control = bless {
    ua => $ua, Monitor => FakeMonitor->new,
    host => '192.0.2.1', port => 80, protocol => 'http', channel => 0,
    username => 'admin', password => 'x',
    token => 'tok0', token_expiry => time() + 3000, token_lease_time => 3600,
  }, 'ZoneMinder::Control::Reolink_HTTP';
  return ($control, $ua);
}

my $ptz = {cmd => 'PtzCtrl', action => 0, param => {channel => 0, op => 'Stop'}};

# An error reply must not look like success.
{
  my $error_body = '[{"cmd":"PtzCtrl","code":1,"error":{"detail":"x","rspCode":-4}}]';
  my ($control, $ua) = camera($error_body);
  my $result = $control->sendApiCmd($ptz);
  check('sendApiCmd is false on a non-zero code', $result ? 1 : 0, 0);
  check('sendApiCmd is undef on a non-zero code', defined($result) ? 1 : 0, 0);
  check('non-zero code sends one request', scalar(@{$ua->{reqs}}), 1);
  my $uri = $ua->{reqs}[0]->uri.'';
  check('non-zero code does not log in', ($uri =~ /cmd=Login/) ? 1 : 0, 0);
}

# A successful reply still returns the body.
{
  my $ok_body = '[{"cmd":"PtzCtrl","code":0,"value":{"rspCode":200}}]';
  my ($control, $ua) = camera($ok_body);
  my $result = $control->sendApiCmd($ptz);
  check('sendApiCmd returns the body on code 0', defined($result) ? $result : '(undef)', $ok_body);
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
