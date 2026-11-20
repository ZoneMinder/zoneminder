#!/usr/bin/perl
#
# zmtrack.pl on a camera that can move relative but not to a map point.
#
# zmtrack falls back to "pseudo map mode" there and used to send
# --command=movePseudoMap to zmcontrol.pl.  No control module implements
# movePseudoMap (the web turns it into a moveRel command in
# buildControlCommand), so zmcontrol refused it and the camera never moved.
#
# Run as: perl tests/perl/test_zmtrack_pseudo_map.pl
#
use strict;
use warnings;
use FindBin;
use File::Temp qw(tempdir);
use File::Path qw(make_path);

my $failures = 0;
my $passes = 0;

sub check {
  my ($name, $got, $want) = @_;
  if ($got eq $want) {
    $passes++;
    print "ok - $name\n";
  } else {
    $failures++;
    print "FAIL - $name\n  got:  $got\n  want: $want\n";
  }
}

sub write_file {
  my ($path, $content) = @_;
  open(my $fh, '>', $path) or die "Can't write $path: $!";
  print $fh $content;
  close($fh);
}

my $dir = tempdir(CLEANUP => 1);
make_path("$dir/lib/ZoneMinder");

# One alarm at TEST_X,TEST_Y, then exit.  The monitor row is the base row below
# with the key=value pairs from TEST_ROW on top.
write_file("$dir/lib/ZoneMinder.pm", <<'EOM');
package ZoneMinder;
use strict;
use warnings;

our %Config = (ZM_PATH_BIN => '/bin');
our $iter = 0;

sub import {
  no strict 'refs';
  my $caller = caller();
  *{$caller.'::Config'} = \%Config;
  *{$caller.'::logInit'} = sub { 1 };
  *{$caller.'::logSetSignal'} = sub { 1 };
  *{$caller.'::logDebugging'} = sub { 0 };
  *{$caller.'::Debug'} = sub { 1 };
  *{$caller.'::Info'} = sub { 1 };
  *{$caller.'::Warning'} = sub { 1 };
  *{$caller.'::Fatal'} = sub { die "FATAL: $_[0]\n"; };
  *{$caller.'::zmDbConnect'} = sub { return bless {}, 'MockDBH'; };
  *{$caller.'::zmMemVerify'} = sub { 1 };
  *{$caller.'::zmMonitorSuspend'} = sub { 1 };
  *{$caller.'::zmMonitorResume'} = sub { 1 };
  *{$caller.'::zmIsAlarmed'} = sub { exit(0) if $iter++; return 1; };
  *{$caller.'::zmGetAlarmLocation'} = sub { return ($ENV{TEST_X}, $ENV{TEST_Y}); };
  *{$caller.'::executeShellCommand'} = sub { print "CMD: $_[0]\n"; 1 };
}

package MockDBH;
sub prepare_cached { return bless {}, 'MockSTH'; }

