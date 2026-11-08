use strict;
use warnings;
use MIME::Base64;
use Test::More tests => 79;

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


# --- log messages survive the logger ----------------------------------------
# ZoneMinder::Logger keeps only what precedes the first newline in a message.
# Perl errors end in one, and "$@ (extra diagnosis)" therefore threw the
# diagnosis away silently - the failure looked like it had no instrumentation
# at all.

my $log_safe = $P->can('log_safe');

is($log_safe->("malformed JSON at AMLink.pm line 221.\n"),
  'malformed JSON at AMLink.pm line 221.',
  'a trailing newline is removed');
unlike($log_safe->("first line\nsecond line\n"), qr/\n/,
  'an embedded newline cannot truncate the message');
is($log_safe->("first line\nsecond line"), 'first line | second line',
  'and both halves are kept, separated visibly');
is($log_safe->("a\r\nb"), 'a | b', 'CRLF counts as one break, not two');
is($log_safe->('no newlines here'), 'no newlines here', 'an ordinary message is untouched');
is($log_safe->(undef), '', 'undef yields an empty string rather than a warning');
is($log_safe->(''), '', 'an empty message stays empty');

# --- recovering from an undecodable reply ------------------------------------
# An undecodable reply means we and the camera disagree about the session.
# Dahua_RPC only re-logs-in when it gets a *parseable* error back, so before
# this the session stayed poisoned and every later command failed the same way,
# leaving a light switched on by an alarm on until something forced a re-login.

{
  package FakeRes;
  sub new { my ($c, $body, $ok) = @_; bless {body => $body, ok => (defined $ok ? $ok : 1)}, $c }
  sub is_success { $_[0]{ok} }
  sub status_line { '500 Boom' }
  sub decoded_content { $_[0]{body} }
}
{
  package FakeUA2;
  sub new { bless {queue => [], sent => []}, shift }
  sub agent { }
  sub post {
    my $self = shift;
    push @{$self->{sent}}, \@_;
    my $r = shift @{$self->{queue}};
    return defined $r ? $r : FakeRes->new('<body><cmd>####</cmd></body>');
  }
}

my $tkey = $key_from_string->('KEY');
# A reply the camera would send: json -> base64 -> masked with the session key.
sub good_reply {
  my $payload = $mask_data->($tkey, MIME::Base64::encode_base64('{"result":1,"id":1}', ''));
  return FakeRes->new('<body><cmd>'.$payload.'</cmd></body>');
}
# '####' unmasks to bytes that are not base64, so nothing valid comes out.
sub bad_reply { FakeRes->new('<body><cmd>####</cmd></body>') }

my $logins;
sub fresh_obj {
  my %extra = @_;
  my $ua = FakeUA2->new;
  my $o = bless {
    session => 's1', mask_key => $tkey, rpc_id => 0, host => 'h',
    RPCBase => 'http://h/Onvif/device_service', ua => $ua, %extra,
  }, $P;
  return ($o, $ua);
}

$logins = 0;
my $login_ok = 1;
{
  no strict 'refs'; no warnings 'redefine';
  *{$P.'::login'} = sub {
    my $self = shift;
    $logins++;
    return 0 if !$login_ok;
    $self->{session} = 's2';
    $self->{mask_key} = $tkey;
    return 1;
  };
}

# 1. a decode failure re-establishes the session and retries once
{
  my ($o, $ua) = fresh_obj();
  @{$ua->{queue}} = (bad_reply(), good_reply());
  $logins = 0;
  my $r = $o->rpc_call('CoaxialControlIO.control', {channel => 0});
  is($logins, 1, 'an undecodable reply triggers exactly one re-login');
  is(scalar @{$ua->{sent}}, 2, 'and the command is sent again on the new session');
  ok(defined $r, 'the retry result is returned to the caller');
  is($o->{session}, 's2', 'the object is left holding the new session');
}

# 2. one retry only - it does not keep going
{
  my ($o, $ua) = fresh_obj();
  @{$ua->{queue}} = (bad_reply(), bad_reply(), good_reply());
  $logins = 0;
  my $r = $o->rpc_call('CoaxialControlIO.control', {channel => 0});
  is($logins, 1, 'a retry that also fails does not start another recovery');
  is(scalar @{$ua->{sent}}, 2, 'exactly two attempts were made');
  is($r, undef, 'and the caller is told it failed');
}

# 3. a failed re-login is reported rather than retried blindly
{
  my ($o, $ua) = fresh_obj();
  @{$ua->{queue}} = (bad_reply(), good_reply());
  $logins = 0; $login_ok = 0;
  my $r = $o->rpc_call('CoaxialControlIO.control', {channel => 0});
  is($r, undef, 'a failed re-login yields undef');
  is(scalar @{$ua->{sent}}, 1, 'and the command is not resent');
  $login_ok = 1;
}

