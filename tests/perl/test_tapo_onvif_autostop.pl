#!/usr/bin/perl
#
# ZoneMinder::Control::TapoC520WS_ONVIF auto stop and zoom stop.
#
# Control::loadMonitor turns the monitor's Auto Stop Timeout into microseconds.
# autoStop multiplied that by itself, so a 1 second timeout slept for
# 1000 seconds before the stop was sent.  zoomStop slept for that time, sent
# nothing and then logged 'Zoom Stop not implemented'.
#
# Run as: perl tests/perl/test_tapo_onvif_autostop.pl
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

BEGIN {
  # Only used to timestamp the WS-Security header, which sendCmd is stubbed out of here.
  if (!eval { require DateTime; 1 }) {
    $INC{'DateTime.pm'} = 1;
  }
}

use ZoneMinder::Control::TapoC520WS_ONVIF;

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

my @sleeps;
my @bodies;
{
  no warnings 'redefine';
  *ZoneMinder::Control::TapoC520WS_ONVIF::usleep = sub ($) { push @sleeps, $_[0]; };
  *ZoneMinder::Control::TapoC520WS_ONVIF::sendCmd = sub { push @bodies, $_[2]; return 1; };
}

# loadMonitor is skipped because Monitor is already set, so give it what
# loadMonitor would have: the DB value of 1.5 seconds converted to microseconds.
sub control { return bless {Monitor => {AutoStopTimeout => int(1000000 * 1.5)}}, 'ZoneMinder::Control::TapoC520WS_ONVIF'; }

@sleeps = (); @bodies = ();
control()->moveConLeft();
check('moveConLeft sleeps the auto stop timeout, in microseconds', $sleeps[0], 1500000);
check('moveConLeft then sends a pan/tilt stop', ($bodies[1] // '') =~ m{<Stop .*<PanTilt>true</PanTilt>\s*<Zoom>false</Zoom>}s ? 1 : 0, 1);

@sleeps = (); @bodies = ();
control()->zoomConTele();
check('zoomConTele sleeps the auto stop timeout, in microseconds', $sleeps[0], 1500000);

@sleeps = (); @bodies = ();
control()->zoomStop();
check('zoomStop does not wait', scalar(@sleeps), 0);
check('zoomStop sends one zoom stop', scalar(@bodies), 1);
check('zoomStop stops the zoom, not the pan/tilt', ($bodies[0] // '') =~ m{<Stop .*<PanTilt>false</PanTilt>\s*<Zoom>true</Zoom>}s ? 1 : 0, 1);

@sleeps = (); @bodies = ();
my $c = control();
$c->{Monitor}{AutoStopTimeout} = 0;
$c->moveConUp();
check('no auto stop timeout, no stop', scalar(@bodies), 1);

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
