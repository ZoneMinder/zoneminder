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

package ZoneMinder::Control::Herospeed;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Control;

our @ISA = qw(ZoneMinder::Control);

use ZoneMinder::Logger qw(:all);
use JSON::MaybeXS qw(encode_json decode_json);
use Digest::SHA qw(sha256_hex);
use MIME::Base64 qw(encode_base64);
use POSIX qw(strftime);
use Time::HiRes qw(usleep);
use LWP::UserAgent;

use constant API_VERSION => 'v4.0.0';

# --- optType values, lifted from the web ui's PTZ_CONTROL_CMD_* constants --
use constant {
  CMD_STOP        => 0,
  CMD_UP          => 10,
  CMD_DOWN        => 11,
  CMD_LEFT        => 12,
  CMD_RIGHT       => 13,
  CMD_LEFTUP      => 14,
  CMD_LEFTDOWN    => 15,
  CMD_RIGHTUP     => 16,
  CMD_RIGHTDOWN   => 17,
  CMD_ZOOM_IN     => 20,
  CMD_ZOOM_OUT    => 21,
  CMD_FOCUS_NEAR  => 22,
  CMD_FOCUS_FAR   => 23,
  CMD_IRIS_OPEN   => 24,
  CMD_IRIS_CLOSE  => 25,
  CMD_ZOOM_STOP   => 26,
  CMD_FOCUS_STOP  => 27,
  CMD_ONEKEYFOCUS => 31,
  CMD_INITLENS    => 32,
  CMD_AUTOPAN     => 34,
  CMD_MOVEPOINT   => 40,
  CMD_CRUISE_START=> 41,
  CMD_PRESET_SET  => 44,
  CMD_PRESET_DEL  => 45,
  CMD_CRUISE_STOP => 46,
};

# dayNightMode.ledMode. The web ui labels these Warm Light, IR Light and
# Intelligent, in that order; ledModeList is [0,1,2].
use constant {
  LED_WARM        => 0,   # the white floodlight
  LED_IR          => 1,   # infrared only, no visible light
  LED_INTELLIGENT => 2,   # infrared, white light on detection
};

# The device does not advertise a speed range anywhere in its api or its ui,
# so this is the conservative window every optType has been observed to
# accept. ZM's own Min/MaxTiltSpeed still bound what reaches us.
use constant { SPEED_MIN => 1, SPEED_MAX => 8, SPEED_DEFAULT => 4 };

# ==========================================================================
# Pure helpers. Everything here is testable without a camera, which matters
# most for the login maths: a wrong digest is still a plausible looking
# digest, and the only symptom is a refused login.
# ==========================================================================

# Each round hashes the BYTES the previous hex digest names, not its text.
sub hex_to_bytes {
  my ($hex) = @_;
  return '' if !defined $hex or $hex eq '';
  $hex =~ s/\A0[xX]//;
  return undef if length($hex) % 2 or $hex =~ /[^0-9a-fA-F]/;
  return pack('H*', $hex);
}

# Local time, no zone, exactly as the ui formats it. The same instant is both
# hashed (base64 encoded) and sent as 'datetime', so they must agree.
sub login_timestamp {
  my ($epoch) = @_;
  $epoch = time() if !defined $epoch;
  return strftime('%Y-%m-%dT%H:%M:%S', localtime($epoch));
}

sub round_two {
  my ($user, $salt, $b64_ts, $pass) = @_;
  return sha256_hex(join('', map { defined $_ ? $_ : '' } $user, $salt, $b64_ts, $pass));
}

sub round_three {
  my ($digest, $challenge) = @_;
  my $raw = hex_to_bytes($digest);
  return undef if !defined $raw;
  return sha256_hex($raw.(defined $challenge ? $challenge : ''));
}

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

