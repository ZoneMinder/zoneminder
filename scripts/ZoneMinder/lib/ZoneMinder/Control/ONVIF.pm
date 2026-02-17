# ==========================================================================
#
# ZoneMinder Unified ONVIF Control Protocol Module
# Copyright (C) 2001-2024 ZoneMinder Inc
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
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
#
# ==========================================================================
#
# Unified ONVIF SOAP/PTZ control module. Replaces the four earlier
# per-vendor copies (onvif.pm, Reolink.pm, Netcat.pm, TapoC520WS_ONVIF.pm)
# with a single implementation that:
#   - Keeps all state in instance variables (no package globals)
#   - Uses base-class guess_credentials() as a fallback
#   - Supports SSL with automatic verification fallback
#   - Fixes brightness case-sensitivity bug in imaging commands
#   - Fixes missing sendCmd calls in contrast commands
#   - Sends imaging commands to the correct /onvif/imaging endpoint
#
# Configuration (Monitor -> Control tab):
#   Control Type    : ONVIF
#   Control Device  : profile token, e.g. "prof0" (default "000")
#   Control Address : [scheme://][user:pass@]host[:port][/onvif/path]
#                     Minimum: 192.168.1.1
#                     Full:    https://admin:secret@192.168.1.100:8080/onvif/PTZ
#   AutoStopTimeout : duration in microseconds (1000000 = 1 s)
#
package ZoneMinder::Control::ONVIF;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Control;

our @ISA = qw(ZoneMinder::Control);

use ZoneMinder::Logger qw(:all);

use Time::HiRes qw( usleep );
use LWP::UserAgent;
use MIME::Base64;
use Digest::SHA;
use DateTime;
use URI;
use URI::Escape;
use IO::Socket::SSL;

# =========================================================================
#  Config types — maps category names to ONVIF SOAP endpoints/actions
# =========================================================================

