use strict;
use warnings;
use Test::More tests => 32;

require_ok('ZoneMinder::Control::IPSpeaker');

my $P = 'ZoneMinder::Control::IPSpeaker';

# --- file id validation -------------------------------------------------------
#
# The firmware answers result -2 "music file value error" for an id outside its
# two windows and -3 "music file empty" for an id inside a window with nothing
# uploaded.  Only the first is worth refusing locally: an empty user slot is a
# legitimate id that the operator may fill later.

ok($P->can('valid_fileid')->(10), 'first system sound is a valid id');
ok($P->can('valid_fileid')->(14), 'last system sound is a valid id');
ok(!$P->can('valid_fileid')->(9), 'id below the system window is refused');
ok(!$P->can('valid_fileid')->(15), 'id in the gap between windows is refused');
ok($P->can('valid_fileid')->(20), 'first user slot is a valid id');
ok($P->can('valid_fileid')->(30), 'last user slot is a valid id');
ok(!$P->can('valid_fileid')->(31), 'id above the user window is refused');
ok(!$P->can('valid_fileid')->('abc'), 'non-numeric id is refused');
ok(!$P->can('valid_fileid')->(''), 'empty id is refused');
ok(!$P->can('valid_fileid')->(undef), 'undefined id is refused');

# --- volume arithmetic --------------------------------------------------------

is($P->can('clamp_volume')->(-5), 0, 'volume below range clamps to the floor');
is($P->can('clamp_volume')->(0), 0, 'zero is a legal volume');
is($P->can('clamp_volume')->(60), 60, 'in-range volume is unchanged');
is($P->can('clamp_volume')->(100), 100, 'top of range is a legal volume');
is($P->can('clamp_volume')->(150), 100, 'volume above range clamps to the ceiling');

is($P->can('stepped_volume')->(60, 5), 65, 'a step up adds to the current volume');
is($P->can('stepped_volume')->(60, -5), 55, 'a step down subtracts from it');
is($P->can('stepped_volume')->(98, 5), 100, 'a step up saturates at the ceiling');
is($P->can('stepped_volume')->(2, -5), 0, 'a step down saturates at the floor');
is($P->can('stepped_volume')->('60', 5), 65, 'a volume read back as a string still works');
is($P->can('stepped_volume')->(undef, 5), undef, 'an unreadable current volume yields undef');

# --- request building ---------------------------------------------------------

is($P->can('spk_path')->('start', 10), '/api/spkplay?action=start&fileid=10',
  'play builds the documented query');
is($P->can('spk_path')->('stop', 30), '/api/spkplay?action=stop&fileid=30',
  'stop builds the documented query');

# --- audio.set is a whole-section write ---------------------------------------
#
# The firmware replaces the entire audio section on every .set, so a partial
# write silently reverts every field left out.  audio_set_form must echo the
# current values back with only the requested override applied.

my %current = (
  micgain => '0', micenable => '1', micvolume => '80', outvolume => '60',
  audiojitterbuffer => '360', codec => 'opus,pcmu,pcma,gsm,g722',
  playring => 'ring1', automute => '1', aecenable => '1', aectime => '20',
  agcenable => '1', agcmode => '0', agctarget => '3', agcgain => '30',
  nrenable => '1', nrlevel => '1', michpfenable => '0', outhpfenable => '0',
  outnrenable => '0', outnrlevel => '3',
  outmute => '0',   # returned by audio.get but not accepted by audio.set
);

my $form = $P->can('audio_set_form')->(\%current, outvolume => 15);
is($form->{outvolume}, 15, 'the requested override is applied');
is($form->{micvolume}, '80', 'an untouched field is echoed back unchanged');
is($form->{codec}, 'opus,pcmu,pcma,gsm,g722', 'the codec list survives the round trip');
is($form->{agcgain}, '30', 'a field the ui hides is still preserved');
ok(!exists $form->{outmute},
  'outmute is dropped because audio.set does not accept it');
is(scalar keys %$form, 20, 'every field audio.set expects is present, and no others');

# A device that answered .get with a missing field must not gain an empty one.
my %sparse = (outvolume => '60', micvolume => '80');
my $sparse_form = $P->can('audio_set_form')->(\%sparse, outvolume => 0);
is_deeply([sort keys %$sparse_form], [qw(micvolume outvolume)],
  'fields absent from the device response are not invented');
is($sparse_form->{outvolume}, 0, 'a zero override is applied, not treated as absent');