package MockSTH;
sub execute { 1 }
sub fetchrow_hashref {
  my %row = (
    Id => 1, Controllable => 1, TrackMotion => 1, TrackDelay => 0, ReturnLocation => -1,
    CanMoveMap => 0, CanMoveRel => 1,
    Width => 640, Height => 480, Orientation => 'ROTATE_0',
    MinPanStep => 1, MaxPanStep => 10, MinTiltStep => 1, MaxTiltStep => 10,
    HasPanSpeed => 1, MinPanSpeed => 1, MaxPanSpeed => 63, HasTurboPan => 0, TurboPanSpeed => undef,
    HasTiltSpeed => 1, MinTiltSpeed => 1, MaxTiltSpeed => 63, HasTurboTilt => 0, TurboTiltSpeed => undef,
  );
  foreach my $pair (split(/;/, $ENV{TEST_ROW} // '')) {
    my ($k, $v) = split(/=/, $pair, 2);
    $row{$k} = $v;
  }
  return \%row;
}
1;
EOM

write_file("$dir/lib/DBI.pm", "package DBI;\n1;\n");

my $source = do {
  open(my $fh, '<', "$FindBin::Bin/../../scripts/zmtrack.pl.in") or die "Can't open zmtrack.pl.in: $!";
  local $/;
  <$fh>;
};
$source =~ s/\@PERL_EXECUTABLE\@/$^X/g;
$source =~ s/\@EXTRA_PERL_LIB\@//g;
$source =~ s/\@ZM_SCRIPT_PATH\@/\/usr\/bin:\/bin/g;
write_file("$dir/zmtrack.pl", $source);

# Returns the zmcontrol options zmtrack sent for an alarm at x,y, or '' if it sent nothing.
sub track {
  my ($x, $y, $row) = @_;
  local $ENV{TEST_X} = $x;
  local $ENV{TEST_Y} = $y;
  local $ENV{TEST_ROW} = $row // '';
  my $out = qx($^X -T -I$dir/lib $dir/zmtrack.pl --monitor=1 2>&1);
  return 'exit '.($? >> 8).": $out" if $?;
  my ($cmd) = $out =~ m{^CMD: /bin/zmcontrol\.pl -i 1 (.*)$}m;
  return $cmd // '';
}

# 640x480: (600,240) is 0.875 of the way from the centre to the right edge.
# Same options buildControlCommand produces for movePseudoMap at that point.
check('alarm right of centre pans right by a relative step',
  track(600, 240), '--command=moveRelRight --panstep=9 --panspeed=55');

check('alarm in the blind spot in the middle sends no command',
  track(330, 250), '');

# The alarm location is in the rotated image (480x640); the camera itself
# moves in its own frame, as the web does for a click on a rotated monitor.
check('alarm on a 90 degree rotated monitor moves in the camera frame',
  track(120, 160, 'Orientation=ROTATE_90'),
  '--command=moveRelDownLeft --panstep=6 --panspeed=32 --tiltstep=6 --tiltspeed=32');

check('alarm on a 270 degree rotated monitor moves the other way',
  track(120, 160, 'Orientation=ROTATE_270'),
  '--command=moveRelUpRight --panstep=6 --panspeed=32 --tiltstep=6 --tiltspeed=32');

check('alarm on a 180 degree rotated monitor',
  track(600, 100, 'Orientation=ROTATE_180'),
  '--command=moveRelDownLeft --panstep=9 --panspeed=55 --tiltstep=6 --tiltspeed=37');

check('alarm on a horizontally flipped monitor',
  track(600, 240, 'Orientation=FLIP_HORI'), '--command=moveRelLeft --panstep=9 --panspeed=55');

# Speed ranges can be negative (the PSIA control type uses -100 to 100); the web
# rounds half away from zero.
check('negative speed range rounds like the web',
  track(0, 130, 'MinPanSpeed=-100;MaxPanSpeed=100;MinTiltSpeed=-100;MaxTiltSpeed=100'),
  '--command=moveRelUpLeft --panstep=10 --panspeed=100 --tiltstep=5 --tiltspeed=-8');

check('turbo pan speed is used near the edge',
  track(630, 20, 'HasTurboPan=1;TurboPanSpeed=100'),
  '--command=moveRelUpRight --panstep=10 --panspeed=100 --tiltstep=9 --tiltspeed=58');

check('no speed options for a camera without speed control',
  track(600, 240, 'HasPanSpeed=0;HasTiltSpeed=0'), '--command=moveRelRight --panstep=9');

check('a camera that can move to a map point still gets moveMap',
  track(600, 240, 'CanMoveMap=1'), '--command=moveMap --xcoord=600 --ycoord=240');

# moveMap takes a point in the camera's own 640x480 frame, the alarm location is in
# the rotated or flipped image (a 90 degree rotation turns camera x,y into 480-y,x).
check('moveMap on a 90 degree rotated monitor sends the point in the camera frame',
  track(120, 160, 'CanMoveMap=1;Orientation=ROTATE_90'), '--command=moveMap --xcoord=160 --ycoord=360');

check('moveMap on a 270 degree rotated monitor',
  track(120, 160, 'CanMoveMap=1;Orientation=ROTATE_270'), '--command=moveMap --xcoord=480 --ycoord=120');

check('moveMap on a 180 degree rotated monitor',
  track(600, 100, 'CanMoveMap=1;Orientation=ROTATE_180'), '--command=moveMap --xcoord=40 --ycoord=380');

check('moveMap on a horizontally flipped monitor',
  track(600, 240, 'CanMoveMap=1;Orientation=FLIP_HORI'), '--command=moveMap --xcoord=40 --ycoord=240');

check('moveMap on a vertically flipped monitor',
  track(600, 100, 'CanMoveMap=1;Orientation=FLIP_VERT'), '--command=moveMap --xcoord=600 --ycoord=380');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