sub login_password {
  my (%a) = @_;
  my $d = round_two($a{user}, $a{salt}, encode_base64($a{timestamp} // '', ''), $a{pass});
  $d = round_three($d, $a{challenge});
  return undef if !defined $d;
  return $a{enable_iteration} ? round_four($a{iterations}, $d) : $d;
}

sub clamp_speed {
  my ($speed) = @_;
  return SPEED_DEFAULT if !defined $speed or $speed !~ /\A-?\d+\z/;
  return SPEED_MIN if $speed < SPEED_MIN;
  return SPEED_MAX if $speed > SPEED_MAX;
  return $speed + 0;
}

# A preset index the camera will accept. Index 0 is not a preset, and the ui
# never offers one above 255.
sub valid_preset {
  my ($idx) = @_;
  return 0 if !defined $idx or $idx !~ /\A\d+\z/;
  return ($idx >= 1 and $idx <= 255) ? 1 : 0;
}

# Whole-section merge: a partial write makes the camera drop whatever it was
# not sent, so a change has to echo every field back.
sub with_led_mode {
  my ($section, $mode) = @_;
  my %copy = %{$section || {}};
  $copy{ledMode} = $mode + 0;
  return \%copy;
}

# --- time configuration ---------------------------------------------------
#
# The timezone is an index into the vendor's own list, which starts at
# GMT-12:00 and steps every 30 minutes: GMT0 is -12:00, GMT24 is +00:00,
# GMT12 is "(GMT-06:00) Central Time (US&Canada)". Deriving it from an offset
# beats hardcoding a number nobody can check.
sub tz_index {
  my ($offset_hours) = @_;
  return undef if !defined $offset_hours;
  return undef if $offset_hours !~ /\A-?\d+(?:\.5)?\z/;
  return undef if $offset_hours < -12 or $offset_hours > 14;
  my $index = ($offset_hours + 12) * 2;
  return undef if $index != int($index);
  return int($index);
}

# The inverse, for logging what an index actually means.
sub tz_offset {
  my ($index) = @_;
  return undef if !defined $index or $index !~ /\A\d+\z/ or $index > 52;
  return $index / 2 - 12;
}

# Merge overrides into a section read from the camera. A partial write makes
# it drop whatever it was not sent, so a change has to carry everything back.
sub time_table {
  my ($current, %override) = @_;
  my %table = %{$current || {}};
  $table{$_} = $override{$_} for keys %override;
  return \%table;
}

# These live in flash, and set_time is meant to be safe to re-run, so only
# write when something actually differs. Compared as strings: the camera
# returns numbers and JSON booleans interchangeably across firmwares.
sub time_needs_write {
  my ($current, $wanted) = @_;
  return 1 if !$current;
  foreach my $field (keys %$wanted) {
    return 1 if !exists $current->{$field};
    my ($a, $b) = ($current->{$field}, $wanted->{$field});
    # Nested sections (the dst start/end rules) compare field by field.
    if (ref $b eq 'HASH') {
      return 1 if ref $a ne 'HASH';
      return 1 if time_needs_write($a, $b);
      next;
    }
    return 1 if "$a" ne "$b";
  }
  return 0;
}

# The United States rule since 2007: second Sunday in March to first Sunday
# in November, both at 02:00 local, one hour forward. weekDay 0 is Sunday and
# weekNumber counts from 1. Passing week based rules makes the camera set
# dstType itself, so it is deliberately not sent.
sub us_dst_rules {
  return (
    enable     => JSON::MaybeXS::true,
    offsetTime => 60,
    start      => {month => 3,  day => 0, hour => 2, weekDay => 0, weekNumber => 2},
    end        => {month => 11, day => 0, hour => 2, weekDay => 0, weekNumber => 1},
  );
}

# ==========================================================================
# Transport
# ==========================================================================

sub open {
  my $self = shift;
  $self->loadMonitor();

  $self->{ua} = LWP::UserAgent->new(timeout => 10);
  $self->{ua}->agent('ZoneMinder Control Agent/'.ZoneMinder::Base::ZM_VERSION());

  if (!$self->guess_credentials()) {
    Error('Herospeed: unable to determine the camera address from ControlAddress or Path');
    $self->{state} = 'closed';
    return undef;
  }
  $self->{api_cookie} = undef;
  if (!$self->api_login()) {
    $self->{state} = 'closed';
    return undef;
  }
  $self->{state} = 'open';
  return 1;
}

sub close {
  my $self = shift;
  if ($self->{api_cookie}) {
    $self->api('/session/logout', 'set', undef);
    $self->{api_cookie} = undef;
  }
  $self->{state} = 'closed';
}

sub api {
  my ($self, $path, $action, $data) = @_;
  $action = 'get' if !defined $action;

  my @headers = (
    'Api-Version'  => API_VERSION,
    'Content-Type' => 'application/json; charset=utf-8',
  );
  # The session cookie is returned in the login response body rather than a
  # Set-Cookie header, so nothing stores it for us.
  push @headers, 'Cookie' => $self->{api_cookie} if $self->{api_cookie};

  my $res = eval {
    $self->{ua}->post('http://'.$self->{host}.'/api'.$path, @headers,
      Content => encode_json({action => $action, data => $data}));
  };
  if (!$res) {
    my $err = $@ // 'no response';
    $err =~ s/\s+/ /g;
    Error("Herospeed: $path request failed: $err");
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
    user => $self->{username}, pass => $self->{password},
    salt => $p->{salt}, challenge => $p->{challenge}, timestamp => $ts,
    enable_iteration => $p->{enableIteration}, iterations => $p->{iterations},
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

# Code 101 is "no session". The camera expires them on its own schedule, so
# any call can meet one; re-login once and repeat rather than failing a
# command the operator just pressed.
sub api_retry {
  my ($self, $path, $action, $data) = @_;
  my $r = $self->api($path, $action, $data);
  return $r if !$r or ($r->{code} // 0) != 101;

  Debug(1, "Herospeed: session expired, logging in again for $path");
  $self->{api_cookie} = undef;
  return undef if !$self->api_login();
  return $self->api($path, $action, $data);
}

# ==========================================================================
# PTZ
# ==========================================================================

sub ptz {
  my ($self, $optType, %extra) = @_;
  my $data = {optType => $optType + 0, %extra};
  my $r = $self->api_retry('/image/ptz-ctrl', 'set', $data);
  if (!$r or ($r->{code} // -1) != 0) {
    Error("Herospeed: ptz command $optType failed"
      .($r ? ' (code '.($r->{code} // '?').')' : ''));
    return undef;
  }
  return 1;
}

sub ptz_move {
  my ($self, $params, $optType) = @_;
  my $speed = clamp_speed($self->getParam($params, 'speed'));
  return $self->ptz($optType, speed => $speed);
}

# ZM sends a move, then a stop when the operator releases the button, unless
# the monitor has an AutoStopTimeout, in which case it expects us to stop
# ourselves after that long.
sub ptz_move_auto {
  my ($self, $params, $optType) = @_;
  my $ok = $self->ptz_move($params, $optType);
  return $ok if !$ok;
  if ($self->{Monitor}{AutoStopTimeout}) {
    usleep($self->{Monitor}{AutoStopTimeout});
    $self->moveStop($params);
  }
  return 1;
}

sub moveConUp        { $_[0]->ptz_move_auto($_[1], CMD_UP) }
sub moveConDown      { $_[0]->ptz_move_auto($_[1], CMD_DOWN) }
sub moveConLeft      { $_[0]->ptz_move_auto($_[1], CMD_LEFT) }
sub moveConRight     { $_[0]->ptz_move_auto($_[1], CMD_RIGHT) }
sub moveConUpLeft    { $_[0]->ptz_move_auto($_[1], CMD_LEFTUP) }
sub moveConUpRight   { $_[0]->ptz_move_auto($_[1], CMD_RIGHTUP) }
sub moveConDownLeft  { $_[0]->ptz_move_auto($_[1], CMD_LEFTDOWN) }
sub moveConDownRight { $_[0]->ptz_move_auto($_[1], CMD_RIGHTDOWN) }
sub moveStop         { $_[0]->ptz(CMD_STOP) }

sub zoomConTele { $_[0]->ptz_move_auto($_[1], CMD_ZOOM_IN) }
sub zoomConWide { $_[0]->ptz_move_auto($_[1], CMD_ZOOM_OUT) }
sub zoomStop    { $_[0]->ptz(CMD_ZOOM_STOP) }

sub focusConNear { $_[0]->ptz_move_auto($_[1], CMD_FOCUS_NEAR) }
sub focusConFar  { $_[0]->ptz_move_auto($_[1], CMD_FOCUS_FAR) }
sub focusStop    { $_[0]->ptz(CMD_FOCUS_STOP) }
sub focusAuto    { $_[0]->ptz(CMD_ONEKEYFOCUS) }

sub irisConOpen  { $_[0]->ptz_move_auto($_[1], CMD_IRIS_OPEN) }
sub irisConClose { $_[0]->ptz_move_auto($_[1], CMD_IRIS_CLOSE) }
sub irisStop     { $_[0]->ptz(CMD_STOP) }

sub reset { $_[0]->ptz(CMD_INITLENS) }

# Auto pan and the cruise tour both run until stopped.
sub horizontalPatrol     { $_[0]->ptz_move($_[1], CMD_AUTOPAN) }
sub horizontalPatrolStop { $_[0]->ptz(CMD_STOP) }

# --- presets --------------------------------------------------------------

sub preset_payload {
  my ($idx, $name, $enable, $readonly) = @_;
  return {
    presetIdx    => $idx + 0,
    presetName   => (defined $name and length $name) ? $name : 'preset'.$idx,
    enablePreset => $enable ? 1 : 0,
    readOnly     => $readonly ? 1 : 0,
  };
}

sub presetGoto {
  my ($self, $params) = @_;
  my $idx = $self->getParam($params, 'preset');
  if (!valid_preset($idx)) {
    Error("Herospeed: refusing to go to preset '".(defined $idx ? $idx : 'undef')."'");
    return undef;
  }
  return $self->ptz(CMD_MOVEPOINT, speed => clamp_speed($self->getParam($params, 'speed')),
    preset => preset_payload($idx, undef, 1, 0));
}

sub presetSet {
  my ($self, $params) = @_;
  my $idx = $self->getParam($params, 'preset');
  if (!valid_preset($idx)) {
    Error("Herospeed: refusing to store preset '".(defined $idx ? $idx : 'undef')."'");
    return undef;
  }
  return $self->ptz(CMD_PRESET_SET, speed => clamp_speed($self->getParam($params, 'speed')),
    preset => preset_payload($idx, $self->getParam($params, 'presetName'), 1, 0));
}

sub presetClear {
  my ($self, $params) = @_;
  my $idx = $self->getParam($params, 'preset');
  if (!valid_preset($idx)) {
    Error("Herospeed: refusing to clear preset '".(defined $idx ? $idx : 'undef')."'");
    return undef;
  }
  return $self->ptz(CMD_PRESET_DEL, preset => preset_payload($idx, undef, 0, 0));
}

# Preset 1 is the conventional home position on these cameras.
sub presetHome {
  my ($self, $params) = @_;
  return $self->ptz(CMD_MOVEPOINT, speed => clamp_speed($self->getParam($params, 'speed')),
    preset => preset_payload(1, undef, 1, 0));
}

# --- position read-back ---------------------------------------------------

sub ptzStatus {
  my $self = shift;
  my $r = $self->api_retry('/image/ptz-info', 'get', undef);
  return undef if !$r or ($r->{code} // -1) != 0 or !$r->{data};
  my $first = ref($r->{data}) eq 'ARRAY' ? $r->{data}[0] : $r->{data};
  return {
    pan  => $first->{panPos},
    tilt => $first->{tiltPos},
    zoom => $first->{zoomPos},
  };
}

# ==========================================================================
# The white supplement floodlight
# ==========================================================================

sub day_night_section {
  my $self = shift;
  my $r = $self->api_retry('/image/image-param', 'get', undef);
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
    Debug(1, "Herospeed: ledMode is already $mode");
    return 1;
  }
  my $r = $self->api_retry('/image/image-param', 'set',
    {dayNightMode => with_led_mode($section, $mode)});
  if (!$r or ($r->{code} // -1) != 0) {
    Error("Herospeed: setting ledMode to $mode failed"
      .($r ? ' (code '.($r->{code} // '?').')' : ''));
    return undef;
  }
  Debug(1, "Herospeed: ledMode set to $mode");
  return 1;
}

sub lightOn  { $_[0]->set_led_mode(LED_WARM) }
sub lightOff { $_[0]->set_led_mode(LED_IR) }

sub lightStatus {
  my $self = shift;
  my $section = $self->day_night_section();
  return {WhiteLight => undef} if !$section;
  my $mode = $section->{ledMode};
  return {WhiteLight => (defined $mode ? ($mode == LED_WARM ? 'On' : 'Off') : undef)};
}

# --- time -----------------------------------------------------------------

sub get_time {
  my $self = shift;
  my $r = $self->api_retry('/system/system-time', 'get', undef);
  if (!$r or ($r->{code} // -1) != 0 or !$r->{data}) {
    Error('Herospeed: could not read the system time settings');
    return undef;
  }
  return $r->{data};
}

sub get_dst {
  my $self = shift;
  my $r = $self->api_retry('/system/summer-time', 'get', undef);
  if (!$r or ($r->{code} // -1) != 0 or !$r->{data}) {
    Error('Herospeed: could not read the summer time settings');
    return undef;
  }
  return $r->{data};
}

# Point the camera at an ntp server and set its timezone, optionally with
# daylight saving rules. Safe to re-run: nothing is written unless it differs
# from what the camera already holds.
#
#   ntp_server    required
#   offset_hours  standard time offset from UTC, e.g. -6 for US Central
#   tz_index      alternative to offset_hours, the vendor's own index
#   dst           hashref of summer time fields, or 'us' for the US rule
sub set_time {
  my ($self, %opts) = @_;

  my $ntp = $opts{ntp_server};
  if (!defined $ntp or $ntp eq '') {
    Error('Herospeed: set_time needs an ntp_server');
    return undef;
  }

  my $index = $opts{tz_index};
  if (!defined $index and defined $opts{offset_hours}) {
    $index = tz_index($opts{offset_hours});
    if (!defined $index) {
      Error("Herospeed: $opts{offset_hours} is not a usable UTC offset");
      return undef;
    }
  }

  my $current = $self->get_time() or return undef;
  my %wanted = (sntpServerAddr => $ntp);
  $wanted{timeZone} = $index if defined $index;

  if (time_needs_write($current, \%wanted)) {
    my $r = $self->api_retry('/system/system-time', 'set',
      time_table($current, %wanted));
    if (!$r or ($r->{code} // -1) != 0) {
      Error('Herospeed: writing the system time settings failed'
        .($r ? ' (code '.($r->{code} // '?').')' : ''));
      return undef;
    }
    Debug(1, "Herospeed: ntp server $ntp"
      .(defined $index ? ', timezone index '.$index
         .' (UTC'.(tz_offset($index) >= 0 ? '+' : '').tz_offset($index).')' : ''));
  } else {
    Debug(1, 'Herospeed: system time settings already correct');
  }

  return 1 if !$opts{dst};

  my %rules = (ref $opts{dst} eq 'HASH') ? %{$opts{dst}} : us_dst_rules();
  my $dst = $self->get_dst() or return undef;
  if (!time_needs_write($dst, \%rules)) {
    Debug(1, 'Herospeed: summer time settings already correct');
    return 1;
  }
  my $r = $self->api_retry('/system/summer-time', 'set', time_table($dst, %rules));
  if (!$r or ($r->{code} // -1) != 0) {
    Error('Herospeed: writing the summer time settings failed'
      .($r ? ' (code '.($r->{code} // '?').')' : ''));
    return undef;
  }
  Debug(1, 'Herospeed: summer time rules written');
  return 1;
}

sub reboot {
  my $self = shift;
  my $r = $self->api_retry('/system/reboot', 'set', undef);
  return ($r and ($r->{code} // -1) == 0) ? 1 : undef;
}

1;
__END__

=head1 NAME

ZoneMinder::Control::Herospeed - Herospeed / LS Vision cameras, vendor web api

=head1 DESCRIPTION

Drives Herospeed (LS Vision) cameras entirely through their own web api,
independently of ZoneMinder::Control::ONVIF. These cameras also speak ONVIF,
and that module remains the right choice when only pan, tilt, zoom and
imaging are wanted; this one exists because two things are reachable only
through the vendor api:

=over

=item * the white supplement floodlight used for active deterrence, which
ONVIF does not expose at all - GetRelayOutputs and the PTZ auxiliary Wiper
command are both accepted by the camera and both do nothing

=item * pan, tilt and zoom position read-back, presets by name, one-key
focus and lens initialisation

=back

Identification: the http Server header is LSWebServer, ONVIF
GetDeviceInformation reports Manufacturer "NONE" and Model "IPCamera", the
ONVIF scope carries the real name (onvif://www.onvif.org/name/L5TW), and the
MAC sits in Herospeed's 2C:6F:51 OUI. /api/system/device-info names the
manufacturer outright.

=head2 Protocol notes

Every call is C<POST /api/E<lt>pathE<gt>> with an C<Api-Version: v4.0.0>
header and a body of C<{"action":"get"|"set","data":...}>. A reply is
C<{"code":0,...}> on success; code 101 means the session has gone and 203 is
a rejected request.

Login is a five round handshake. The session cookie comes back in the login
response body rather than a Set-Cookie header, so it has to be carried by
hand. Each round hashes the raw bytes the previous hex digest names, not the
hex text, which is the detail most likely to be got wrong silently.

=head2 The light

The floodlight is C<dayNightMode.ledMode> inside C</api/image/image-param>,
switched between Warm Light (0) and IR Light (1). It only physically engages
while the camera is in night mode: setting it during the day stores and reads
back correctly but produces no light.

=cut
