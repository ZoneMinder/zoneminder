#!/usr/bin/perl
#
# Netcat and TapoC520WS_ONVIF moveMap.
#
# The web click and zmtrack hand moveMap a point in image pixels, but an
# ONVIF AbsoluteMove position is normalised to -1..1, as ONVIF.pm already
# converts it.  These modules sent the pixels unchanged.
#
# Run as: perl tests/perl/test_onvif_movemap_position.pl
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

use ZoneMinder::Control::Netcat;
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

my @bodies;
{
  no warnings 'redefine';
  *ZoneMinder::Control::Netcat::sendCmd = sub { push @bodies, $_[2]; return 1; };
  *ZoneMinder::Control::TapoC520WS_ONVIF::sendCmd = sub { push @bodies, $_[2]; return 1; };
}

foreach my $class ('ZoneMinder::Control::Netcat', 'ZoneMinder::Control::TapoC520WS_ONVIF') {
  my $control = bless {Monitor => {Width => 640, Height => 480}}, $class;
  foreach my $case ([320, 240, '0.0000', '0.0000'], [0, 0, '-1.0000', '1.0000'], [640, 480, '1.0000', '-1.0000'], [480, 120, '0.5000', '0.5000']) {
    my ($px, $py, $wx, $wy) = @$case;
    @bodies = ();
    $control->moveMap({xcoord => $px, ycoord => $py});
    my ($x, $y) = ($bodies[0] // '') =~ /<PanTilt x="([^"]*)" y="([^"]*)"/;
    check("$class moveMap $px,$py sends x $wx", $x, $wx);
    check("$class moveMap $px,$py sends y $wy", $y, $wy);
  }
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
