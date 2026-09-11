use strict;
use warnings;
use Test::More tests => 43;

require_ok('ZoneMinder::Control::AMLink');

my $P = 'ZoneMinder::Control::AMLink';
my $key_from_string = $P->can('key_from_string');
my $mask_data       = $P->can('mask_data');
my $build_envelope  = $P->can('build_envelope');
my $extract_cmd     = $P->can('extract_cmd');
my $cmd_type_for    = $P->can('cmd_type_for');
my $numeric_salt    = $P->can('numeric_salt');
my $parse_rsa_pub   = $P->can('parse_rsa_pub');

# --- the mask key is the byte values of the key string -----------------------

is_deeply($key_from_string->('AB'), [65, 66], 'key is the ordinals of the string');
is($key_from_string->(''), undef, 'an empty key string yields undef');
is($key_from_string->(undef), undef, 'an undefined key string yields undef');
# The device hands back a 32 character hex string; nothing may truncate it.
is(scalar @{$key_from_string->('b3729e0a14befba6205e7d29195e03c0')}, 32,
  'a real 32 character device key gives 32 bytes');

# --- masking -----------------------------------------------------------------

my $key = $key_from_string->('KEY');
is($mask_data->($key, 'AAA'), chr(65^75).chr(65^69).chr(65^89),
  'each byte is xored against the cycling key');

# Symmetry is what lets one routine serve both directions.
my $plain = 'eyJtZXRob2QiOiJnbG9iYWwubG9naW4ifQ==';
is($mask_data->($key, $mask_data->($key, $plain)), $plain,
  'masking twice returns the original');

# The key is shorter than the data here, so this also proves the cycling.
my $long = 'x' x 100;
is(length($mask_data->($key, $long)), 100, 'masking preserves length');
is($mask_data->($key, $long), $mask_data->($key, $long), 'masking is deterministic');

# A missing key must pass data through rather than crash: the bootstrap
# channels legitimately have no key yet.
is($mask_data->(undef, 'abc'), 'abc', 'no key leaves the payload untouched');
is($mask_data->([], 'abc'), 'abc', 'an empty key leaves the payload untouched');

# Masking is byte-oriented; a key byte equal to the data byte yields NUL, which
# must survive rather than terminate the string.
is(length($mask_data->($key_from_string->('A'), 'AAA')), 3,
  'NUL bytes produced by masking are kept');

# --- envelope ----------------------------------------------------------------

is($build_envelope->('Request', 'PAYLOAD'),
  '<body><cmdType>Request</cmdType><cmd>PAYLOAD</cmd></body>',
  'envelope carries cmdType and cmd');

# cmdType is not optional: the camera drops the connection without it.
like($build_envelope->('Login', 'x'), qr{<cmdType>Login</cmdType>},
  'the login envelope names its channel');

# --- extracting the reply ----------------------------------------------------

is($extract_cmd->('<body><cmd>ABC</cmd></body>'), 'ABC', 'reads a single line reply');

# Real replies wrap the payload in CRLF, which is not part of the base64.
is($extract_cmd->("<body>\r\n<cmd>\r\nABC\r\n</cmd>\r\n</body>\r\n"), 'ABC',
  'strips the newlines the device pads the payload with');
is($extract_cmd->("<body>\n<cmd>\nA+B/C=\n</cmd>\n</body>"), 'A+B/C=',
  'keeps base64 punctuation intact');
is($extract_cmd->('<body></body>'), undef, 'a reply with no cmd yields undef');
is($extract_cmd->(undef), undef, 'undef input yields undef');

# --- channel routing ---------------------------------------------------------
# Getting this wrong is silent: the camera resets the connection rather than
# returning an error.

is($cmd_type_for->('global.login', login => 1), 'Login', 'login uses the Login channel');
is($cmd_type_for->('Security.getEncryptInfo', outside => 1), 'OutsideCmd',
  'the pre-auth key fetch uses OutsideCmd');
is($cmd_type_for->('LXSecurity.getGeneralKey'), 'GetGeneralKey',
  'the key exchange routes by method name, without an option');
is($cmd_type_for->('CoaxialControlIO.control'), 'Request',
  'ordinary commands use the Request channel');

