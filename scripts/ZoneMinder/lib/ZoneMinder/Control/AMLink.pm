# ==========================================================================
#
# ZoneMinder AMLink Control Protocol Module
# Copyright (C) 2001-2026 ZoneMinder Inc
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
#
# ==========================================================================
#
# AMLINK AL5M-T5171EW and siblings.
#
# These speak Dahua's JSON-RPC vocabulary -- CoaxialControlIO for the white
# light, magicBox, configManager -- so every command lives in Dahua_RPC and
# this module only replaces the transport underneath it.
#
# That transport is unusual enough to be worth stating plainly, because none
# of it is discoverable by probing:
#
#   * The endpoint is /Onvif/device_service, with a capital O.  The lowercase
#     /onvif/device_service is the genuine ONVIF SOAP endpoint; the capitalised
#     spelling is a completely separate vendor JSON-RPC tunnel that happens to
#     share the path.  /RPC2, /RPC3 and every /cgi-bin/* return 404 here.
#   * Requests are wrapped as <body><cmdType>T</cmdType><cmd>P</cmd></body>.
#     Omitting cmdType makes the camera drop the connection.
#   * P is base64 of the JSON-RPC object, and once a session key has been
#     negotiated it is additionally XOR-masked with that key.  Masking is not
#     optional: an unmasked Request is refused even on a fresh session.
#   * Getting the mask key needs a three-step bootstrap -- log in, ask for the
#     device's RSA public key over the unauthenticated OutsideCmd channel, then
#     hand it an AES key encrypted under that RSA key and get the mask key back
#     encrypted under the AES key.
#
# Configuration (Monitor -> Control tab):
#   Control Type    : AMLink
#   Control Address : [user:pass@]host  -- optional, Path plus the monitor's
#                     User/Pass is used when it is empty
#
package ZoneMinder::Control::AMLink;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Control::Dahua_RPC;
require LWP::UserAgent;

use JSON::MaybeXS qw(encode_json decode_json);
require JSON::MaybeXS;
use MIME::Base64 qw(encode_base64 decode_base64);
use Crypt::PK::RSA;
use Crypt::Mode::CBC;

our @ISA = qw(ZoneMinder::Control::Dahua_RPC);

use ZoneMinder::Logger qw(:all);

# The device advertises RPAC-256, meaning AES-CBC with this literal ASCII IV.
# It is sixteen '0' characters, NOT sixteen zero bytes.
use constant AES_IV => '0000000000000000';
# Salt length for the "V2.0" security baseline these cameras report. A 16-char
# salt is accepted by the AES step but rejected by getGeneralKey.
use constant SALT_LEN => 32;

# --- Pure helpers ---------------------------------------------------------
# Split out so the wire format can be tested without a camera (see
# t/amlink_transport.t).

