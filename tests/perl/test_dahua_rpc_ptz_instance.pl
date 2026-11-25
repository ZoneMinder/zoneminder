#!/usr/bin/perl
#
# ZoneMinder::Control::Dahua_RPC when the camera refuses ptz.factory.instance.
#
# A refusal is answered with "result": false and an error. ensure_ptz_object
# took any defined result for a handle, so it kept the false, never logged the
# failure and never asked again: every move was sent with object false and
# failed until the session ended. A refusal now leaves no handle, is logged,
# and the next command asks again.
#
# Run as: perl tests/perl/test_dahua_rpc_ptz_instance.pl
# ZM_CONTROL_LIB may name another scripts/ZoneMinder/lib to test instead.
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";
use lib ($ENV{ZM_CONTROL_LIB} // ());

use JSON::MaybeXS qw(encode_json decode_json);
use ZoneMinder::Control::Dahua_RPC;

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
sub new { my ($class, $data) = @_; return bless {data => $data}, $class; }
sub is_success { 1 }
sub status_line { '200 OK' }
sub decoded_content { JSON::MaybeXS::encode_json($_[0]->{data}) }

# Answers ptz.factory.instance from a queue; everything else succeeds.
package FakeUA;
sub new { return bless {calls => [], instance => []}, $_[0]; }
sub post {
  my ($self, $url, %headers) = @_;
  my $body = JSON::MaybeXS::decode_json($headers{Content});
  push @{$self->{calls}}, $body;
  if ($body->{method} eq 'ptz.factory.instance') {
    return FakeResponse->new(shift @{$self->{instance}});
  }
  return FakeResponse->new({ result => 1 });
}

package FakeMonitor;
sub new { return bless {ModectDuringPTZ => 1}, $_[0]; }
sub suspendMotionDetection {}
sub resumeMotionDetection {}

package main;

my $refused = { result => JSON::MaybeXS::false, error => { code => 268632085, message => 'Method not found' } };

my $ua = FakeUA->new();
my $control = bless {
  ua => $ua,
  Monitor => FakeMonitor->new(),
  RPCBase => 'http://192.0.2.1:80/',
  session => 'sess123',
  state => 'open',
}, 'ZoneMinder::Control::Dahua_RPC';

sub methods { return join(',', map { $_->{method} } @{$ua->{calls}}); }

@{$ua->{instance}} = ($refused);
my $obj = $control->ensure_ptz_object();
check('a refused instance gives no handle', defined($obj) ? 'handle' : 'undef', 'undef');
check('and none is kept', defined($control->{ptz_object}) ? 'kept' : 'undef', 'undef');

@{$ua->{calls}} = ();
@{$ua->{instance}} = ($refused);
$control->moveConUp({tiltspeed => 2});
check('a move after a refusal sends nothing but the instance request', methods(), 'ptz.factory.instance');

@{$ua->{calls}} = ();
@{$ua->{instance}} = ({ result => 7 });
$control->moveConUp({tiltspeed => 2});
check('the next move asks for the instance again', methods(), 'ptz.factory.instance,ptz.start');
check('and uses the handle it got', $ua->{calls}[1]{object}, 7);

@{$ua->{calls}} = ();
$control->moveConUp({tiltspeed => 2});
check('the handle is reused afterwards', methods(), 'ptz.start');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