my %config_types = (
  # --- Device service (/onvif/device_service) ---
  'DeviceInformation' => {
    endpoint => '/onvif/device_service',
    action   => 'http://www.onvif.org/ver10/device/wsdl/GetDeviceInformation',
    body     => '<s:Body><GetDeviceInformation xmlns="http://www.onvif.org/ver10/device/wsdl"/></s:Body>',
  },
  'DateTime' => {
    endpoint => '/onvif/device_service',
    action   => 'http://www.onvif.org/ver10/device/wsdl/GetSystemDateAndTime',
    body     => '<s:Body><GetSystemDateAndTime xmlns="http://www.onvif.org/ver10/device/wsdl"/></s:Body>',
    writable => 1,
  },
  'Hostname' => {
    endpoint => '/onvif/device_service',
    action   => 'http://www.onvif.org/ver10/device/wsdl/GetHostname',
    body     => '<s:Body><GetHostname xmlns="http://www.onvif.org/ver10/device/wsdl"/></s:Body>',
  },
  'DNS' => {
    endpoint => '/onvif/device_service',
    action   => 'http://www.onvif.org/ver10/device/wsdl/GetDNS',
    body     => '<s:Body><GetDNS xmlns="http://www.onvif.org/ver10/device/wsdl"/></s:Body>',
  },
  'NTP' => {
    endpoint => '/onvif/device_service',
    action   => 'http://www.onvif.org/ver10/device/wsdl/GetNTP',
    body     => '<s:Body><GetNTP xmlns="http://www.onvif.org/ver10/device/wsdl"/></s:Body>',
    writable => 1,
  },
  'NetworkInterfaces' => {
    endpoint => '/onvif/device_service',
    action   => 'http://www.onvif.org/ver10/device/wsdl/GetNetworkInterfaces',
    body     => '<s:Body><GetNetworkInterfaces xmlns="http://www.onvif.org/ver10/device/wsdl"/></s:Body>',
  },
  'Capabilities' => {
    endpoint => '/onvif/device_service',
    action   => 'http://www.onvif.org/ver10/device/wsdl/GetCapabilities',
    body     => '<s:Body><GetCapabilities xmlns="http://www.onvif.org/ver10/device/wsdl"><Category>All</Category></GetCapabilities></s:Body>',
  },
  'Scopes' => {
    endpoint => '/onvif/device_service',
    action   => 'http://www.onvif.org/ver10/device/wsdl/GetScopes',
    body     => '<s:Body><GetScopes xmlns="http://www.onvif.org/ver10/device/wsdl"/></s:Body>',
  },
  'Services' => {
    endpoint => '/onvif/device_service',
    action   => 'http://www.onvif.org/ver10/device/wsdl/GetServices',
    body     => '<s:Body><GetServices xmlns="http://www.onvif.org/ver10/device/wsdl"><IncludeCapability>true</IncludeCapability></GetServices></s:Body>',
  },
  # --- Media service (/onvif/media_service) ---
  'Profiles' => {
    endpoint => '/onvif/media_service',
    action   => 'http://www.onvif.org/ver10/media/wsdl/GetProfiles',
    body     => '<s:Body><GetProfiles xmlns="http://www.onvif.org/ver10/media/wsdl"/></s:Body>',
  },
  'VideoEncoderConfiguration' => {
    endpoint => '/onvif/media_service',
    action   => 'http://www.onvif.org/ver10/media/wsdl/GetVideoEncoderConfigurations',
    body     => '<s:Body><GetVideoEncoderConfigurations xmlns="http://www.onvif.org/ver10/media/wsdl"/></s:Body>',
  },
  'VideoSources' => {
    endpoint => '/onvif/media_service',
    action   => 'http://www.onvif.org/ver10/media/wsdl/GetVideoSources',
    body     => '<s:Body><GetVideoSources xmlns="http://www.onvif.org/ver10/media/wsdl"/></s:Body>',
  },
  'VideoSourceConfigurations' => {
    endpoint => '/onvif/media_service',
    action   => 'http://www.onvif.org/ver10/media/wsdl/GetVideoSourceConfigurations',
    body     => '<s:Body><GetVideoSourceConfigurations xmlns="http://www.onvif.org/ver10/media/wsdl"/></s:Body>',
  },
  'AudioSources' => {
    endpoint => '/onvif/media_service',
    action   => 'http://www.onvif.org/ver10/media/wsdl/GetAudioSources',
    body     => '<s:Body><GetAudioSources xmlns="http://www.onvif.org/ver10/media/wsdl"/></s:Body>',
  },
  'AudioSourceConfigurations' => {
    endpoint => '/onvif/media_service',
    action   => 'http://www.onvif.org/ver10/media/wsdl/GetAudioSourceConfigurations',
    body     => '<s:Body><GetAudioSourceConfigurations xmlns="http://www.onvif.org/ver10/media/wsdl"/></s:Body>',
  },
  'AudioEncoderConfigurations' => {
    endpoint => '/onvif/media_service',
    action   => 'http://www.onvif.org/ver10/media/wsdl/GetAudioEncoderConfigurations',
    body     => '<s:Body><GetAudioEncoderConfigurations xmlns="http://www.onvif.org/ver10/media/wsdl"/></s:Body>',
  },
  'StreamUri' => {
    endpoint => '/onvif/media_service',
    action   => 'http://www.onvif.org/ver10/media/wsdl/GetStreamUri',
    body     => '<s:Body><GetStreamUri xmlns="http://www.onvif.org/ver10/media/wsdl"><StreamSetup><Stream xmlns="http://www.onvif.org/ver10/schema">RTP-Unicast</Stream><Transport xmlns="http://www.onvif.org/ver10/schema"><Protocol>RTSP</Protocol></Transport></StreamSetup><ProfileToken>__PROFILE_TOKEN__</ProfileToken></GetStreamUri></s:Body>',
  },
  'SnapshotUri' => {
    endpoint => '/onvif/media_service',
    action   => 'http://www.onvif.org/ver10/media/wsdl/GetSnapshotUri',
    body     => '<s:Body><GetSnapshotUri xmlns="http://www.onvif.org/ver10/media/wsdl"><ProfileToken>__PROFILE_TOKEN__</ProfileToken></GetSnapshotUri></s:Body>',
  },
  # --- Imaging service (/onvif/imaging) ---
  'ImagingSettings' => {
    endpoint => '/onvif/imaging',
    action   => 'http://www.onvif.org/ver20/imaging/wsdl/GetImagingSettings',
    body     => '<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"><GetImagingSettings xmlns="http://www.onvif.org/ver20/imaging/wsdl"><VideoSourceToken>000</VideoSourceToken></GetImagingSettings></s:Body>',
    writable => 1,
  },
  'ImagingOptions' => {
    endpoint => '/onvif/imaging',
    action   => 'http://www.onvif.org/ver20/imaging/wsdl/GetOptions',
    body     => '<s:Body><GetOptions xmlns="http://www.onvif.org/ver20/imaging/wsdl"><VideoSourceToken>000</VideoSourceToken></GetOptions></s:Body>',
  },
  # --- PTZ service (/onvif/PTZ) ---
  'PTZConfigurations' => {
    endpoint => '/onvif/PTZ',
    action   => 'http://www.onvif.org/ver20/ptz/wsdl/GetConfigurations',
    body     => '<s:Body><GetConfigurations xmlns="http://www.onvif.org/ver20/ptz/wsdl"/></s:Body>',
  },
  'PTZNodes' => {
    endpoint => '/onvif/PTZ',
    action   => 'http://www.onvif.org/ver20/ptz/wsdl/GetNodes',
    body     => '<s:Body><GetNodes xmlns="http://www.onvif.org/ver20/ptz/wsdl"/></s:Body>',
  },
  'PTZPresets' => {
    endpoint => '/onvif/PTZ',
    action   => 'http://www.onvif.org/ver20/ptz/wsdl/GetPresets',
    body     => '<s:Body><GetPresets xmlns="http://www.onvif.org/ver20/ptz/wsdl"><ProfileToken>__PROFILE_TOKEN__</ProfileToken></GetPresets></s:Body>',
  },
  'PTZStatus' => {
    endpoint => '/onvif/PTZ',
    action   => 'http://www.onvif.org/ver20/ptz/wsdl/GetStatus',
    body     => '<s:Body><GetStatus xmlns="http://www.onvif.org/ver20/ptz/wsdl"><ProfileToken>__PROFILE_TOKEN__</ProfileToken></GetStatus></s:Body>',
  },
);

