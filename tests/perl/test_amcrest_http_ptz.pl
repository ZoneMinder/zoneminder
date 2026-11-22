#!/usr/bin/perl
#
# ZoneMinder::Control::Amcrest_HTTP continuous moves.
#
# The Controls row advertises pan and tilt speed (1..4) and the web ui scales
# the pointer position into it, but every move was sent with a fixed speed of 1.
# And sendCmd, which retries a failed request once with a fresh user agent,
# reported failure even when that retry succeeded.
#
# Run as: perl tests/perl/test_amcrest_http_ptz.pl
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

BEGIN {
  # Optional CPAN modules that are only used by the config helpers.
  if (!eval { require URI::Encode; 1 }) {
    # Not installed here: define a pass-through at run time, so the stub
    # cannot collide with the real module where it is installed.
    delete $INC{'URI/Encode.pm'};
    $INC{'URI/Encode.pm'} = __FILE__;
    no strict 'refs';
    *{'URI::Encode::uri_encode'} = sub { return $_[0]; };
  }
}

use ZoneMinder::Control::Amcrest_HTTP;

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
sub new { return bless {ok => $_[1]}, $_[0]; }
sub is_success { $_[0]->{ok} }
sub status_line { $_[0]->{ok} ? '200 OK' : '500 fail' }

package FakeMonitor;
sub new { return bless {ModectDuringPTZ => 1, ControlAddress => 'x'}, $_[0]; }
sub suspendMotionDetection {}
sub resumeMotionDetection {}

package CountingMonitor;
sub new { return bless {ModectDuringPTZ => 0, ControlAddress => 'x', calls => $_[1]}, $_[0]; }
sub suspendMotionDetection { $_[0]->{calls}{suspend}++ }
sub resumeMotionDetection { $_[0]->{calls}{resume}++ }

package main;

# Replies per get(), in order; the last one repeats.
my @urls;
my @replies;
{
  no warnings 'redefine';
  *ZoneMinder::Control::Amcrest_HTTP::get = sub {
    push @urls, $_[1];
    my $ok = @replies > 1 ? shift(@replies) : $replies[0];
    return FakeResponse->new($ok);
  };
}

sub control { return bless {Monitor => FakeMonitor->new, BaseURL => 'http://192.0.2.1'}, 'ZoneMinder::Control::Amcrest_HTTP'; }

sub last_url {
  my ($method, $params) = @_;
  @urls = ();
  @replies = (1);
  control()->$method($params);
  return $urls[-1];
}

check('moveConUp uses the tilt speed',
  last_url('moveConUp', {tiltspeed => 3}), 'cgi-bin/ptz.cgi?action=start&code=Up&channel=0&arg1=0&arg2=3&arg3=0');
check('moveConDown uses the tilt speed',
  last_url('moveConDown', {tiltspeed => 2}), 'cgi-bin/ptz.cgi?action=start&code=Down&channel=0&arg1=0&arg2=2&arg3=0');
check('moveConLeft uses the pan speed',
  last_url('moveConLeft', {panspeed => 4}), 'cgi-bin/ptz.cgi?action=start&code=Left&channel=0&arg1=0&arg2=4&arg3=0');
check('moveConRight uses the pan speed',
  last_url('moveConRight', {panspeed => 4, tiltspeed => 1}), 'cgi-bin/ptz.cgi?action=start&code=Right&channel=0&arg1=0&arg2=4&arg3=0');
check('moveConUp without a speed is the slowest',
  last_url('moveConUp', {}), 'cgi-bin/ptz.cgi?action=start&code=Up&channel=0&arg1=0&arg2=1&arg3=0');
check('moveConLeft ignores a non-numeric speed',
  last_url('moveConLeft', {panspeed => '3&code=Down'}), 'cgi-bin/ptz.cgi?action=start&code=Left&channel=0&arg1=0&arg2=1&arg3=0');
check('moveConUpLeft: arg1 is the vertical, arg2 the horizontal speed',
  last_url('moveConUpLeft', {panspeed => 4, tiltspeed => 2}), 'cgi-bin/ptz.cgi?action=start&code=LeftUp&channel=0&arg1=2&arg2=4&arg3=0');

# moveStop repeats the move's own arguments with action=stop.
{
  @urls = ();
  @replies = (1);
  my $c = control();
  $c->moveConRight({panspeed => 3});
  $c->moveStop();
  check('moveStop stops the code that was started',
    $urls[-1], 'cgi-bin/ptz.cgi?action=stop&code=Right&channel=0&arg1=0&arg2=3&arg3=0');
}

# The zoom and focus stops give motion detection back after the move
# suspended it; they clear LastCmd, so a later moveStop cannot.
foreach my $pair (['zoomConTele', 'zoomStop'], ['focusConNear', 'focusStop']) {
  my ($start, $stop) = @$pair;
  @urls = ();
  @replies = (1);
  my %calls;
  my $c = control();
  $c->{Monitor} = CountingMonitor->new(\%calls);
  $c->$start({});
  $c->$stop();
  $c->moveStop();
  check("$start suspends motion detection", $calls{suspend}, 1);
  check("$stop resumes motion detection", $calls{resume}, 1);
}

# sendCmd result when the first try fails and the retry works
{
  @urls = ();
  @replies = (0, 1);
  my $c = control();
  $c->{address} = '192.0.2.1';
  my $result = $c->sendCmd('cgi-bin/x.cgi');
  check('sendCmd tried twice', scalar(@urls), 2);
  check('sendCmd reports success when the retry worked', $result ? 1 : 0, 1);
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
