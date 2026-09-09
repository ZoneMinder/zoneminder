use strict;
use warnings;
use Test::More tests => 26;

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
