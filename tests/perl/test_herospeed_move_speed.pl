#!/usr/bin/perl
#
# ZoneMinder::Control::Herospeed pan/tilt speed.
#
# The web ui sends --panspeed/--tiltspeed for pan and tilt moves and --speed
# only for zoom, focus and iris. Herospeed read 'speed' for every move, so a
# pan or tilt always went out at the default speed.
#
# Run as: perl tests/perl/test_herospeed_move_speed.pl
# Set ZM_CONTROL_LIB to a directory holding ZoneMinder/ to test another copy.
#
use strict;
use warnings;
use FindBin;
use lib ($ENV{ZM_CONTROL_LIB} // "$FindBin::Bin/../../scripts/ZoneMinder/lib");

use ZoneMinder::Control::Herospeed;

my ($passes, $failures) = (0, 0);
sub check {
  my ($name, $got, $want) = @_;
  if ((defined $got ? $got : '(undef)') eq (defined $want ? $want : '(undef)')) {
    $passes++;
    print "ok - $name\n";
  } else {
    $failures++;
    print "FAIL - $name\n  got:  ".(defined $got ? $got : '(undef)')."\n  want: ".(defined $want ? $want : '(undef)')."\n";
  }
}

my @sent;
{
  no warnings 'redefine';
  *ZoneMinder::Control::Herospeed::api_retry = sub {
    my ($self, $path, $action, $data) = @_;
    push @sent, join(',', map { "$_=$data->{$_}" } sort grep { !ref $data->{$_} } keys %$data);
    return {code => 0};
  };
}

my $control = bless {Monitor => {Id => 1, AutoStopTimeout => 0}}, 'ZoneMinder::Control::Herospeed';

sub run {
  my ($cmd, $params) = @_;
  @sent = ();
  $control->$cmd($params);
  return $sent[0];
}

# optType: up 10, down 11, left 12, right 13, leftup 14, leftdown 15, rightup 16, rightdown 17
check('left uses panspeed',  run('moveConLeft',  {panspeed => 6}), 'optType=12,speed=6');
check('right uses panspeed', run('moveConRight', {panspeed => 2}), 'optType=13,speed=2');
check('up uses tiltspeed',   run('moveConUp',    {tiltspeed => 7}), 'optType=10,speed=7');
check('down uses tiltspeed', run('moveConDown',  {tiltspeed => 3}), 'optType=11,speed=3');
check('diagonal uses panspeed', run('moveConUpLeft', {panspeed => 5, tiltspeed => 8}), 'optType=14,speed=5');
check('diagonal falls back to tiltspeed', run('moveConDownRight', {tiltspeed => 5}), 'optType=17,speed=5');
check('up ignores panspeed when tiltspeed is given', run('moveConUp', {panspeed => 1, tiltspeed => 8}), 'optType=10,speed=8');
check('speed above the window is clamped', run('moveConLeft', {panspeed => 50}), 'optType=12,speed=8');
check('no speed given uses the default', run('moveConLeft', {}), 'optType=12,speed=4');
check('zoom still uses speed', run('zoomConTele', {speed => 6}), 'optType=20,speed=6');

print "$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
