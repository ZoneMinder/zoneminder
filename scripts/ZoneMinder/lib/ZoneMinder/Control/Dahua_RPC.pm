# ==========================================================================
#
# ZoneMinder Dahua/Amcrest JSON-RPC Control Protocol Module
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
#
# ==========================================================================
#
# This module controls Dahua and Amcrest cameras (including the Amcrest
# "Smart Home" ASH/ADC line) via the Dahua JSON-RPC HTTP interface (/RPC2).
# These cameras have the cgi-bin HTTP API disabled and expose no ONVIF PTZ
# service, so the JSON-RPC interface is the only local PTZ control path.
#
# ==========================================================================

package ZoneMinder::Control::Dahua_RPC;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Control;
require LWP::UserAgent;

use JSON::MaybeXS qw(encode_json decode_json);
use Digest::MD5 qw(md5_hex);
use Scalar::Util qw(blessed);

our @ISA = qw(ZoneMinder::Control);

use ZoneMinder::Logger qw(:all);
use ZoneMinder::Config qw(:all);

# Compute the Dahua RPC login response from the server-supplied realm/random.
# Pure function (no $self) so it is unit-testable without a network or DB.
sub compute_login_hash {
  my ($user, $realm, $random, $pass) = @_;
  my $h1 = uc(md5_hex("$user:$realm:$pass"));
  return uc(md5_hex("$user:$random:$h1"));
}

sub open {
  my $self = shift;
  $self->loadMonitor();

  $self->{ua} = LWP::UserAgent->new(timeout => 10);
  $self->{ua}->agent('ZoneMinder Control Agent/'.ZoneMinder::Base::ZM_VERSION);

  # guess_credentials() needs {ua} to exist; it fills host/port/username/password.
  $self->guess_credentials() if !$self->{host};

  if (!$self->{host}) {
    Error('Dahua_RPC: no host could be determined from ControlAddress or Path');
    $self->{state} = 'closed';
    return undef;
  }
  # RPC2 is always on the camera's web port (default 80), not the stream port.
  # parse_Path() derives {port} from the RTSP URL (typically 554), which is
  # wrong for RPC. Only honour {port} if it looks like a web port.
  my $port = ($self->{port} && $self->{port} != 554) ? $self->{port} : 80;
  # JSON-RPC auth is in-band; do not put userinfo in the URL.
  $self->{RPCBase} = 'http://'.$self->{host}.':'.$port.'/';
  $self->{rpc_id} = 0;

  if ($self->login()) {
    $self->{state} = 'open';
    return 1;
  }
  $self->{state} = 'closed';
  return undef;
}

sub close {
  my $self = shift;
  $self->{state} = 'closed';
}

# Low-level transport. Returns the decoded response hashref, or undef on
# HTTP/JSON failure. Does NOT retry; callers handle session expiry.
sub rpc_call {
  my ($self, $method, $params, %opts) = @_;
  $self->{rpc_id} = ($self->{rpc_id} || 0) + 1;

  my $req = { method => $method, id => $self->{rpc_id}, params => $params };
  $req->{session} = $self->{session} if $self->{session};
  $req->{object}  = $opts{object}    if defined $opts{object};

  my $url = $self->{RPCBase} . ($opts{login} ? 'RPC2_Login' : 'RPC2');
  my $res = eval {
    $self->{ua}->post($url, 'Content-Type' => 'application/json',
                      Content => encode_json($req));
  };
  if (!$res) {
    # Keep the persistent control daemon alive if the transport throws.
    Error("Dahua_RPC: request failed for $method: $@");
    return undef;
  }
  if (!$res->is_success) {
    Error('Dahua_RPC: HTTP '.$res->status_line." for $method");
    return undef;
  }
  my $data = eval { decode_json($res->decoded_content) };
  if ($@ or !$data) {
    Error("Dahua_RPC: failed to decode JSON for $method: $@");
    return undef;
  }
  return $data;
}

