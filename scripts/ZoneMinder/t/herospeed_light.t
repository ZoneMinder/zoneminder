use strict;
use warnings;
use Test::More tests => 29;
use MIME::Base64 qw(encode_base64);
use Digest::SHA qw(sha256_hex);

require_ok('ZoneMinder::Control::Herospeed');
my $P = 'ZoneMinder::Control::Herospeed';

# It must keep everything ONVIF already does for these cameras.
ok($P->isa('ZoneMinder::Control::ONVIF'), 'inherits the ONVIF protocol');
ok($P->can('moveConUp'), 'so ptz still works');
ok($P->can('presetGoto'), 'and presets');
ok($P->can('lightOn') && $P->can('lightOff'), 'and it adds the light');

# --- hex to bytes ---------------------------------------------------------
# Each round hashes the BYTES the previous hex digest stands for, not the hex
# text. Hashing the text instead yields an equally plausible digest and a
# login that simply fails, so this is pinned.

my $h2b = $P->can('hex_to_bytes');
is($h2b->('00ff'), "\x00\xff", 'hex becomes the bytes it names');
is($h2b->('0x41'), 'A', 'a 0x prefix is ignored');
is($h2b->(''), '', 'empty hex is an empty string');
is($h2b->(undef), '', 'undef is an empty string, not a crash');
is($h2b->('abc'), undef, 'odd length hex is rejected rather than truncated');
# A NUL in the middle must survive into the digest input.
is(length($h2b->('41004200')), 4, 'embedded NUL bytes are kept');

# --- the rounds -----------------------------------------------------------

my $r2 = $P->can('round_two');
my $r3 = $P->can('round_three');
my $r4 = $P->can('round_four');

is($r2->('admin','SALT','B64','PASS'), sha256_hex('adminSALTB64PASS'),
  'round two hashes the concatenated text');
is($r2->('admin','SALT','B64',undef), sha256_hex('adminSALTB64'),
  'a missing field contributes nothing rather than the string "undef"');

my $d = sha256_hex('x');
is($r3->($d, 'CHAL'), sha256_hex(pack('H*',$d).'CHAL'),
  'round three hashes raw digest bytes then the challenge text');
isnt($r3->($d, 'CHAL'), sha256_hex($d.'CHAL'),
  'and is NOT the hex text with the challenge appended');
is($r3->('abc','CHAL'), undef, 'bad hex gives undef rather than a wrong digest');

is($r4->(0, $d), $d, 'zero iterations leaves the digest alone');
is($r4->(undef, $d), $d, 'as does an undefined count');
is($r4->(1, $d), sha256_hex(pack('H*',$d)), 'one iteration rehashes the bytes');
is($r4->(2, $d), sha256_hex(pack('H*',sha256_hex(pack('H*',$d)))),
  'two iterations chain, each over the bytes');

# --- whole handshake ------------------------------------------------------
# Replays the shape of the exchange captured from the camera on 2026-10-13:
# enableIteration false, so round four is skipped.

my $lp = $P->can('login_password');
my $ts = '2026-10-13T13:01:02';
my $expect = sha256_hex(pack('H*',
    sha256_hex('admin'.'f3415cba'.encode_base64($ts,'').'secret')).'e8af9ea8');
is($lp->(user=>'admin', pass=>'secret', salt=>'f3415cba', challenge=>'e8af9ea8',
         timestamp=>$ts, enable_iteration=>0),
   $expect, 'the handshake reproduces the captured algorithm');
isnt($lp->(user=>'admin', pass=>'wrong', salt=>'f3415cba', challenge=>'e8af9ea8',
           timestamp=>$ts, enable_iteration=>0),
     $expect, 'a different password gives a different digest');
# The timestamp is both hashed and sent, so the two must agree.
isnt($lp->(user=>'admin', pass=>'secret', salt=>'f3415cba', challenge=>'e8af9ea8',
           timestamp=>'2026-10-13T13:01:03', enable_iteration=>0),
     $expect, 'the timestamp is part of the digest');

# --- timestamp format -----------------------------------------------------
my $lt = $P->can('login_timestamp');
like($lt->(0), qr/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\z/,
  'timestamp has the exact shape the camera expects');
unlike($lt->(0), qr/[Zz+]/, 'and carries no timezone, which the camera rejects');

# --- the led mode merge ---------------------------------------------------
# A partial write makes the camera drop whatever it was not sent, so the
# untouched fields have to survive.

my $wlm = $P->can('with_led_mode');
my %section = (ledMode => 2, lightBrightness => 100, delayTime => 1,
               dayNightMode => 0, sensitivity => 3);
my $merged = $wlm->(\%section, 0);
is($merged->{ledMode}, 0, 'the mode is changed');
is($merged->{lightBrightness}, 100, 'brightness is echoed back untouched');
is($section{ledMode}, 2, 'the original section is not mutated');
is(scalar keys %$merged, 5, 'and no field is invented or dropped');
