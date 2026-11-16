#!/usr/bin/perl
#
# ZoneMinder::Control::3S reset command.
#
# The Controls row advertises CanReset, and the ui's Reset button sends the
# command 'reset', but the module only defines cameraReset, so the button was
# logged as "does not implement 'reset'" and ignored.
#
# Run as: perl tests/perl/test_3s_ptz.pl
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

use ZoneMinder::Control::3S;

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
sub request {
  my ($self, $req) = @_;
  push @{$self->{reqs}}, $req;
  return FakeResponse->new;
}

package FakeMonitor;
sub new { return bless {ControlAddress => '192.0.2.1', ControlDevice => '', AutoStopTimeout => 0}, $_[0]; }

package main;

sub camera {
  my $ua = FakeUA->new;
  my $control = bless {
    ua => $ua, Monitor => FakeMonitor->new, state => 'open',
  }, 'ZoneMinder::Control::3S';
  return ($control, $ua);
}

sub last_url {
  my ($method, $params) = @_;
  my ($control, $ua) = camera();
  $control->$method($params || {});
  return @{$ua->{reqs}} ? $ua->{reqs}[-1]->uri.'' : '';
}

{
  my ($control) = camera();
  check('reset is implemented', $control->can('reset') ? 1 : 0, 1);
}

check('reset restarts the camera', last_url('reset'),
  'http://192.0.2.1//restart.cgi');
check('cameraReset still restarts the camera', last_url('cameraReset'),
  'http://192.0.2.1//restart.cgi');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