# 4. only an undecodable reply counts - an HTTP error is not a lost session
{
  my ($o, $ua) = fresh_obj();
  @{$ua->{queue}} = (FakeRes->new('', 0), good_reply());
  $logins = 0;
  $o->rpc_call('CoaxialControlIO.control', {channel => 0});
  is($logins, 0, 'an HTTP error does not trigger a re-login');
}

# 5. the bootstrap channels must never recover: login() issues them
{
  my ($o, $ua) = fresh_obj();
  @{$ua->{queue}} = (bad_reply(), good_reply());
  $logins = 0;
  $o->rpc_call('global.login', {}, login => 1);
  is($logins, 0, 'a Login that fails to decode does not call login again');

  ($o, $ua) = fresh_obj();
  @{$ua->{queue}} = (bad_reply(), good_reply());
  $logins = 0;
  $o->rpc_call('LXSecurity.getGeneralKey', {});
  is($logins, 0, 'nor does the key exchange');
}

# 6. no recursion: login() calls logout(), which is a Request on the dead
# session. Without the guard its own decode failure starts another recovery.
{
  my ($o, $ua) = fresh_obj();
  @{$ua->{queue}} = (bad_reply(), bad_reply(), bad_reply(), good_reply());
  $logins = 0;
  {
    no strict 'refs'; no warnings 'redefine';
    local *{$P.'::login'} = sub {
      my $self = shift;
      $logins++;
      die "runaway recursion: login called $logins times\n" if $logins > 3;
      $self->rpc_call('global.logout');      # the real login() does this
      $self->{session} = 's2'; $self->{mask_key} = $tkey;
      return 1;
    };
    my $r = eval { $o->rpc_call('CoaxialControlIO.control', {channel => 0}) };
    is($@, '', 'a logout that fails inside login does not recurse');
    is($logins, 1, 'login runs once, not once per nested failure');
  }
}

# 7. backoff stops a camera that always answers unreadably from being hammered
{
  my ($o, $ua) = fresh_obj();
  @{$ua->{queue}} = (bad_reply(), bad_reply(), bad_reply(), bad_reply());
  $logins = 0;
  $o->rpc_call('CoaxialControlIO.control', {channel => 0});
  is($logins, 1, 'the first failure recovers');
  $o->rpc_call('CoaxialControlIO.control', {channel => 0});
  is($logins, 1, 'a second failure straight after does not log in again');
}

# --- the plain-text session-expired reply ------------------------------------
# Seen on the wire 2026-09-11: the camera answers an expired session with a
# bare printable string where a masked base64 payload belongs, so it can never
# parse. It used to surface as "malformed JSON string ... at character offset
# 0", which says nothing about the actual problem.

my $session_error = $P->can('session_error');

ok($session_error->('Invalid session in request'), 'the exact reply seen on the wire is recognised');
ok($session_error->('invalid session'), 'matching is case insensitive');
ok($session_error->('Invalid  session in request'), 'and tolerant of extra spacing');
ok(!$session_error->(''), 'an empty payload is not a session error');
ok(!$session_error->(undef), 'nor is undef');
# A real masked payload is binary, and a real base64 payload is printable but
# says nothing about sessions. Neither may be mistaken for this.
ok(!$session_error->('eyJyZXN1bHQiOjEsImlkIjoxfQ=='), 'ordinary base64 is not a session error');
ok(!$session_error->("\x01\x02\xff\xfe"), 'a masked binary payload is not a session error');

# End to end: an expired session recovers, and is not reported as a decode
# failure.
{
  my ($o, $ua) = fresh_obj();
  @{$ua->{queue}} = (FakeRes->new('<body><cmd>Invalid session in request</cmd></body>'), good_reply());
  $logins = 0;
  my $r = $o->rpc_call('CoaxialControlIO.control', {channel => 0});
  is($logins, 1, 'an expired session triggers a re-login');
  is(scalar @{$ua->{sent}}, 2, 'and the command is sent again');
  ok(defined $r, 'the caller gets the result rather than undef');
  is($o->{last_failure}, undef, 'the retry cleared the failure state');
}

# The bootstrap channels must not recover from it either.
{
  my ($o, $ua) = fresh_obj();
  @{$ua->{queue}} = (FakeRes->new('<body><cmd>Invalid session in request</cmd></body>'), good_reply());
  $logins = 0;
  $o->rpc_call('global.login', {}, login => 1);
  is($logins, 0, 'a session error on the Login channel does not re-login');
}

# Backoff applies here too.
{
  my ($o, $ua) = fresh_obj();
  my $expired = sub { FakeRes->new('<body><cmd>Invalid session in request</cmd></body>') };
  @{$ua->{queue}} = ($expired->(), $expired->(), $expired->(), $expired->());
  $logins = 0;
  $o->rpc_call('CoaxialControlIO.control', {channel => 0});
  $o->rpc_call('CoaxialControlIO.control', {channel => 0});
  is($logins, 1, 'a second expiry straight after does not log in again');
}