# Two-stage Dahua login. Stores the session on success.
sub login {
  my $self = shift;
  $self->{rpc_id} = 0;
  $self->{session} = undef;
  $self->{ptz_object} = undef;

  my $r = $self->rpc_call('global.login',
    { userName => $self->{username}, password => '', clientType => 'Web3.0' },
    login => 1);
  if (!$r or !$r->{params} or !$r->{params}{realm} or !$r->{session}) {
    Error('Dahua_RPC: login stage 1 (challenge) failed');
    return undef;
  }
  $self->{session} = $r->{session};
  my $pw = compute_login_hash($self->{username}, $r->{params}{realm},
                              $r->{params}{random}, $self->{password});

  $r = $self->rpc_call('global.login',
    { userName => $self->{username}, password => $pw, clientType => 'Web3.0',
      loginType => 'Direct', authorityType => 'Default', passwordType => 'Default' },
    login => 1);
  if ($r and $r->{result}) {
    Debug('Dahua_RPC: logged in to '.$self->{host});
    return 1;
  }
  Error('Dahua_RPC: login failed: '.(($r and $r->{error}) ? $r->{error}{message} : 'no/!result response'));
  return undef;
}

# True if an RPC error response indicates the session has expired.
sub session_expired {
  my ($self, $error) = @_;
  return ($error && $error->{message} && $error->{message} =~ /session/i) ? 1 : 0;
}

# Lazily create (and cache) the ptz.factory.instance handle for channel 0.
sub ensure_ptz_object {
  my $self = shift;
  return $self->{ptz_object} if defined $self->{ptz_object};
  my $r = $self->rpc_call('ptz.factory.instance', { channel => 0 });
  # A refusal is "result": false with an error, which is defined but not a handle.
  $self->{ptz_object} = ($r && defined $r->{result} && !JSON::MaybeXS::is_bool($r->{result})) ? $r->{result} : undef;
  Error('Dahua_RPC: failed to create ptz instance') if !defined $self->{ptz_object};
  return $self->{ptz_object};
}

# Issue a ptz.start / ptz.stop with the given code and args. Self-heals on
# session expiry by re-logging-in, recreating the instance, and retrying once.
# %a keys: code, arg1, arg2, arg3 (all default 0). $action is 'start' or 'stop'.
sub ptz_raw {
  my ($self, $action, %a) = @_;
  my $obj = $self->ensure_ptz_object();
  return undef if !defined $obj;

  my $params = {
    code    => $a{code},
    arg1    => defined $a{arg1} ? $a{arg1} : 0,
    arg2    => defined $a{arg2} ? $a{arg2} : 0,
    arg3    => defined $a{arg3} ? $a{arg3} : 0,
    channel => 0,
  };
  my $r = $self->rpc_call("ptz.$action", $params, object => $obj);
  if ($r && !$r->{result} && $r->{error} && $self->session_expired($r->{error})) {
    Debug('Dahua_RPC: session expired during ptz, re-logging in');
    return undef if !$self->login();
    $obj = $self->ensure_ptz_object();
    return undef if !defined $obj;
    $r = $self->rpc_call("ptz.$action", $params, object => $obj);
  }
  return $r;
}

# Continuous-move speed comes from the UI command params (default 4).
sub _con {
  my ($self, $code, $params, $axis) = @_;
  my $mon = $$self{Monitor};
  $mon->suspendMotionDetection() if blessed($mon) && !$mon->{ModectDuringPTZ};
  my $pan_speed = $self->getParam($params, 'panspeed', 4);
  my $tilt_speed = $self->getParam($params, 'tiltspeed', 4);
  $self->{LastCmd} = $code;
  my ($arg1, $arg2) = (0, 0);
  if ($axis eq 'pan') {
    $arg2 = $pan_speed;
  } elsif ($axis eq 'tilt') {
    $arg2 = $tilt_speed;
  } elsif ($axis eq 'diag') {
    $arg1 = $tilt_speed;
    $arg2 = $pan_speed;
  }
  $self->ptz_raw('start', code => $code, arg1 => $arg1, arg2 => $arg2);
}

