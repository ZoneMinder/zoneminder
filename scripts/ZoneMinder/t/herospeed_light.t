use strict;
use warnings;
use Test::More tests => 128;
use MIME::Base64 qw(encode_base64);
use Digest::SHA qw(sha256_hex);

require_ok('ZoneMinder::Control::Herospeed');
my $P = 'ZoneMinder::Control::Herospeed';

# --- it stands alone ------------------------------------------------------
# This drives the vendor web api directly. ONVIF stays a separate protocol
# for these cameras, so nothing here may depend on it.

ok($P->isa('ZoneMinder::Control'), 'inherits the control base');
ok(!$P->isa('ZoneMinder::Control::ONVIF'), 'and is NOT an ONVIF subclass');

# --- the whole dispatch surface exists ------------------------------------
# zmcontrol calls $control->$command(...) off the monitor's configured
# buttons and actions. A method named in the Controls row but missing here
# is a configuration error at best; these are the ones this protocol claims.

for my $m (qw(open close moveConUp moveConDown moveConLeft moveConRight
              moveConUpLeft moveConUpRight moveConDownLeft moveConDownRight
              moveStop zoomConTele zoomConWide zoomStop
              focusConNear focusConFar focusStop focusAuto
              irisConOpen irisConClose irisStop
              presetGoto presetSet presetClear presetHome
              horizontalPatrol horizontalPatrolStop
              lightOn lightOff lightStatus ptzStatus reset reboot)) {
  ok($P->can($m), "implements $m");
}

# Standing alone means nothing is inherited from ONVIF.pm's import list, so
# every module used at runtime has to be pulled in here. perl -c passes
# either way; the failure only appears when the code path runs.
ok($INC{'LWP/UserAgent.pm'}, 'LWP::UserAgent is loaded, not borrowed from ONVIF');
ok($INC{'Time/HiRes.pm'}, 'Time::HiRes is loaded');
ok($INC{'Digest/SHA.pm'}, 'Digest::SHA is loaded for the login rounds');

# usleep is called on the AutoStopTimeout path, which perl -c cannot reach.
# Without the import that path dies at runtime, only for monitors that have
# a timeout set - the kind of gap that ships.
ok(defined &ZoneMinder::Control::Herospeed::usleep,
  'usleep is imported, so the auto-stop path cannot die undefined');

# --- login maths ----------------------------------------------------------
# Each round hashes the BYTES the previous hex digest names, not the hex
# text. Getting it wrong yields an equally plausible digest and a refused
# login with no other symptom.

my $h2b = $P->can('hex_to_bytes');
is($h2b->('00ff'), "\x00\xff", 'hex becomes the bytes it names');
is($h2b->('0x41'), 'A', 'a 0x prefix is ignored');
is($h2b->(''), '', 'empty hex is an empty string');
is($h2b->(undef), '', 'undef is an empty string, not a crash');
is($h2b->('abc'), undef, 'odd length hex is rejected, not truncated');
is($h2b->('zz'), undef, 'non hex is rejected rather than packed to garbage');
is(length($h2b->('41004200')), 4, 'embedded NUL bytes survive');

my $d = sha256_hex('x');
is($P->can('round_two')->('admin','SALT','B64','PASS'),
   sha256_hex('adminSALTB64PASS'), 'round two hashes the concatenated text');
is($P->can('round_two')->('admin','SALT','B64',undef),
   sha256_hex('adminSALTB64'), 'a missing field adds nothing, not "undef"');
is($P->can('round_three')->($d,'CHAL'), sha256_hex(pack('H*',$d).'CHAL'),
   'round three hashes raw digest bytes then the challenge');
isnt($P->can('round_three')->($d,'CHAL'), sha256_hex($d.'CHAL'),
   'and is NOT the hex text with the challenge appended');
is($P->can('round_three')->('abc','CHAL'), undef, 'bad hex gives undef');
is($P->can('round_four')->(0,$d), $d, 'zero iterations is a no-op');
is($P->can('round_four')->(undef,$d), $d, 'as is an undefined count');
is($P->can('round_four')->(2,$d),
   sha256_hex(pack('H*',sha256_hex(pack('H*',$d)))), 'iterations chain over bytes');

# Replays the exchange captured from the camera on 2026-10-13.
my $ts = '2026-10-13T13:01:02';
my $expect = sha256_hex(pack('H*',
    sha256_hex('admin'.'f3415cba'.encode_base64($ts,'').'secret')).'e8af9ea8');
