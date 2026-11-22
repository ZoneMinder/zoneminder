use strict;
use warnings;
use Test::More;

# presetHome must use the same cgi-bin/hi3510/ path as presetGoto;
# without the prefix the Home button asks for a page the camera lacks.

require_ok('ZoneMinder::Control::SPP1802SWPTZ');
my $P = 'ZoneMinder::Control::SPP1802SWPTZ';

my @sent;
{
  no warnings 'redefine';
  *ZoneMinder::Control::SPP1802SWPTZ::sendCmd = sub { push @sent, $_[1]; return 1; };
}

my $self = bless { Monitor => { AutoStopTimeout => 0 } }, $P;

@sent = ();
$self->presetGoto({ preset => 1 });
my $goto = $sent[-1];

@sent = ();
$self->presetHome();
my $home = $sent[-1];

like($home, qr{^cgi-bin/hi3510/ptzgotopoint\.cgi\?}, 'presetHome uses the cgi-bin/hi3510 path');
like($home, qr{-point=1$}, 'presetHome goes to point 1');
is($home, $goto, 'presetHome sends the same request as presetGoto preset 1');

done_testing();