sub moveConUp        { my ($s,$p)=@_; $s->_con('Up',        $p, 'tilt'); }
sub moveConDown      { my ($s,$p)=@_; $s->_con('Down',      $p, 'tilt'); }
sub moveConLeft      { my ($s,$p)=@_; $s->_con('Left',      $p, 'pan'); }
sub moveConRight     { my ($s,$p)=@_; $s->_con('Right',     $p, 'pan'); }
sub moveConUpLeft    { my ($s,$p)=@_; $s->_con('LeftUp',    $p, 'diag'); }
sub moveConUpRight   { my ($s,$p)=@_; $s->_con('RightUp',   $p, 'diag'); }
sub moveConDownLeft  { my ($s,$p)=@_; $s->_con('LeftDown',  $p, 'diag'); }
sub moveConDownRight { my ($s,$p)=@_; $s->_con('RightDown', $p, 'diag'); }

sub moveStop {
  my $self = shift;
  if ($self->{LastCmd}) {
    $self->ptz_raw('stop', code => $self->{LastCmd}, arg2 => 0);
    $self->{LastCmd} = '';
    my $mon = $$self{Monitor};
    $mon->resumeMotionDetection() if blessed($mon) && !$mon->{ModectDuringPTZ};
  }
}

sub zoomStop  { my $self = shift; $self->moveStop(); }
sub focusStop { my $self = shift; $self->moveStop(); }

sub presetGoto {
  my ($self, $params) = @_;
  my $preset = $self->getParam($params, 'preset');
  Debug("Dahua_RPC: goto preset $preset");
  $self->ptz_raw('start', code => 'GotoPreset', arg2 => $preset);
}

sub presetSet {
  my ($self, $params) = @_;
  my $preset = $self->getParam($params, 'preset');
  Debug("Dahua_RPC: set preset $preset");
  $self->ptz_raw('start', code => 'SetPreset', arg2 => $preset);
}

sub presetHome {
  my $self = shift;
  Debug('Dahua_RPC: goto home (preset 1)');
  $self->ptz_raw('start', code => 'GotoPreset', arg2 => 1);
}

sub zoomConTele {
  my ($self, $params) = @_;
  my $mon = $$self{Monitor};
  $mon->suspendMotionDetection() if blessed($mon) && !$mon->{ModectDuringPTZ};
  $self->{LastCmd} = 'ZoomTele';
  $self->ptz_raw('start', code => 'ZoomTele');
}

sub zoomConWide {
  my ($self, $params) = @_;
  my $mon = $$self{Monitor};
  $mon->suspendMotionDetection() if blessed($mon) && !$mon->{ModectDuringPTZ};
  $self->{LastCmd} = 'ZoomWide';
  $self->ptz_raw('start', code => 'ZoomWide');
}

sub focusConNear {
  my ($self, $params) = @_;
  my $mon = $$self{Monitor};
  $mon->suspendMotionDetection() if blessed($mon) && !$mon->{ModectDuringPTZ};
  $self->{LastCmd} = 'FocusNear';
  $self->ptz_raw('start', code => 'FocusNear');
}

sub focusConFar {
  my ($self, $params) = @_;
  my $mon = $$self{Monitor};
  $mon->suspendMotionDetection() if blessed($mon) && !$mon->{ModectDuringPTZ};
  $self->{LastCmd} = 'FocusFar';
  $self->ptz_raw('start', code => 'FocusFar');
}

# Reboot the camera via magicBox.reboot (RPC). Used for both reset and reboot.
sub reset {
  my $self = shift;
  Debug('Dahua_RPC: reboot via magicBox.reboot');
  $self->rpc_call('magicBox.reboot', undef);
}