# --- salt --------------------------------------------------------------------

is(length($numeric_salt->(32)), 32, 'salt is exactly the requested length');
like($numeric_salt->(32), qr/\A[0-9]{32}\z/,
  'salt is decimal digits only, because it doubles as the AES key');

# --- RSA public key ----------------------------------------------------------

is_deeply($parse_rsa_pub->('N:BDE596CF,E:010001'), { N => 'BDE596CF', e => '010001' },
  'splits the device pub string into modulus and exponent');

# --- NTP -----------------------------------------------------------------
# The camera keeps these in flash and set_time is meant to be safe to re-run,
# so the change detection is what stops it burning erase cycles.

my $ntp_table       = $P->can('ntp_table');
my $ntp_needs_write = $P->can('ntp_needs_write');

# A real section as the camera returns it.
my %current = (
  Address => 'time.windows.com', Enable => 1, Port => 123,
  TimeZone => 26, TimeZoneDesc => 'Middletime', UpdatePeriod => 1440,
);

my $merged = $ntp_table->(\%current, Address => '10.0.0.1', UpdatePeriod => 1);
is($merged->{Address}, '10.0.0.1', 'the override is applied');
is($merged->{UpdatePeriod}, 1, 'the second override is applied too');
# A partial write would have the camera drop whatever it was not sent, so the
# untouched fields must survive the merge.
is($merged->{TimeZone}, 26, 'an untouched field is echoed back');
is($merged->{TimeZoneDesc}, 'Middletime', 'including the one we deliberately never set');
is($merged->{Port}, 123, 'and the port');
is(scalar keys %$merged, 6, 'the merge invents no fields');

# The original must not be modified in place: set_time reads it, compares, then
# merges, and a mutated copy would defeat the comparison.
is($current{Address}, 'time.windows.com', 'the current settings are not mutated');

ok($ntp_needs_write->(\%current, {Address => '10.0.0.1'}),
  'a different server needs writing');
ok($ntp_needs_write->(\%current, {UpdatePeriod => 1}),
  'a different interval needs writing');
ok(!$ntp_needs_write->(\%current, {Address => 'time.windows.com', Port => 123}),
  'settings that already match need no write');
ok($ntp_needs_write->(\%current, {Nonexistent => 1}),
  'a field the camera does not have yet needs writing');
# 1440 vs "1440" must not read as a change, or every run would rewrite flash.
ok(!$ntp_needs_write->(\%current, {UpdatePeriod => '1440'}),
  'a numeric field matching its string form needs no write');

# --- refusing to send without a key --------------------------------------
# Dahua_RPC re-logins on error from several places and does not always check
# whether it worked, so rpc_call can be reached with the key cleared. Sending
# a Request unmasked gets a masked reply back, which fails as an unreadable
# JSON decode error instead of saying the session is gone.

{
  my @sent;
  my $obj = bless {
    session => 'abc', mask_key => undef, rpc_id => 0, host => 'h',
    RPCBase => 'http://h/Onvif/device_service',
    ua => bless({}, 'FakeUA'),
  }, $P;
  # FakeUA records any request that escapes, so the test fails loudly if one does.
  { no strict 'refs';
    *{'FakeUA::post'} = sub { push @sent, $_[1]; die "a request escaped without a key\n" };
    *{'FakeUA::agent'} = sub { }; }

  is($obj->rpc_call('CoaxialControlIO.control', {channel=>0}), undef,
    'a Request with no mask key returns undef');
  is(scalar @sent, 0, 'and nothing was put on the wire');

  # The bootstrap channels legitimately have no key and must still go out.
  eval { $obj->rpc_call('global.login', {}, login => 1) };
  is(scalar @sent, 1, 'a Login is still sent when there is no key');
  eval { $obj->rpc_call('Security.getEncryptInfo', undef, outside => 1) };
  is(scalar @sent, 2, 'an OutsideCmd is still sent when there is no key');
  eval { $obj->rpc_call('LXSecurity.getGeneralKey', {}) };
  is(scalar @sent, 3, 'the key exchange is still sent when there is no key');
}

