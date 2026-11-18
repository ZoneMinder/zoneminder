#!/usr/bin/perl
#
# ZoneMinder::Control::LoftekSentinel zoom stop command.
#
# The Controls row advertises continuous zoom (CanZoomCon), so the web ui shows
# the zoom panel's centre button and sends 'zoomStop' when it is clicked.  The
# module only defines zoomConTele and zoomConWide, so the only way to stop a
# continuous zoom was ignored, and a zoom started from the ui kept running
# unless an AutoStopTimeout happened to be configured.
#
# The stop codes are the ones the module already uses for each direction:
# 19 stops zooming in (started with 18) and 17 stops zooming out (started
# with 16).
#
# Run as: perl tests/perl/test_lofteksentinel_ptz.pl
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

use ZoneMinder::Control::LoftekSentinel;

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
sub new { return bless {ControlAddress => '192.0.2.1', AutoStopTimeout => 0}, $_[0]; }

package main;

sub camera {
  my $ua = FakeUA->new;
  my $control = bless {
    ua => $ua, Monitor => FakeMonitor->new, state => 'open',
  }, 'ZoneMinder::Control::LoftekSentinel';
  return ($control, $ua);
}

sub commands {
  my ($method) = @_;
  my ($control, $ua) = camera();
  $control->$method();
  return join(',', map { (($_->uri.'') =~ /command=(\d+)/) ? $1 : '?' } @{$ua->{reqs}});
}

{
  my ($control) = camera();
  for my $name (qw(zoomConTele zoomConWide zoomStop)) {
    check("$name is implemented", $control->can($name) ? 1 : 0, 1);
  }
}

check('zoomConTele still zooms in', commands('zoomConTele'), '18');
check('zoomConWide still zooms out', commands('zoomConWide'), '16');
check('zoomStop stops zooming in and out', commands('zoomStop'), '19,17');
check('moveStop still stops pan and tilt', commands('moveStop'), '1');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