sub reboot {
  my $self = shift;
  $self->reset();
}

# Build the CoaxialControlIO.control params for the white light.
# Pure function (no $self) so it is unit-testable without a camera.
# $io: 1 = on, 2 = off. Type 1 = white light (Type 2 = siren, intentionally unused).
# IO/TriggerMode are numeric: the camera rejects string "On"/"Off"/"Manual".
sub coaxial_white_payload {
  my ($io) = @_;
  return { channel => 0, info => [ { Type => 1, IO => $io + 0, TriggerMode => 1 } ] };
}

sub coaxial_white {
  my ($self, $io) = @_;
  return $self->rpc_call('CoaxialControlIO.control', coaxial_white_payload($io));
}

sub lightOn  { my $self = shift; $self->coaxial_white(1); }
sub lightOff { my $self = shift; $self->coaxial_white(2); }

# Indicator LED (camera status light) via LightGlobal config.
# Used on models like the ASH42-B where LightGlobal.Enable controls the indicator.
sub indicatorLightOn  { my $self = shift; $self->set_config({ name => 'LightGlobal', table => [ { Enable => JSON::MaybeXS::true  } ] }); }
sub indicatorLightOff { my $self = shift; $self->set_config({ name => 'LightGlobal', table => [ { Enable => JSON::MaybeXS::false } ] }); }

sub indicatorLightStatus {
  my $self = shift;
  my $r = $self->rpc_call('configManager.getConfig', { name => 'LightGlobal' });
  if (!$r || !$r->{result}) {
    if ($r && $self->session_expired($r->{error})) {
      return { Enable => undef } if !$self->login();
      $r = $self->rpc_call('configManager.getConfig', { name => 'LightGlobal' });
    }
  }
  my $enable = ($r and $r->{result} and $r->{params} and $r->{params}{table})
    ? $r->{params}{table}[0]{Enable} : undef;
  return { Enable => (defined $enable ? ($enable ? 'On' : 'Off') : undef) };
}

# Query the current white-light state. Returns { WhiteLight => 'On'|'Off'|undef }.
# Used by the status-aware single-button toggle in the web UI.
sub lightStatus {
  my $self = shift;
  my $r = $self->rpc_call('CoaxialControlIO.getStatus', { channel => 0 });
  if (!$r || !$r->{result}) {
    if ($r && $self->session_expired($r->{error})) {
      return { WhiteLight => undef } if !$self->login();
      $r = $self->rpc_call('CoaxialControlIO.getStatus', { channel => 0 });
    }
  }
  my $status = ($r and ref($r) eq 'HASH' and $r->{params}) ? $r->{params}{status} : undef;
  return { WhiteLight => ($status ? $status->{WhiteLight} : undef) };
}

# Send a keepalive ping to prevent idle session expiry in long-running daemons.
# Call periodically (every ~30s) from the control daemon's idle loop. The
# requested timeout must comfortably exceed the ping interval. Note: these
# cameras also enforce an absolute session lifetime (~30 min) that keepAlive
# cannot extend, so an occasional failure here is expected and simply triggers
# a re-authentication.
sub keepAlive {
  my $self = shift;
  my $r = $self->rpc_call('global.keepAlive', { timeout => 60 });
  if (!$r || !$r->{result}) {
    Debug('Dahua_RPC: keepAlive did not confirm session, re-authenticating');
    $self->login();
  }
}

# ---------------------------------------------------------------------------
# Config / probe interface
# ---------------------------------------------------------------------------