# The mask key is the byte values of the key string, applied cyclically.
sub key_from_string {
  my ($str) = @_;
  return undef if !defined $str or $str eq '';
  return [ map { ord } split //, $str ];
}

# XOR each byte against the key, cycling. Symmetric: masking twice is a no-op,
# which is why the same routine serves requests and responses.
sub mask_data {
  my ($key, $data) = @_;
  return $data if !$key or !@$key;
  my $out = '';
  for my $i (0 .. length($data) - 1) {
    $out .= chr($key->[$i % scalar(@$key)] ^ ord(substr($data, $i, 1)));
  }
  return $out;
}

sub build_envelope {
  my ($cmd_type, $payload) = @_;
  return '<body><cmdType>'.$cmd_type.'</cmdType><cmd>'.$payload.'</cmd></body>';
}

# Replies wrap the payload in newlines, which are not part of the base64.
sub extract_cmd {
  my ($xml) = @_;
  return undef if !defined $xml;
  return $xml =~ m{<cmd>[\r\n]*(.*?)[\r\n]*</cmd>}s ? $1 : undef;
}

# Which channel a method travels on. Login and the key exchange predate the
# session key and so must not be masked; OutsideCmd is the pre-auth channel
# that serves the RSA public key.
sub cmd_type_for {
  my ($method, %opts) = @_;
  return 'Login'         if $opts{login};
  return 'OutsideCmd'    if $opts{outside};
  return 'GetGeneralKey' if $method eq 'LXSecurity.getGeneralKey';
  return 'Request';
}

# The device wants a decimal-digit string, not arbitrary bytes: it is used
# directly as the AES key, so its characters are the key material.
sub numeric_salt {
  my ($len) = @_;
  my $salt = '';
  while (length($salt) < $len) {
    my $v = int(rand(4294967296));
    $salt .= $v if $v;
  }
  return substr($salt, 0, $len);
}

sub parse_rsa_pub {
  my ($pub) = @_;
  return undef if !defined $pub;
  my ($n) = $pub =~ /N:([0-9A-Fa-f]+)/;
  my ($e) = $pub =~ /E:([0-9A-Fa-f]+)/;
  return undef if !$n or !$e;
  return { N => $n, e => $e };
}

# --- Transport ------------------------------------------------------------

sub open {
  my $self = shift;
  $self->loadMonitor();

  $self->{ua} = LWP::UserAgent->new(timeout => 10, cookie_jar => {});
  $self->{ua}->agent('ZoneMinder Control Agent/'.ZoneMinder::Base::ZM_VERSION());

  $self->guess_credentials() if !$self->{host};
  if (!$self->{host}) {
    Error('AMLink: no host could be determined from ControlAddress or Path');
    $self->{state} = 'closed';
    return undef;
  }

  # As in Dahua_RPC: parse_Path() yields the RTSP port, which is not where the
  # vendor RPC lives.
  my $port = ($self->{port} && $self->{port} != 554) ? $self->{port} : 80;
  $self->{RPCBase} = 'http://'.$self->{host}.':'.$port.'/Onvif/device_service';
  $self->{rpc_id} = 0;
  $self->{mask_key} = undef;

  if ($self->login()) {
    $self->{state} = 'open';
    return 1;
  }
  $self->{state} = 'closed';
  return undef;
}

sub rpc_call {
  my ($self, $method, $params, %opts) = @_;
  $self->{rpc_id} = ($self->{rpc_id} || 0) + 1;

  my $req = { method => $method, id => $self->{rpc_id}, params => $params };
  $req->{session} = $self->{session} if defined $self->{session};
  $req->{object}  = $opts{object}    if defined $opts{object};

  my $cmd_type = cmd_type_for($method, %opts);
  # Only Request traffic is masked. The bootstrap channels run before a key
  # exists, and masking them makes the camera drop the connection.
  my $key = ($cmd_type eq 'Request') ? $self->{mask_key} : undef;

  # Without a key a Request cannot be sent at all. Several callers in Dahua_RPC
  # re-login on error and carry on regardless of whether it worked, so this can
  # be reached with the key cleared by a failed login. Sending unmasked would
  # get a masked reply back and fail with a confusing JSON decode error rather
  # than saying what is actually wrong.
  if ($cmd_type eq 'Request' and !$key) {
    Error("AMLink: not logged in, refusing to send $method");
    return undef;
  }

  my $payload = encode_base64(encode_json($req), '');
  $payload = mask_data($key, $payload) if $key;

  my $res = eval {
    $self->{ua}->post($self->{RPCBase}, 'Content-Type' => 'application/json',
                      Content => build_envelope($cmd_type, $payload));
  };
  if (!$res) {
    Error("AMLink: request failed for $method: $@");
    return undef;
  }
  if (!$res->is_success) {
    Error('AMLink: HTTP '.$res->status_line." for $method");
    return undef;
  }

  my $cmd = extract_cmd($res->decoded_content(charset => 'none'));
  if (!defined $cmd) {
    Error("AMLink: no <cmd> element in the reply to $method");
    return undef;
  }
  $cmd = mask_data($key, $cmd) if $key;

  my $data = eval { decode_json(decode_base64($cmd)) };
  if ($@ or !$data) {
    Error("AMLink: failed to decode the reply to $method: $@");
    return undef;
  }
  return $data;
}

# Ask the device for its RSA public key. This rides the OutsideCmd channel,
# which is the only one that works before a mask key exists.
sub fetch_encrypt_info {
  my $self = shift;
  my $r = $self->rpc_call('Security.getEncryptInfo', undef, outside => 1);
  return undef if !$r or !$r->{params};
  return $r->{params};
}

# Exchange an AES key for the session mask key.
sub negotiate_mask_key {
  my $self = shift;

  my $info = $self->fetch_encrypt_info();
  if (!$info or !$info->{pub}) {
    Error('AMLink: could not read the device RSA public key');
    return undef;
  }
  my $pub = parse_rsa_pub($info->{pub});
  if (!$pub) {
    Error('AMLink: could not parse the device RSA public key');
    return undef;
  }

  my $salt = numeric_salt(SALT_LEN);
  my $cbc = Crypt::Mode::CBC->new('AES', 0);   # 0 = no padding; we zero-pad
  my $plain = 'getGeneralKey';
  $plain .= "\0" x ((16 - length($plain) % 16) % 16);
  my $content = encode_base64($cbc->encrypt($plain, $salt, AES_IV), '');

  my $rsa = eval { Crypt::PK::RSA->new({ N => $pub->{N}, e => $pub->{e} }) };
  if (!$rsa) {
    Error("AMLink: could not build the RSA key: $@");
    return undef;
  }
  my $enc_salt = uc(unpack('H*', $rsa->encrypt($salt, 'v1.5')));

  my $r = $self->rpc_call('LXSecurity.getGeneralKey',
    { cipher => 'RPAC-256', salt => $enc_salt, content => $content });
  if (!$r or !$r->{params} or !$r->{params}{content}) {
    Error('AMLink: getGeneralKey returned no content');
    return undef;
  }

  my $blob = eval { $cbc->decrypt(decode_base64($r->{params}{content}), $salt, AES_IV) };
  if (!defined $blob) {
    Error("AMLink: could not decrypt the general key: $@");
    return undef;
  }
  $blob =~ s/\0+\z//;
  my $inner = eval { decode_json($blob) };
  my $key = ($inner and $inner->{params} and $inner->{params}{table})
          ? $inner->{params}{table}{key} : undef;
  if (!$key) {
    Error('AMLink: general key missing from the decrypted response');
    return undef;
  }

  $self->{mask_key} = key_from_string($key);
  Debug('AMLink: negotiated a '.scalar(@{$self->{mask_key}}).'-byte mask key');
  return 1;
}

# Release the session. The camera counts connections and reclaims them slowly,
# so a daemon that logs in repeatedly without this eventually gets told there
# are "too many connections" and cannot log in at all.
sub logout {
  my $self = shift;
  return if !defined $self->{session} or !$self->{mask_key};
  $self->rpc_call('global.logout');
  $self->{session} = undef;
  $self->{mask_key} = undef;
}

sub close {
  my $self = shift;
  $self->logout();
  $self->{state} = 'closed';
}

sub login {
  my $self = shift;
  # Give back the previous session before asking for another one. zmcontrol
  # keeps one object for the life of the daemon and Dahua_RPC re-logins when the
  # camera times the session out, so without this each re-login leaks a slot.
  $self->logout();
  $self->{rpc_id} = 0;
  $self->{session} = undef;
  $self->{mask_key} = undef;
  $self->{ptz_object} = undef;

  my $r = $self->rpc_call('global.login',
    { userName => $self->{username}, password => '', clientType => 'Web5.0' },
    login => 1);
  if (!$r or !$r->{params} or !$r->{params}{realm} or !$r->{session}) {
    Error('AMLink: login stage 1 (challenge) failed');
    return undef;
  }
  $self->{session} = $r->{session};

  my $pw = ZoneMinder::Control::Dahua_RPC::compute_login_hash(
    $self->{username}, $r->{params}{realm}, $r->{params}{random}, $self->{password});
  my $enc = $r->{params}{encryption} || 'Default';

  $r = $self->rpc_call('global.login',
    { userName => $self->{username}, password => $pw, clientType => 'Web5.0',
      authorityType => $enc, passwordType => $enc,
      realm => $r->{params}{realm}, random => $r->{params}{random} },
    login => 1);
  if (!$r or !$r->{result}) {
    my $msg = ($r and $r->{error}) ? $r->{error}{message} : 'no/!result response';
    if ($msg =~ /too many connections/i) {
      # Sessions the camera has not reclaimed yet. Reached by anything that logs
      # in without logging out; it clears on its own once they time out.
      Error('AMLink: login refused, the camera has too many open connections. '
            .'They are released on logout or when the camera times them out.');
    } else {
      Error("AMLink: login failed: $msg");
    }
    return undef;
  }

  # Without the mask key every subsequent call would be refused, so a failure
  # here is a failed login even though the credentials were accepted.
  return undef if !$self->negotiate_mask_key();

  Debug('AMLink: logged in to '.$self->{host});
  return 1;
}

# ==========================================================================
#
# Time and NTP.
#
# The camera keeps NTP in the configManager "NTP" section. Only Address,
# Enable and UpdatePeriod are touched: TimeZone/TimeZoneDesc are left alone
# because the camera's own clock display is already correct and the index is
# not a standard one (26 is "Middletime" here, while the factory default is 25
# "Easterntime").
#
# UpdatePeriod is in minutes. 1 is accepted - verified by writing it and
# reading it back - so a camera can be kept within a minute of the server.
#
# ==========================================================================

# Whole-section merge, so a write echoes every field the camera gave us back
# and cannot silently drop one. Pure, so the merge and the change detection are
# testable without a camera.
sub ntp_table {
  my ($current, %override) = @_;
  my %table = %{$current || {}};
  $table{$_} = $override{$_} for keys %override;
  return \%table;
}

# True when the wanted settings differ from what the camera already holds.
sub ntp_needs_write {
  my ($current, $wanted) = @_;
  return 1 if !$current;
  foreach my $field (keys %$wanted) {
    return 1 if !exists $current->{$field};
    # JSON booleans and numbers both stringify usefully for this comparison.
    return 1 if "$current->{$field}" ne "$wanted->{$field}";
  }
  return 0;
}

sub get_ntp {
  my $self = shift;
  my $r = $self->rpc_call('configManager.getConfig', { name => 'NTP' });
  return ($r and $r->{params}) ? $r->{params}{table} : undef;
}

sub set_time {
  my ($self, %opts) = @_;

  my $ntp_server = $opts{ntp_server};
  if (!defined($ntp_server) or $ntp_server eq '') {
    Error('AMLink: set_time needs an ntp_server');
    return undef;
  }
  # Minutes. The camera accepts 1, which is as often as it will go.
  my $period = defined($opts{update_period}) ? int($opts{update_period}) : 1;
  if ($period < 1) {
    Error('AMLink: update_period must be at least 1 minute');
    return undef;
  }

  my $current = $self->get_ntp();
  if (!$current) {
    Error('AMLink: could not read the current NTP settings');
    return undef;
  }

  my %wanted = (
    Address      => $ntp_server,
    Enable       => JSON::MaybeXS::true,
    UpdatePeriod => $period,
  );
  $wanted{Port} = int($opts{port}) if defined $opts{port};

  # The camera holds this in flash and set_time is meant to be safe to re-run,
  # so don't spend an erase cycle re-writing settings that already match.
  if (!ntp_needs_write($current, \%wanted)) {
    Debug('AMLink: NTP settings already as wanted, not writing them');
    return 1;
  }

  my $table = ntp_table($current, %wanted);
  my $r = $self->rpc_call('configManager.setConfig', { name => 'NTP', table => $table });
  if (!$r or !$r->{result}) {
    Error('AMLink: failed to write the NTP settings');
    return undef;
  }
  Debug("AMLink: NTP set to $ntp_server every $period minute(s)");
  return 1;
}

1;
__END__

=head1 NAME

ZoneMinder::Control::AMLink - AMLINK AL5M white light control

=head1 DESCRIPTION

Drives the white light on AMLINK cameras (verified on AL5M-T5171EW, firmware
1.00.U800000.R). The camera speaks Dahua's JSON-RPC vocabulary over a vendor
transport at C</Onvif/device_service>, so this module subclasses
L<ZoneMinder::Control::Dahua_RPC> and replaces only C<open>, C<rpc_call> and
C<login>; C<lightOn>, C<lightOff>, C<lightStatus> and C<reboot> are inherited
unchanged.

Note that C<IO =E<gt> 1> turns the light on and C<IO =E<gt> 2> turns it off,
matching Dahua_RPC. The camera's own web UI is a toggle and does not reveal
which is which; C<CoaxialControlIO.getStatus> does, and a floodlight cannot be
verified optically in daylight.

=cut
