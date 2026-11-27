use strict;
use warnings;
use Test::More;

# The preset actions must send an explicit speed and a complete preset
# payload, even when the caller (zmcontrol, or a Controls row with no
# HasPanSpeed/HasTiltSpeed) supplies neither speed nor a preset name.
# getParam() logs a "missing mandatory parameter" through Error(), so an
# empty argument hash doubling as a valid request is only safe if the
# module does not route "preset"/"speed" through it.

require_ok('ZoneMinder::Control::Herospeed');

my (@calls, @errors);
{
  no warnings qw(redefine once);
  *ZoneMinder::Control::Herospeed::ptz = sub {
    my ($self, $optType, %extra) = @_;
    push @calls, { optType => $optType, extra => {%extra} };
    return 1;
  };
  *ZoneMinder::Control::Error = sub {
    push @errors, [@_];
    return undef;
  };
  *ZoneMinder::Control::Herospeed::Error = sub {
    push @errors, [@_];
    return undef;
  };
}

my $self = bless {}, 'ZoneMinder::Control::Herospeed';

sub run {
  my ($method, $params) = @_;
  @calls = ();
  @errors = ();
  $self->$method($params);
  return $calls[-1];
}

# --- no speed, no preset name --------------------------------------------
# These are the shapes zmcontrol actually produces when the monitor has no
# speed support configured. None of them may be refused for a missing
# parameter, and all of them must still carry the default speed.

for my $case (
  ['presetGoto',  {preset => 3}, 40, 'preset3'],
  ['presetSet',   {preset => 3}, 44, 'preset3'],
  ['presetHome',  {},            40, 'preset1'],
) {
  my ($method, $params, $optType, $presetName) = @$case;
  my $call = run($method, $params);

  is(scalar @errors, 0, "$method with no speed logs no error")
    or diag join('; ', map { join(' ', @$_) } @errors);
  ok($call, "$method sends a ptz command");
  is($call->{optType}, $optType, "$method uses optType $optType");
  is($call->{extra}{speed}, 4, "$method falls back to SPEED_DEFAULT");
  is($call->{extra}{preset}{presetName}, $presetName,
    "$method names preset $presetName");
}

# --- explicit values are honoured ----------------------------------------

my $goto = run('presetGoto', {preset => 3, speed => 6});
is(scalar @errors, 0, 'presetGoto with an explicit speed logs no error');
is($goto->{optType}, 40, 'presetGoto still uses optType 40');
is($goto->{extra}{speed}, 6, 'an explicit goto speed is used');

my $set = run('presetSet', {preset => 3, presetName => 'Gate'});
is(scalar @errors, 0, 'presetSet with a name logs no error');
is($set->{optType}, 44, 'presetSet still uses optType 44');
is($set->{extra}{preset}{presetName}, 'Gate', 'an explicit preset name is used');

done_testing();