# Return a hashref of the camera's current readable state via RPC.
# Used by zmcontrol direct mode: zmcontrol.pl --protocol Dahua_RPC --address ... --command get_config
sub get_config {
  my $self = shift;
  my %cfg;

  # Identity
  for my $m (qw(magicBox.getDeviceType magicBox.getSoftwareVersion magicBox.getProductDefinition)) {
    my $r = $self->rpc_call($m);
    $cfg{$m} = $r->{params} if $r and $r->{result} and $r->{params};
  }

  # PTZ
  my $ptz_obj = $self->ensure_ptz_object();
  if (defined $ptz_obj) {
    for my $m (qw(ptz.getCurrentProtocolCaps ptz.getStatus ptz.getPresets)) {
      my $r = $self->rpc_call($m, {channel => 0}, object => $ptz_obj);
      $cfg{$m} = $r->{params} if $r and $r->{result} and $r->{params};
      $cfg{$m.'_error'} = $r->{error}{message} if $r and !$r->{result} and $r->{error};
    }
  }

  # Coaxial (light/siren)
  for my $m (qw(CoaxialControlIO.getCaps CoaxialControlIO.getStatus)) {
    my $r = $self->rpc_call($m, {channel => 0});
    $cfg{$m} = $r->{params} if $r and $r->{result} and $r->{params};
    $cfg{$m.'_error'} = $r->{error}{message} if $r and !$r->{result} and $r->{error};
  }

  # Named configs
  for my $n (qw(Lighting Lighting_V2 LightGlobal PtzPreset WorkMode)) {
    my $r = $self->rpc_call('configManager.getConfig', {name => $n});
    $cfg{"config.$n"} = $r->{params}{table} if $r and $r->{result} and $r->{params};
    $cfg{"config.$n".'_error'} = $r->{error}{message} if $r and !$r->{result} and $r->{error};
  }

  return \%cfg;
}

