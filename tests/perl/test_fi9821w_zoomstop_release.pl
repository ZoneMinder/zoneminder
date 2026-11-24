#!/usr/bin/perl
#
# ZoneMinder::Control::FI9821W_Y2k must implement zoomStop(), focusStop() and
# irisStop().
#
# The FI9821W Controls row has continuous zoom, focus and iris, and the web UI
# sends zoomStop, focusStop or irisStop when such a button is released. The
# module only had moveStop, so zmcontrol logged an error for every release and
# the OSD text set by the button (for example "Manual IR LED Switch ON") stayed
# on the picture.
#
# Run as: perl tests/perl/test_fi9821w_zoomstop_release.pl
#
use strict;
use warnings;
use FindBin;
use lib (($ENV{ZM_TEST_ROOT} // "$FindBin::Bin/../..") . '/scripts/ZoneMinder/lib');

use ZoneMinder::Control::FI9821W_Y2k;

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

my @sent;
{
  no warnings qw(redefine once);
  *ZoneMinder::Control::FI9821W_Y2k::sendCmd = sub { push @sent, $_[1]; return 1; };
}

my $c = bless {
  Monitor => {ControlAddress => '10.0.0.5:88', ControlDevice => 'admin:pw', AutoStopTimeout => 0},
}, 'ZoneMinder::Control::FI9821W_Y2k';

for my $cmd (qw(zoomStop focusStop irisStop)) {
  check("zmcontrol can run $cmd", $c->can($cmd) ? 'yes' : 'no', 'yes');
  next if !$c->can($cmd);
  @sent = ();
  $c->$cmd();
  check("$cmd sends the ptz stop", scalar(grep { $_ eq 'ptzStopRun' } @sent), 1);
  check("$cmd clears the OSD text", scalar(grep { $_ eq 'setDevName%26devName%3D.' } @sent), 1);
  check("$cmd does not start a move", scalar(grep { /^ptzMove|InfraLed/ } @sent), 0);
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