is($P->can('login_password')->(user=>'admin', pass=>'secret', salt=>'f3415cba',
     challenge=>'e8af9ea8', timestamp=>$ts, enable_iteration=>0),
   $expect, 'the handshake reproduces the captured algorithm');
isnt($P->can('login_password')->(user=>'admin', pass=>'wrong', salt=>'f3415cba',
     challenge=>'e8af9ea8', timestamp=>$ts, enable_iteration=>0),
   $expect, 'a wrong password gives a different digest');
isnt($P->can('login_password')->(user=>'admin', pass=>'secret', salt=>'f3415cba',
     challenge=>'e8af9ea8', timestamp=>'2026-10-13T13:01:03', enable_iteration=>0),
   $expect, 'the timestamp is part of the digest, so it must match what is sent');

like($P->can('login_timestamp')->(0), qr/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\z/,
  'timestamp has the exact shape the camera wants');
unlike($P->can('login_timestamp')->(0), qr/[Zz+]/, 'and carries no timezone');

# --- speed ----------------------------------------------------------------
# The device advertises no range, so everything is clamped into the window
# observed to work rather than passed through.

my $cs = $P->can('clamp_speed');
is($cs->(4), 4, 'an in range speed passes through');
is($cs->(0), 1, 'below the floor clamps up');
is($cs->(99), 8, 'above the ceiling clamps down');
is($cs->(-5), 1, 'a negative speed clamps rather than being sent');
is($cs->(undef), 4, 'no speed gives the default');
is($cs->('fast'), 4, 'a non numeric speed gives the default, not a bad request');

# --- presets --------------------------------------------------------------
my $vp = $P->can('valid_preset');
ok($vp->(1), 'preset 1 is valid');
ok($vp->(255), 'preset 255 is valid');
ok(!$vp->(0), 'preset 0 is not a preset');
ok(!$vp->(256), 'above 255 is rejected');
ok(!$vp->(undef), 'undef is rejected');
ok(!$vp->('home'), 'a non numeric preset is rejected rather than sent');

my $pp = $P->can('preset_payload');
is($pp->(3,'Gate',1,0)->{presetIdx}, 3, 'payload carries the index as a number');
is($pp->(3,'Gate',1,0)->{presetName}, 'Gate', 'and the given name');
is($pp->(3,undef,1,0)->{presetName}, 'preset3', 'inventing one when absent');
is($pp->(3,'',1,0)->{presetName}, 'preset3', 'an empty name counts as absent');
is($pp->(3,'Gate',0,0)->{enablePreset}, 0, 'enablePreset is a 0/1 flag');
is($pp->(3,'Gate',1,1)->{readOnly}, 1, 'as is readOnly');

# --- the led merge --------------------------------------------------------
# A partial write makes the camera drop whatever it was not sent.
my $wlm = $P->can('with_led_mode');
my %section = (ledMode=>2, lightBrightness=>100, delayTime=>1,
               dayNightMode=>0, sensitivity=>3);
my $m = $wlm->(\%section, 0);
is($m->{ledMode}, 0, 'the mode changes');
is($m->{lightBrightness}, 100, 'brightness is echoed back');
is($m->{delayTime}, 1, 'and the delay');
is($section{ledMode}, 2, 'the original is not mutated');
is(scalar keys %$m, 5, 'no field invented or dropped');

# The constants must match the web ui's PTZ_CONTROL_CMD_* values; a wrong
# number is silently a different command.
is($P->can('CMD_STOP')->(), 0, 'stop is 0');
is($P->can('CMD_UP')->(), 10, 'up is 10');
is($P->can('CMD_MOVEPOINT')->(), 40, 'goto preset is 40');
is($P->can('CMD_PRESET_SET')->(), 44, 'store preset is 44');
is($P->can('LED_WARM')->(), 0, 'warm light is ledMode 0');
is($P->can('LED_IR')->(), 1, 'ir light is ledMode 1');


# --- time configuration ---------------------------------------------------

ok($P->can('get_time') && $P->can('set_time') && $P->can('get_dst'),
  'the time methods exist');