# =========================================================================
#  open / close
# =========================================================================

sub open {
  my $self = shift;
  $self->loadMonitor();

  # --- UserAgent with SSL verification (will fall back if needed) --------
  $self->{ua} = LWP::UserAgent->new();
  $self->{ua}->agent('ZoneMinder Control Agent/'.ZoneMinder::Base::ZM_VERSION);
  $self->{ua}->ssl_opts(
    verify_hostname => 1,
    SSL_verify_mode => IO::Socket::SSL::SSL_VERIFY_PEER,
  );
  $$self{ssl_verified} = 1;

  # --- Profile token from ControlDevice (default '000') -----------------
  my $cd = $self->{Monitor}->{ControlDevice} // '';
  $$self{profileToken} = ($cd =~ /\S/) ? $cd : '000';
  $$self{realm} = $cd;

  # --- Parse ControlAddress for credentials, host, port, ONVIF path -----
  my $control_address = $self->{Monitor}->{ControlAddress} // '';
  if ($control_address =~ /\S/) {
    $self->_parse_control_address($control_address);
  } else {
    # No ControlAddress — try base-class fallback (extracts creds from Path)
    $$self{onvif_path} = '/onvif/PTZ';
    if ($self->guess_credentials()) {
      # Rebuild BaseURL without any path that guess_credentials may include
      my $scheme = ($$self{uri} && $$self{uri}->scheme()) ? $$self{uri}->scheme() : 'http';
      $scheme = 'http' if $scheme eq 'rtsp';
      $$self{BaseURL} = $scheme . '://' . $$self{host} . ':' . $$self{port};
    } else {
      Warning('Failed to determine camera address from ControlAddress or Path');
    }
  }

  # --- Connectivity check (non-fatal) ------------------------------------
  if ($$self{BaseURL}) {
    my $res = $self->sendCmd('/onvif/device_service',
      '<s:Body><GetSystemDateAndTime xmlns="http://www.onvif.org/ver10/device/wsdl"/></s:Body>',
      'http://www.onvif.org/ver10/device/wsdl/GetSystemDateAndTime');
    if ($res) {
      Debug('ONVIF device responded at ' . $$self{BaseURL});
    } else {
      Warning('No response from ONVIF device at ' . $$self{BaseURL}
        . '/onvif/device_service — PTZ commands may still work');
    }
  }

  $self->{state} = 'open';
  return !undef;
}

