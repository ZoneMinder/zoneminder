# ==========================================================================
#
# ZoneMinder Herospeed / LS Vision Control Protocol Module
# Copyright (C) 2026 ZoneMinder LLC
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301 USA
#
# ==========================================================================
#
# Herospeed (LS Vision) cameras, e.g. the L5TW. Identified by a
# "LSWebServer" Server header, an ONVIF GetDeviceInformation that reports
# Manufacturer "NONE" and Model "IPCamera", and a MAC in Herospeed's
# 2C:6F:51 OUI. The real name is in the ONVIF scope (onvif://.../name/L5TW)
# and in the vendor API's /system/device-info.
#
# PTZ, presets and imaging all come from the ONVIF parent. What ONVIF does
# not expose on these cameras is the white "supplement" floodlight that the
# deterrence feature uses, so that one function is driven through the
# vendor's REST api instead.
#
# ==========================================================================

package ZoneMinder::Control::Herospeed;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Control::ONVIF;

our @ISA = qw(ZoneMinder::Control::ONVIF);

use ZoneMinder::Logger qw(:all);
use JSON::MaybeXS qw(encode_json decode_json);
use Digest::SHA qw(sha256_hex);
use MIME::Base64 qw(encode_base64);
use POSIX qw(strftime);

use constant API_VERSION => 'v4.0.0';

# dayNightMode.ledMode. The web ui calls these Warm Light, IR Light and
# Intelligent, in that order, and ledModeList is [0,1,2].
use constant LED_WARM        => 0;   # the white floodlight
use constant LED_IR          => 1;   # infrared only, no visible light
use constant LED_INTELLIGENT => 2;   # infrared, white light on detection

# --- pure helpers, so the login maths is testable without a camera --------

# The device hands back a hex digest; the next round hashes the *bytes* that
# hex represents, not the hex text. Getting this wrong still produces a
# plausible looking digest, so it is pinned by a test.
sub hex_to_bytes {
  my ($hex) = @_;
  return '' if !defined $hex or $hex eq '';
  $hex =~ s/\A0[xX]//;
  return undef if length($hex) % 2;
  return pack('H*', $hex);
}

# Local time, no zone, exactly as the web ui formats it. It is sent as
# 'datetime' and, base64 encoded, folded into the first hash, so the two must
# be derived from the same instant.
sub login_timestamp {
  my ($epoch) = @_;
  $epoch = time() if !defined $epoch;
  return strftime('%Y-%m-%dT%H:%M:%S', localtime($epoch));
}

# round two: everything concatenated as text
sub round_two {
  my ($user, $salt, $b64_ts, $pass) = @_;
  return sha256_hex(join('', map { defined $_ ? $_ : '' } $user, $salt, $b64_ts, $pass));
}

# round three: the previous digest as raw bytes, then the challenge as text
sub round_three {
  my ($digest, $challenge) = @_;
  my $raw = hex_to_bytes($digest);
  return undef if !defined $raw;
  return sha256_hex($raw.(defined $challenge ? $challenge : ''));
}

# round four: rehash the raw bytes, n times. Skipped when the camera says
# enableIteration is false, which is the common case.
sub round_four {
  my ($iterations, $digest) = @_;
  $iterations = 0 if !defined $iterations or $iterations < 0;
  for (1 .. $iterations) {
    my $raw = hex_to_bytes($digest);
    return undef if !defined $raw;
    $digest = sha256_hex($raw);
  }
  return $digest;
}

# The whole handshake as one pure function, so a captured exchange can be
# replayed in a test.
sub login_password {
  my (%a) = @_;
  my $b64 = encode_base64($a{timestamp}, '');
  my $d = round_two($a{user}, $a{salt}, $b64, $a{pass});
  $d = round_three($d, $a{challenge});
  return undef if !defined $d;
  return $a{enable_iteration} ? round_four($a{iterations}, $d) : $d;
}

# Whole-section merge. A partial write makes the camera drop the fields it
# was not sent, so set_led_mode reads the section, changes one key and sends
# all of it back.
sub with_led_mode {
  my ($section, $mode) = @_;
  my %copy = %{$section || {}};
  $copy{ledMode} = $mode + 0;
  return \%copy;
}

sub led_mode_for_light {
  my ($on) = @_;
  return $on ? LED_WARM : LED_IR;
}

# --- transport ------------------------------------------------------------

sub open {
  my $self = shift;
  # ONVIF's open does loadMonitor, credentials and the ptz profile.
  return undef if !$self->SUPER::open();
  $self->{api_cookie} = undef;
  $self->api_login();
  return 1;
}

sub close {
  my $self = shift;
  $self->{api_cookie} = undef;
  $self->SUPER::close() if $self->SUPER::can('close');
  $self->{state} = 'closed';
}