# The vendor's timezone list starts at GMT-12:00 and steps every 30 minutes,
# so the index is derived rather than hardcoded. Verified against the labels
# the camera's own language pack carries:
#   GMT12 "(GMT-06:00) Central Time (US&Canada)"
#   GMT14 "(GMT-05:00) Eastern Time(US&Canada)"
#   GMT24 "(GMT+00:00) Dublin, Edinburgh, London"
my $tzi = $P->can('tz_index');
is($tzi->(-6), 12, 'US Central is index 12');
is($tzi->(-5), 14, 'US Eastern is index 14');
is($tzi->(0), 24, 'UTC is index 24');
is($tzi->(-12), 0, 'the bottom of the list is index 0');
is($tzi->(5.5), 35, 'a half hour zone lands on an odd index');
is($tzi->(-9.5), 5, 'and a negative half hour zone');
is($tzi->(-13), undef, 'an offset below the list is rejected');
is($tzi->(15), undef, 'and one above it');
is($tzi->(1.25), undef, 'a quarter hour offset has no index, so it is refused');
is($tzi->(undef), undef, 'undef is refused');
is($tzi->('east'), undef, 'and a non numeric offset');

my $tzo = $P->can('tz_offset');
is($tzo->(12), -6, 'index 12 reads back as -6');
is($tzo->(24), 0, 'index 24 as UTC');
# Round tripping is what makes the log line trustworthy.
is($tzo->($tzi->(-6)), -6, 'offset survives a round trip');
is($tzo->(99), undef, 'an out of range index yields undef, not a bogus offset');

# --- change detection -----------------------------------------------------
# These settings live in flash and set_time is meant to be re-runnable, so a
# no-op call must not write.

my $tnw = $P->can('time_needs_write');
my %cur = (sntpServerAddr => 'time.windows.com', timeZone => 24, model => 2,
           ntpAutomatic => 5, hour => 19);
ok($tnw->(\%cur, {sntpServerAddr => '10.10.50.72'}), 'a different server needs writing');
ok($tnw->(\%cur, {timeZone => 12}), 'a different timezone needs writing');
ok(!$tnw->(\%cur, {sntpServerAddr => 'time.windows.com', timeZone => 24}),
  'settings that already match need no write');
ok($tnw->(\%cur, {somethingNew => 1}), 'a field the camera lacks needs writing');
# 24 vs "24" must not read as a change, or every run would rewrite flash.
ok(!$tnw->(\%cur, {timeZone => '24'}), 'a number matching its string form is no change');
ok($tnw->(undef, {timeZone => 12}), 'no current settings means write');

# The dst rules are nested, so the comparison has to recurse rather than
# compare hash references, which would always differ.
my %dst = (enable => 0, offsetTime => 30,
           start => {month => 3, day => 0, hour => 2, weekDay => 0, weekNumber => 1});
ok($tnw->(\%dst, {start => {month => 3, day => 0, hour => 2, weekDay => 0, weekNumber => 2}}),
  'a changed week number inside the nested rule is detected');
ok(!$tnw->(\%dst, {start => {month => 3, day => 0, hour => 2, weekDay => 0, weekNumber => 1}}),
  'an identical nested rule is not a change');
ok($tnw->(\%dst, {start => 'notahash'}), 'a type change counts as a change');
ok($tnw->({start => 'scalar'}, {start => {month => 3}}),
  'and so does the reverse, without dying');

# --- the merge ------------------------------------------------------------
my $tt = $P->can('time_table');
my $merged = $tt->(\%cur, sntpServerAddr => '10.10.50.72', timeZone => 12);
is($merged->{sntpServerAddr}, '10.10.50.72', 'the override is applied');
is($merged->{timeZone}, 12, 'and the second one');
is($merged->{model}, 2, 'an untouched field is echoed back');
is($merged->{ntpAutomatic}, 5, 'including one we never set');
is(scalar keys %$merged, 5, 'the merge invents no fields');
is($cur{timeZone}, 24, 'and does not mutate the original');

# --- the US rule ----------------------------------------------------------
# Second Sunday in March to first Sunday in November, 02:00, one hour.
my %us = $P->can('us_dst_rules')->();
is($us{offsetTime}, 60, 'the offset is a full hour, not the factory 30 minutes');
is($us{start}{month}, 3, 'dst starts in March');
is($us{start}{weekNumber}, 2, 'on the second week');
is($us{start}{weekDay}, 0, 'on a Sunday');
is($us{end}{month}, 11, 'and ends in November');
is($us{end}{weekNumber}, 1, 'on the first week');
ok(!exists $us{dstType}, 'dstType is left to the camera, which normalises it');
