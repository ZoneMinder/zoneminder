#!/usr/bin/perl
#
# ZoneMinder::Control::FI8608W_Y2k must implement zoomStop() and irisStop().
#
# The FI8608W Controls row has continuous zoom and iris, and the web UI sends
# zoomStop or irisStop when such a button is released. The module only had
# moveStop, so zmcontrol logged an error for every release and the OSD text set
# by the button (for example "Manual IR LED ON") stayed on the picture.
#
# Run as: perl tests/perl/test_fi8608w_zoomstop_release.pl
#
use strict;
use warnings;
use FindBin;
use lib (($ENV{ZM_TEST_ROOT} // "$FindBin::Bin/../..") . '/scripts/ZoneMinder/lib');

use ZoneMinder::Control::FI8608W_Y2k;

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
  *ZoneMinder::Control::FI8608W_Y2k::sendCmd = sub { push @sent, $_[1]; return 1; };
}

my $c = bless {
  Monitor => {ControlAddress => '10.0.0.5:80', ControlDevice => 'pw', AutoStopTimeout => 0},
}, 'ZoneMinder::Control::FI8608W_Y2k';

for my $cmd (qw(zoomStop irisStop)) {
  check("zmcontrol can run $cmd", $c->can($cmd) ? 'yes' : 'no', 'yes');
  next if !$c->can($cmd);
  @sent = ();
  $c->$cmd();
  check("$cmd sends the ptz stop", scalar(grep { $_ eq 'ptzctrl.cgi?-step=0&-act=stop' } @sent), 1);
  check("$cmd hides the OSD text", scalar(grep { /setoverlayattr&-region=1&-show=0/ } @sent), 1);
  check("$cmd does not start a move", scalar(grep { /-act=(up|down|left|right)|setinfra/ } @sent), 0);
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
