use strict;
use warnings;
use Test::More;
use HTTP::Response;

# sendCmd must report whether the camera accepted the command; a 401 or an
# unreachable camera used to look like success.

require_ok('ZoneMinder::Control::Toshiba_IK_WB11A');

my $code = 200;
my @errors;
{
  package FakeUA;
  sub new { bless {}, shift }
  sub request { HTTP::Response->new($code, $code == 200 ? 'OK' : 'Unauthorized') }
}
{
  no warnings qw(redefine once);
  *ZoneMinder::Control::Toshiba_IK_WB11A::Error = sub { push @errors, $_[0] };
}

my $self = bless { Monitor => { ControlAddress => 'camera' }, ua => FakeUA->new },
  'ZoneMinder::Control::Toshiba_IK_WB11A';

ok($self->sendCmd('/control.cgi?cont_2=16'), 'a 200 reply is success');
is(scalar @errors, 0, 'and logs no error');

$code = 401;
ok(!$self->sendCmd('/control.cgi?cont_2=16'), 'a 401 reply is a failure');
is(scalar @errors, 1, 'and logs an error');

done_testing();
