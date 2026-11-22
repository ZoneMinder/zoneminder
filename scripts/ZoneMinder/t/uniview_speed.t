use strict;
use warnings;
use Test::More;

# The UI only sends --panspeed/--tiltspeed/--speed when the Controls row
# has HasPanSpeed/HasTiltSpeed/HasZoomSpeed. Without them a Uniview move
# must still use a non-zero speed, or the camera never moves.

require_ok('ZoneMinder::Control::Uniview');

my @xml;
{
  no warnings qw(redefine once);
  *ZoneMinder::Control::Uniview::PutCmd = sub { push @xml, $_[2]; return 1; };
}

my $self = bless { Monitor => { AutoStopTimeout => 0 } }, 'ZoneMinder::Control::Uniview';

sub sent { my ($m, $p) = @_; @xml = (); $self->$m($p); return $xml[-1] // ''; }

my ($pan) = sent('moveConLeft', {}) =~ m{<pan>(-?\d+)</pan>};
ok(defined $pan && $pan < 0, 'moveConLeft with no speed pans left') or diag "pan=".($pan//'none');
my ($tilt) = sent('moveConUp', {}) =~ m{<tilt>(-?\d+)</tilt>};
ok(defined $tilt && $tilt > 0, 'moveConUp with no speed tilts up') or diag "tilt=".($tilt//'none');
my ($zoom) = sent('zoomConTele', {}) =~ m{<zoom>(-?\d+)</zoom>};
ok(defined $zoom && $zoom > 0, 'zoomConTele with no speed zooms in') or diag "zoom=".($zoom//'none');

like(sent('moveConLeft', { panspeed => 40 }), qr{<pan>-40</pan>}, 'a given pan speed is still used');
like(sent('moveStop', {}), qr{<pan>0</pan><tilt>0</tilt><zoom>0</zoom>}, 'moveStop still sends zero');

done_testing();