# Write a named config blob back to the camera.
# $diff should be { name => 'Lighting', table => [...] } matching the Dahua configManager shape.
sub set_config {
  my ($self, $diff) = @_;
  return undef unless ref($diff) eq 'HASH' and $diff->{name} and exists $diff->{table};
  my $r = $self->rpc_call('configManager.setConfig', $diff);
  if (!$r || !$r->{result}) {
    my $msg = $r ? ($r->{error}{message} // 'result=false') : 'no response';
    Error('Dahua_RPC: set_config('.$diff->{name}.') failed: '.$msg);
    # Re-login on session expiry and retry once (same pattern as ptz_raw).
    if ($r && $self->session_expired($r->{error})) {
      Debug('Dahua_RPC: session expired during set_config, re-logging in');
      return undef if !$self->login();
      $r = $self->rpc_call('configManager.setConfig', $diff);
      Error('Dahua_RPC: set_config('.$diff->{name}.') still failed after re-login')
        if !$r || !$r->{result};
    }
  }
  return $r ? $r->{result} : undef;
}

# Probe the camera's capabilities: everything get_config returns, plus test
# which interfaces respond vs. return InterfaceNotFound. Useful for
# characterising an unknown Dahua/Amcrest model without writing ad-hoc scripts.
# Strictly read-only: it never sends a method that changes camera state (no
# reboot, no PTZ move), so it is safe to run against a live camera.
#   zmcontrol.pl --protocol Dahua_RPC --address admin:pass@IP --command probe
sub probe {
  my $self = shift;
  my %out;

  # --- identity ---
  for my $m (qw(magicBox.getDeviceType magicBox.getSoftwareVersion magicBox.getProductDefinition)) {
    my $r = $self->rpc_call($m);
    if ($r and $r->{result}) {
      $out{identity}{$m} = $r->{params};
    } else {
      $out{identity}{$m} = 'unavailable: '.($r->{error}{message}//'no result');
    }
  }

  # --- PTZ ---
  my $ptz_obj = $self->ensure_ptz_object();
  $out{ptz}{instance} = defined $ptz_obj ? "handle=$ptz_obj" : 'unavailable';
  if (defined $ptz_obj) {
    for my $m (qw(ptz.getCurrentProtocolCaps ptz.getStatus ptz.getPresets)) {
      my $r = $self->rpc_call($m, {channel => 0}, object => $ptz_obj);
      $out{ptz}{$m} = ($r and $r->{result}) ? $r->{params} : ($r->{error}{message}//'?');
    }
    # No ptz.start test here: even GotoPreset can move the camera, and the
    # getCurrentProtocolCaps/getStatus/getPresets calls above already show
    # whether PTZ is available.
  }

  # --- Coaxial (light/siren/deterrence) ---
  for my $m (qw(CoaxialControlIO.getCaps CoaxialControlIO.getStatus)) {
    my $r = $self->rpc_call($m, {channel => 0});
    $out{coaxial}{$m} = ($r and $r->{result}) ? $r->{params} : ($r->{error}{message}//'?');
  }

  # --- Named configs ---
  for my $n (qw(Lighting Lighting_V2 LightGlobal LightingControl PtzPreset
                WorkMode AlarmLocal CoaxialControlIO WhiteLight ExAlarmDeterrence)) {
    my $r = $self->rpc_call('configManager.getConfig', {name => $n});
    $out{config}{$n} = ($r and $r->{result}) ? $r->{params}{table} : ($r->{error}{message}//'?');
  }

  # --- Method reachability (read-only) ---
  # system.listMethod is safe and returns the method names the camera
  # implements. magicBox.reboot would power-cycle the camera, so it is never
  # sent: we only look for it in the returned list.
  my $lm = $self->rpc_call('system.listMethod');
  my @methods;
  if ($lm and $lm->{result}) {
    # Dahua answers {"params":{"method":[names]}}; accept a bare list too.
    my $p = $lm->{params};
    $p = $p->{method} if ref($p) eq 'HASH';
    @methods = map { ref($_) eq 'HASH' ? ($_->{method} // $_->{name} // '') : ($_ // '') } @$p
      if ref($p) eq 'ARRAY';
    @methods = grep { length } @methods;
  }
  if ($lm and defined $lm->{result}) {
    $out{methods}{'system.listMethod'} = $lm->{result}
      ? 'available'.(@methods ? ': '.scalar(@methods).' methods' : '')
      : 'error: '.($lm->{error}{message}//'?');
  } else {
    $out{methods}{'system.listMethod'} = 'unreachable';
  }

  for my $m (qw(magicBox.reboot ptz.start configManager.setConfig)) {
    if (@methods) {
      $out{methods}{$m} = (grep { $_ eq $m } @methods)
        ? 'present in method list' : 'not in method list';
    } else {
      # Unknown/empty list format: report without guessing.
      $out{methods}{$m} = 'not tested';
    }
  }

  return \%out;
}

1;
__END__

=pod

=head1 NAME

ZoneMinder::Control::Dahua_RPC - Dahua/Amcrest JSON-RPC PTZ control

=head1 DESCRIPTION

Controls Dahua and Amcrest cameras (including the Amcrest "Smart Home"
ASH/ADC line) over the Dahua JSON-RPC HTTP interface exposed at C</RPC2>.

These Smart Home models have the legacy C<cgi-bin> HTTP API disabled and
expose no ONVIF PTZ service, so the JSON-RPC interface used by the Amcrest
mobile app is the only local PTZ control path. Authentication uses the Dahua
two-stage MD5 challenge: the camera returns a realm and random nonce, and the
client replies with C<MD5(user:random:MD5(user:realm:pass))> (uppercase hex).

Credentials are taken from the monitor's Control Address in the form
C<user:pass@host[:port]> (default port 80). The session is established once
when the control daemon starts and reused for the daemon's lifetime; it is
transparently re-established if the camera expires it.

=head1 SEE ALSO

ZoneMinder::Control::Amcrest_HTTP (cgi-bin API variant for non-Smart-Home models)

=head1 COPYRIGHT AND LICENSE

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation; either version 2 of the License, or (at your option) any later
version.

=cut