sub _parse_control_address {
  my ($self, $address) = @_;

  # Ensure a scheme is present so URI can parse correctly
  $address = 'http://' . $address if $address !~ m{^https?://};

  my $uri = URI->new($address);

  $$self{host} = $uri->host();

  # Credentials
  if ($uri->userinfo) {
    my ($user, $pass) = split /:/, $uri->userinfo, 2;
    $$self{username} = $user;
    $$self{password} = URI::Escape::uri_unescape($pass) if defined $pass;
  }

  # Port — URI->port falls back to default_port for the scheme
  $$self{port} = $uri->port || 80;

  # ONVIF service path (default /onvif/PTZ)
  my $path = $uri->path;
  $$self{onvif_path} = ($path && $path ne '/') ? $path : '/onvif/PTZ';

  # Base URL without path
  $$self{BaseURL} = $uri->scheme . '://' . $$self{host} . ':' . $$self{port};

  Debug('ONVIF open: base=' . $$self{BaseURL}
    . ' path=' . $$self{onvif_path}
    . ' user=' . ($$self{username} // '(none)'));
}

# =========================================================================
#  WS-Security PasswordDigest helpers (plain functions, not methods)
# =========================================================================

sub _digest_base64 {
  my ($nonce, $date, $password) = @_;
  my $sha = Digest::SHA->new(1);
  $sha->add($nonce . $date . $password);
  return encode_base64($sha->digest, '');
}

sub _auth_header {
  my ($username, $password) = @_;
  return '' if !$username;

  my @charset = ('0' .. '9', 'A' .. 'Z', 'a' .. 'z');
  my $nonce = join '' => map $charset[rand @charset], 1 .. 20;
  my $nonceBase64 = encode_base64($nonce, '');
  my $date = DateTime->now()->iso8601() . 'Z';

  return '
<s:Header>
  <Security s:mustUnderstand="1" xmlns="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
    <UsernameToken xmlns="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
      <Username>' . $username . '</Username>
      <Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordDigest">' . _digest_base64($nonce, $date, $password) . '</Password>
      <Nonce EncodingType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary">' . $nonceBase64 . '</Nonce>
      <Created xmlns="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">' . $date . '</Created>
    </UsernameToken>
  </Security>
</s:Header>';
}

# =========================================================================
#  sendCmd — core SOAP POST
# =========================================================================

sub sendCmd {
  my ($self, $endpoint, $body, $action) = @_;

  my $msg = '<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope">'
    . _auth_header($$self{username}, $$self{password})
    . $body
    . '</s:Envelope>';

  my $url = $$self{BaseURL} . $endpoint;
  $self->printMsg($url, 'Tx');

  my $req = HTTP::Request->new(POST => $url);
  $req->header('content-type'     => 'application/soap+xml; charset=utf-8; action="' . $action . '"');
  $req->header('Host'             => $$self{host} . ':' . $$self{port});
  $req->header('content-length'   => length($msg));
  $req->header('accept-encoding'  => 'gzip, deflate');
  $req->header('connection'       => 'Close');
  $req->content($msg);

  my $res = $self->{ua}->request($req);

  # SSL fallback: on certificate errors, retry without verification
  if (!$res->is_success && $$self{ssl_verified}
      && $res->status_line =~ /SSL|certificate|verify/i) {
    Warning("SSL verification failed for $url ("
      . $res->status_line . '), retrying without verification');
    $self->{ua}->ssl_opts(
      verify_hostname => 0,
      SSL_verify_mode => IO::Socket::SSL::SSL_VERIFY_NONE,
      SSL_hostname    => '',
    );
    $$self{ssl_verified} = 0;
    $res = $self->{ua}->request($req);
  }

  if ($res->is_success) {
    return $res;
  }

  Error("ONVIF command to $url failed: " . $res->status_line());
  return undef;
}

# =========================================================================
#  Imaging — Brightness / Contrast
# =========================================================================

sub getCamParams {
  my $self = shift;
  $$self{CamParams} = {} if !$$self{CamParams};

  my $body = '
<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
  <GetImagingSettings xmlns="http://www.onvif.org/ver20/imaging/wsdl">
    <VideoSourceToken>000</VideoSourceToken>
  </GetImagingSettings>
</s:Body>';

  my $res = $self->sendCmd('/onvif/imaging', $body,
    'http://www.onvif.org/ver20/imaging/wsdl/GetImagingSettings');

  if ($res) {
    my $content = $res->decoded_content;
    if ($content =~ /<tt:(Brightness)>(.+?)<\/tt:Brightness>/) {
      $$self{CamParams}{$1} = $2;
    }
    if ($content =~ /<tt:(Contrast)>(.+?)<\/tt:Contrast>/) {
      $$self{CamParams}{$1} = $2;
    }
  } else {
    Error('Unable to retrieve camera imaging settings');
  }
}

sub _setImaging {
  my ($self, $param, $value) = @_;

  my $body = '
<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
  <SetImagingSettings xmlns="http://www.onvif.org/ver20/imaging/wsdl">
    <VideoSourceToken>000</VideoSourceToken>
    <ImagingSettings>
      <' . $param . ' xmlns="http://www.onvif.org/ver10/schema">' . $value . '</' . $param . '>
    </ImagingSettings>
    <ForcePersistence>true</ForcePersistence>
  </SetImagingSettings>
</s:Body>';

  return $self->sendCmd('/onvif/imaging', $body,
    'http://www.onvif.org/ver20/imaging/wsdl/SetImagingSettings');
}

# Increase Brightness (mapped to Iris Open in ZM UI)
sub irisAbsOpen {
  my $self = shift;
  my $params = shift;
  $$self{CamParams} = {} if !$$self{CamParams};
  $self->getCamParams() unless $$self{CamParams}{Brightness};
  my $step = $self->getParam($params, 'step');

  $$self{CamParams}{Brightness} += $step;
  $$self{CamParams}{Brightness} = 100 if $$self{CamParams}{Brightness} > 100;
  Debug("Brightness increase to $$self{CamParams}{Brightness}");
  $self->_setImaging('Brightness', $$self{CamParams}{Brightness});
}

# Decrease Brightness (mapped to Iris Close in ZM UI)
sub irisAbsClose {
  my $self = shift;
  my $params = shift;
  $$self{CamParams} = {} if !$$self{CamParams};
  # BUG FIX: originals checked lowercase 'brightness' — never matched
  $self->getCamParams() unless $$self{CamParams}{Brightness};
  my $step = $self->getParam($params, 'step');

  $$self{CamParams}{Brightness} -= $step;
  $$self{CamParams}{Brightness} = 0 if $$self{CamParams}{Brightness} < 0;
  Debug("Brightness decrease to $$self{CamParams}{Brightness}");
  $self->_setImaging('Brightness', $$self{CamParams}{Brightness});
}

# Increase Contrast (mapped to White In in ZM UI)
sub whiteAbsIn {
  my $self = shift;
  my $params = shift;
  $$self{CamParams} = {} if !$$self{CamParams};
  $self->getCamParams() unless $$self{CamParams}{Contrast};
  my $step = $self->getParam($params, 'step');

  $$self{CamParams}{Contrast} += $step;
  $$self{CamParams}{Contrast} = 100 if $$self{CamParams}{Contrast} > 100;
  Debug("Contrast increase to $$self{CamParams}{Contrast}");
  # BUG FIX: originals (Reolink/Netcat/TapoC520WS) were missing this sendCmd call
  $self->_setImaging('Contrast', $$self{CamParams}{Contrast});
}

# Decrease Contrast (mapped to White Out in ZM UI)
sub whiteAbsOut {
  my $self = shift;
  my $params = shift;
  $$self{CamParams} = {} if !$$self{CamParams};
  $self->getCamParams() unless $$self{CamParams}{Contrast};
  my $step = $self->getParam($params, 'step');

  $$self{CamParams}{Contrast} -= $step;
  $$self{CamParams}{Contrast} = 0 if $$self{CamParams}{Contrast} < 0;
  Debug("Contrast decrease to $$self{CamParams}{Contrast}");
  # BUG FIX: originals (Reolink/Netcat/TapoC520WS) were missing this sendCmd call
  $self->_setImaging('Contrast', $$self{CamParams}{Contrast});
}

# =========================================================================
#  Configuration get/set
# =========================================================================

sub _xml_to_hash {
  my ($xml) = @_;
  my %hash;
  # Match leaf elements: <ns:Name>value</ns:Name> where value has no child elements
  while ($xml =~ /<(?:\w+:)?(\w+)>([^<]+)<\/(?:\w+:)?\1>/g) {
    $hash{$1} = $2;
  }
  return \%hash;
}

sub _deep_merge {
  my ($base, $override) = @_;
  foreach my $key (keys %$override) {
    if (ref $$override{$key} eq 'HASH' and ref $$base{$key} eq 'HASH') {
      _deep_merge($$base{$key}, $$override{$key});
    } else {
      $$base{$key} = $$override{$key};
    }
  }
}

sub get_config {
  my $self = shift;
  my %config;
  foreach my $category ( @_ ? @_ : keys %config_types ) {
    if (!$config_types{$category}) {
      Warning("Unknown config category: $category");
      next;
    }
    my $ct = $config_types{$category};
    my $body = $$ct{body};
    $body =~ s/__PROFILE_TOKEN__/$$self{profileToken}/g;
    my $res = $self->sendCmd($$ct{endpoint}, $body, $$ct{action});
    if (!$res) {
      Warning("Failed to get config for $category");
      next;
    }
    $config{$category} = _xml_to_hash($res->decoded_content);
  }
  return \%config;
}

sub set_config {
  my $self = shift;
  my $diff = shift;
  foreach my $category ( @_ ? @_ : keys %config_types ) {
    if (!$$diff{$category}) {
      Debug("No changes for category $category");
      next;
    }
    if (!$config_types{$category}) {
      Warning("Unknown config category: $category");
      next;
    }

    if (!$config_types{$category}{writable}) {
      Debug("Category $category is read-only, skipping set");
      next;
    }

    if ($category eq 'ImagingSettings') {
      my $current = $self->get_config($category);
      my $merged = $$current{$category} || {};
      _deep_merge($merged, $$diff{$category});

      my $fields = '';
      for my $f (qw(Brightness Contrast Saturation Sharpness ColorSaturation)) {
        if (defined $$merged{$f}) {
          $fields .= '<' . $f . ' xmlns="http://www.onvif.org/ver10/schema">'
            . $$merged{$f} . '</' . $f . '>';
        }
      }
      my $body = '
<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
  <SetImagingSettings xmlns="http://www.onvif.org/ver20/imaging/wsdl">
    <VideoSourceToken>000</VideoSourceToken>
    <ImagingSettings>' . $fields . '</ImagingSettings>
    <ForcePersistence>true</ForcePersistence>
  </SetImagingSettings>
</s:Body>';
      if (!$self->sendCmd('/onvif/imaging', $body,
          'http://www.onvif.org/ver20/imaging/wsdl/SetImagingSettings')) {
        Error("Failed to set ImagingSettings");
        return undef;
      }

    } elsif ($category eq 'DateTime') {
      my $current = $self->get_config($category);
      my $merged = $$current{$category} || {};
      _deep_merge($merged, $$diff{$category});

      my $dt_type = $$merged{DateTimeType} || 'Manual';
      my $dst     = $$merged{DaylightSavings} || 'false';
      my $tz      = $$merged{TimeZone} // '';

      my $time_xml = '';
      if (defined $$merged{Hour} || defined $$merged{Minute} || defined $$merged{Second}
          || defined $$merged{Year} || defined $$merged{Month} || defined $$merged{Day}) {
        $time_xml = '
      <UTCDateTime>
        <Time xmlns="http://www.onvif.org/ver10/schema">
          <Hour>'   . ($$merged{Hour}   // 0) . '</Hour>
          <Minute>' . ($$merged{Minute} // 0) . '</Minute>
          <Second>' . ($$merged{Second} // 0) . '</Second>
        </Time>
        <Date xmlns="http://www.onvif.org/ver10/schema">
          <Year>'  . ($$merged{Year}  // 2000) . '</Year>
          <Month>' . ($$merged{Month} // 1)    . '</Month>
          <Day>'   . ($$merged{Day}   // 1)    . '</Day>
        </Date>
      </UTCDateTime>';
      }

      my $tz_xml = '';
      $tz_xml = '<TimeZone><TZ xmlns="http://www.onvif.org/ver10/schema">' . $tz . '</TZ></TimeZone>' if $tz;

      my $body = '
<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
  <SetSystemDateAndTime xmlns="http://www.onvif.org/ver10/device/wsdl">
    <DateTimeType>' . $dt_type . '</DateTimeType>
    <DaylightSavings>' . $dst . '</DaylightSavings>'
    . $tz_xml . $time_xml . '
  </SetSystemDateAndTime>
</s:Body>';
      if (!$self->sendCmd('/onvif/device_service', $body,
          'http://www.onvif.org/ver10/device/wsdl/SetSystemDateAndTime')) {
        Error("Failed to set DateTime");
        return undef;
      }

    } elsif ($category eq 'NTP') {
      my $current = $self->get_config($category);
      my $merged = $$current{$category} || {};
      _deep_merge($merged, $$diff{$category});

      my $from_dhcp = $$merged{FromDHCP} || 'false';
      my $ntp_manual = '';
      if (lc($from_dhcp) ne 'true') {
        my $type = $$merged{Type} || 'IPv4';
        my $addr = $$merged{IPv4Address} // $$merged{IPv6Address} // $$merged{DNSname} // '';
        my $addr_tag;
        if ($type eq 'IPv4') {
          $addr_tag = '<IPv4Address xmlns="http://www.onvif.org/ver10/schema">' . $addr . '</IPv4Address>';
        } elsif ($type eq 'IPv6') {
          $addr_tag = '<IPv6Address xmlns="http://www.onvif.org/ver10/schema">' . $addr . '</IPv6Address>';
        } else {
          $addr_tag = '<DNSname xmlns="http://www.onvif.org/ver10/schema">' . $addr . '</DNSname>';
        }
        $ntp_manual = '
    <NTPManual>
      <Type xmlns="http://www.onvif.org/ver10/schema">' . $type . '</Type>
      ' . $addr_tag . '
    </NTPManual>';
      }

      my $body = '
<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
  <SetNTP xmlns="http://www.onvif.org/ver10/device/wsdl">
    <FromDHCP>' . $from_dhcp . '</FromDHCP>' . $ntp_manual . '
  </SetNTP>
</s:Body>';
      if (!$self->sendCmd('/onvif/device_service', $body,
          'http://www.onvif.org/ver10/device/wsdl/SetNTP')) {
        Error("Failed to set NTP");
        return undef;
      }

    } else {
      Debug("Category $category is writable but no set handler implemented, skipping");
    }
  }
  return !undef;
}

# =========================================================================
#  PTZ — ContinuousMove / Stop
# =========================================================================

sub _continuous_move {
  my ($self, $pan_x, $pan_y, $zoom_x) = @_;

  my $velocity = '';
  if (defined $pan_x and defined $pan_y) {
    $velocity .= '<PanTilt x="' . $pan_x . '" y="' . $pan_y
      . '" xmlns="http://www.onvif.org/ver10/schema"/>';
  }
  if (defined $zoom_x) {
    $velocity .= '<Zoom x="' . $zoom_x
      . '" xmlns="http://www.onvif.org/ver10/schema"/>';
  }

  my $body = '
<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
  <ContinuousMove xmlns="http://www.onvif.org/ver20/ptz/wsdl">
    <ProfileToken>' . $$self{profileToken} . '</ProfileToken>
    <Velocity>' . $velocity . '</Velocity>
  </ContinuousMove>
</s:Body>';

  $self->sendCmd($$self{onvif_path}, $body,
    'http://www.onvif.org/ver20/ptz/wsdl/ContinuousMove');

  my $is_zoom = (defined $zoom_x && !defined $pan_x);
  $self->_auto_stop($is_zoom);
}

sub _auto_stop {
  my ($self, $is_zoom) = @_;
  my $timeout = $self->{Monitor}->{AutoStopTimeout};
  return unless $timeout;

  Debug("Auto stop after ${timeout} us");
  usleep($timeout);

  my $body = '
<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
  <Stop xmlns="http://www.onvif.org/ver20/ptz/wsdl">
    <ProfileToken>' . $$self{profileToken} . '</ProfileToken>
    <PanTilt>' . ($is_zoom ? 'false' : 'true') . '</PanTilt>
    <Zoom>'   . ($is_zoom ? 'true'  : 'false') . '</Zoom>
  </Stop>
</s:Body>';

  $self->sendCmd($$self{onvif_path}, $body,
    'http://www.onvif.org/ver20/ptz/wsdl/ContinuousMove');
}

# --- Directional continuous moves ----------------------------------------

sub moveConUp        { Debug('Move Up');         $_[0]->_continuous_move( 0,     0.5,  undef) }
sub moveConDown      { Debug('Move Down');       $_[0]->_continuous_move( 0,    -0.5,  undef) }
sub moveConLeft      { Debug('Move Left');       $_[0]->_continuous_move(-0.49,  0,    undef) }
sub moveConRight     { Debug('Move Right');      $_[0]->_continuous_move( 0.49,  0,    undef) }
sub moveConUpRight   { Debug('Move Up-Right');   $_[0]->_continuous_move( 0.5,   0.5,  undef) }
sub moveConUpLeft    { Debug('Move Up-Left');    $_[0]->_continuous_move(-0.5,   0.5,  undef) }
sub moveConDownRight { Debug('Move Down-Right'); $_[0]->_continuous_move( 0.5,  -0.5,  undef) }
sub moveConDownLeft  { Debug('Move Down-Left');  $_[0]->_continuous_move(-0.5,  -0.5,  undef) }

# --- Zoom ----------------------------------------------------------------

sub zoomConTele { Debug('Zoom Tele'); $_[0]->_continuous_move(undef, undef,  0.49) }
sub zoomConWide { Debug('Zoom Wide'); $_[0]->_continuous_move(undef, undef, -0.49) }

# --- Stop ----------------------------------------------------------------

sub moveStop {
  my $self = shift;
  Debug('Move Stop');

  my $body = '
<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
  <Stop xmlns="http://www.onvif.org/ver20/ptz/wsdl">
    <ProfileToken>' . $$self{profileToken} . '</ProfileToken>
    <PanTilt>true</PanTilt>
    <Zoom>true</Zoom>
  </Stop>
</s:Body>';

  $self->sendCmd($$self{onvif_path}, $body,
    'http://www.onvif.org/ver20/ptz/wsdl/ContinuousMove');
}

sub zoomStop {
  my $self = shift;
  Debug('Zoom Stop');

  my $body = '
<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
  <Stop xmlns="http://www.onvif.org/ver20/ptz/wsdl">
    <ProfileToken>' . $$self{profileToken} . '</ProfileToken>
    <PanTilt>false</PanTilt>
    <Zoom>true</Zoom>
  </Stop>
</s:Body>';

  $self->sendCmd($$self{onvif_path}, $body,
    'http://www.onvif.org/ver20/ptz/wsdl/ContinuousMove');
}

# =========================================================================
#  AbsoluteMove / RelativeMove
# =========================================================================

sub moveMap {
  my $self   = shift;
  my $params = shift;
  my $x = $self->getParam($params, 'xcoord');
  my $y = $self->getParam($params, 'ycoord');
  Debug("AbsoluteMove to $x, $y");

  my $body = '
<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
  <AbsoluteMove xmlns="http://www.onvif.org/ver20/ptz/wsdl">
    <ProfileToken>' . $$self{profileToken} . '</ProfileToken>
    <Position>
      <PanTilt x="' . $x . '" y="' . $y . '" xmlns="http://www.onvif.org/ver10/schema"/>
    </Position>
    <Speed>
      <Zoom x="1" xmlns="http://www.onvif.org/ver10/schema"/>
    </Speed>
  </AbsoluteMove>
</s:Body>';

  $self->sendCmd($$self{onvif_path}, $body,
    'http://www.onvif.org/ver20/ptz/wsdl/AbsoluteMove');
}

sub moveRel {
  my $self   = shift;
  my $params = shift;
  my $x = $self->getParam($params, 'xcoord');
  my $y = $self->getParam($params, 'ycoord');
  Debug("RelativeMove by $x, $y");

  my $body = '
<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
  <RelativeMove xmlns="http://www.onvif.org/ver20/ptz/wsdl">
    <ProfileToken>' . $$self{profileToken} . '</ProfileToken>
    <Translation>
      <PanTilt x="' . $x . '" y="' . $y . '" xmlns="http://www.onvif.org/ver10/schema" space="http://www.onvif.org/ver10/tptz/PanTiltSpaces/PositionGenericSpace"/>
      <Zoom x="1"/>
    </Translation>
  </RelativeMove>
</s:Body>';

  $self->sendCmd($$self{onvif_path}, $body,
    'http://www.onvif.org/ver20/ptz/wsdl/RelativeMove');
}

# =========================================================================
#  Presets
# =========================================================================

sub presetSet {
  my $self   = shift;
  my $params = shift;
  my $preset = $self->getParam($params, 'preset');
  Debug("Set Preset $preset");

  my $body = '
<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
  <SetPreset xmlns="http://www.onvif.org/ver20/ptz/wsdl">
    <ProfileToken>' . $$self{profileToken} . '</ProfileToken>
    <PresetToken>' . $preset . '</PresetToken>
  </SetPreset>
</s:Body>';

  $self->sendCmd($$self{onvif_path}, $body,
    'http://www.onvif.org/ver20/ptz/wsdl/SetPreset');
}

sub presetGoto {
  my $self   = shift;
  my $params = shift;
  my $preset = $self->getParam($params, 'preset');
  Debug("Goto Preset $preset");

  my $body = '
<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
  <GotoPreset xmlns="http://www.onvif.org/ver20/ptz/wsdl">
    <ProfileToken>' . $$self{profileToken} . '</ProfileToken>
    <PresetToken>' . $preset . '</PresetToken>
  </GotoPreset>
</s:Body>';

  $self->sendCmd($$self{onvif_path}, $body,
    'http://www.onvif.org/ver20/ptz/wsdl/GotoPreset');
}

sub presetHome {
  my $self = shift;
  Debug('Goto Home Position');

  my $body = '
<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
  <GotoHomePosition xmlns="http://www.onvif.org/ver20/ptz/wsdl">
    <ProfileToken>' . $$self{profileToken} . '</ProfileToken>
  </GotoHomePosition>
</s:Body>';

  $self->sendCmd($$self{onvif_path}, $body,
    'http://www.onvif.org/ver20/ptz/wsdl/GotoHomePosition');
}

# =========================================================================
#  Reboot
# =========================================================================

sub reboot {
  my $self = shift;
  Debug('ONVIF SystemReboot');

  my $body = '
<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
  <SystemReboot xmlns="http://www.onvif.org/ver10/device/wsdl"/>
</s:Body>';

  $self->sendCmd('/onvif/device_service', $body,
    'http://www.onvif.org/ver10/device/wsdl/SystemReboot');
}

sub reset {
  return $_[0]->reboot();
}

# =========================================================================
#  probe / rtsp_url  (called by ZM's network probe infrastructure)
# =========================================================================

sub probe {
  my ($ip, $username, $password) = @_;

  my $self = ZoneMinder::Control::ONVIF->new();
  $self->{ua} = LWP::UserAgent->new();
  $self->{ua}->agent('ZoneMinder Control Agent/'.ZoneMinder::Base::ZM_VERSION);
  $self->{ua}->ssl_opts(
    verify_hostname => 1,
    SSL_verify_mode => IO::Socket::SSL::SSL_VERIFY_PEER,
  );
  $$self{ssl_verified} = 1;
  $$self{username} = $username;
  $$self{password} = $password;
  $$self{onvif_path} = '/onvif/PTZ';

  my $test_body = '<s:Body><GetSystemDateAndTime xmlns="http://www.onvif.org/ver10/device/wsdl"/></s:Body>';
  my $test_action = 'http://www.onvif.org/ver10/device/wsdl/GetSystemDateAndTime';

  foreach my $port ('80', '443') {
    $$self{host} = $ip;
    $$self{port} = $port;

    # Try HTTP
    $$self{BaseURL} = "http://$ip:$port";
    if ($self->sendCmd('/onvif/device_service', $test_body, $test_action)) {
      return { url => "rtsp://$ip/onvif1", realm => '' };
    }

    # Try HTTPS on 443
    if ($port eq '443') {
      $$self{BaseURL} = "https://$ip:$port";
      if ($self->sendCmd('/onvif/device_service', $test_body, $test_action)) {
        return { url => "rtsp://$ip/onvif1", realm => '' };
      }
    }
  }
  return undef;
}

sub rtsp_url {
  my ($self, $ip) = @_;
  return 'rtsp://' . $ip . '/onvif1';
}

1;
__END__