sub api {
  my ($self, $path, $action, $data) = @_;
  $action = 'get' if !defined $action;

  my $url = 'http://'.$self->{host}.'/api'.$path;
  my @headers = (
    'Api-Version'  => API_VERSION,
    'Content-Type' => 'application/json; charset=utf-8',
  );
  # The session cookie comes back in the login response body rather than a
  # Set-Cookie header, so it is carried by hand.
  push @headers, 'Cookie' => $self->{api_cookie} if $self->{api_cookie};

  my $res = eval {
    $self->{ua}->post($url, @headers,
      Content => encode_json({action => $action, data => $data}));
  };
  if (!$res) {
    Error("Herospeed: $path request failed: ".($@ // 'no response'));
    return undef;
  }
  if (!$res->is_success) {
    Error('Herospeed: HTTP '.$res->status_line." for $path");
    return undef;
  }
  my $out = eval { decode_json($res->decoded_content) };
  if (!$out) {
    Error("Herospeed: could not decode the reply to $path");
    return undef;
  }
  return $out;
}

sub api_login {
  my $self = shift;

  my $cap = $self->api('/session/login-capabilities', 'get',
    {username => $self->{username}});
  if (!$cap or ($cap->{code} // -1) != 0 or !$cap->{data}) {
    Error('Herospeed: login-capabilities refused'
      .($cap ? ' (code '.($cap->{code} // '?').')' : ''));
    return undef;
  }
  my $d = $cap->{data};
  my $p = $d->{param} || {};

  my $ts = login_timestamp();
  my $password = login_password(
    user             => $self->{username},
    pass             => $self->{password},
    salt             => $p->{salt},
    challenge        => $p->{challenge},
    timestamp        => $ts,
    enable_iteration => $p->{enableIteration},
    iterations       => $p->{iterations},
  );
  if (!defined $password) {
    Error('Herospeed: could not compute the login digest');
    return undef;
  }

  my $r = $self->api('/session/login', 'set', {
    username            => $self->{username},
    loginEncryptionType => ($d->{encryptionType} || ['sha256-1'])->[0],
    password            => $password,
    sessionID           => $d->{sessionID},
    datetime            => $ts,
  });
  if (!$r or ($r->{code} // -1) != 0 or !$r->{data} or !$r->{data}{cookie}) {
    Error('Herospeed: login refused'
      .($r ? ' (code '.($r->{code} // '?').')' : ''));
    return undef;
  }
  $self->{api_cookie} = $r->{data}{cookie};
  Debug(1, 'Herospeed: logged in to '.$self->{host});
  return 1;
}

# --- the light ------------------------------------------------------------

sub day_night_section {
  my $self = shift;
  my $r = $self->api('/image/image-param', 'get', undef);
  # code 101 is "no session"; the camera expires them, so re-login once.
  if ($r and ($r->{code} // 0) == 101) {
    Debug(1, 'Herospeed: session expired, logging in again');
    return undef if !$self->api_login();
    $r = $self->api('/image/image-param', 'get', undef);
  }
  if (!$r or ($r->{code} // -1) != 0 or !$r->{data} or !$r->{data}{dayNightMode}) {
    Error('Herospeed: could not read the day/night settings');
    return undef;
  }
  return $r->{data}{dayNightMode};
}

sub set_led_mode {
  my ($self, $mode) = @_;
  my $section = $self->day_night_section();
  return undef if !$section;

  if (($section->{ledMode} // -1) == $mode) {
    Debug(1, "Herospeed: ledMode is already $mode, nothing to do");
    return 1;
  }
  my $r = $self->api('/image/image-param', 'set',
    {dayNightMode => with_led_mode($section, $mode)});
  if (!$r or ($r->{code} // -1) != 0) {
    Error("Herospeed: setting ledMode to $mode failed"
      .($r ? ' (code '.($r->{code} // '?').')' : ''));
    return undef;
  }
  Debug(1, "Herospeed: ledMode set to $mode");
  return 1;
}

# The supplement light only physically engages while the camera is in night
# mode; in day mode the setting is stored but nothing lights up.
sub lightOn  { my $self = shift; return $self->set_led_mode(LED_WARM); }
sub lightOff { my $self = shift; return $self->set_led_mode(LED_IR); }

sub lightStatus {
  my $self = shift;
  my $section = $self->day_night_section();
  return {WhiteLight => undef} if !$section;
  my $mode = $section->{ledMode};
  return {WhiteLight => (defined $mode ? ($mode == LED_WARM ? 'On' : 'Off') : undef)};
}

1;
__END__

=head1 NAME

ZoneMinder::Control::Herospeed - Herospeed / LS Vision camera control

=head1 DESCRIPTION

PTZ, presets and imaging are inherited from ZoneMinder::Control::ONVIF, which
these cameras implement well. The white "supplement" floodlight is not exposed
over ONVIF at all - GetRelayOutputs and the PTZ auxiliary Wiper command are
both accepted and both do nothing - so it is driven through the vendor REST
api at /api/image/image-param instead, by switching dayNightMode.ledMode
between Warm Light and IR Light.

Note that the supplement light only engages while the camera is in night mode.
Setting it during the day is stored and read back correctly but produces no
visible light.

=cut
