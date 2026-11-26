use strict;
use warnings;
use Test::More;

# The web UI sends moveStop when any move or zoom button is released.
# Each direction on this camera has its own stop code, so moveStop must
# send the one for the move that is running, not always "stop up".

require_ok('ZoneMinder::Control::LoftekSentinel');
my $P = 'ZoneMinder::Control::LoftekSentinel';

my @sent;
{
  no warnings 'redefine';
  *ZoneMinder::Control::LoftekSentinel::sendCmd = sub { push @sent, $_[1]; return 1; };
}

my %stop_for = (
  moveConUp    => 1,
  moveConDown  => 3,
  moveConLeft  => 5,
  moveConRight => 7,
  zoomConTele  => 19,
  zoomConWide  => 17,
);

for my $move (sort keys %stop_for) {
  my $self = bless { Monitor => { AutoStopTimeout => 0 } }, $P;
  @sent = ();
  $self->$move();
  $self->moveStop();
  is($sent[-1], "decoder_control.cgi?command=$stop_for{$move}",
    "moveStop after $move sends stop code $stop_for{$move}");
}

my $fresh = bless { Monitor => { AutoStopTimeout => 0 } }, $P;
@sent = ();
$fresh->moveStop();
is($sent[-1], 'decoder_control.cgi?command=1', 'moveStop with no move running still sends a stop');

done_testing();
