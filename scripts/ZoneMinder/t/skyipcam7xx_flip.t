use strict;
use warnings;
use Test::More tests => 17;

require_ok('ZoneMinder::Control::SkyIPCam7xx');
my $P = 'ZoneMinder::Control::SkyIPCam7xx';

# buildControlCommand in web/includes/control_functions.php has already
# converted Left and Right for the monitor's Orientation before the command
# reaches this module. A second swap here sends the camera the opposite way,
# so every method must send the direction it is named for whatever the
# monitor's Orientation says.

my $step = { panstep => 1, tiltstep => 1 };

for my $orientation (qw(FLIP_HORI ROTATE_0)) {
  my @sent;
  my $control = bless { Monitor => { Orientation => $orientation } }, $P;

  no warnings 'redefine', 'once';
  local *ZoneMinder::Control::SkyIPCam7xx::sendCmd = sub {
    push @sent, $_[1];
    return 1;
  };

  for my $dir (qw(left right up down)) {
    @sent = ();
    my $method = "moveRel" . ucfirst($dir);
    $control->$method($step);
    is(scalar @sent, 1, "$orientation: $method sends one command");
    like($sent[0], qr/\bmove=$dir\b/,
      "$orientation: $method asks for $dir, not the opposite");
  }
}
