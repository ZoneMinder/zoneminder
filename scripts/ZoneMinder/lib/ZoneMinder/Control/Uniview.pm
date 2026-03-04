# ==========================================================================
#
# ZoneMinder Uniview Control Protocol Module
# Copyright (C) 2022 ZoneMinder Inc
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
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
#
# ==========================================================================
#
# This module contains an implementation of the Uniview camera control
# protocol using Uniview's LAPI (JSON-based REST API) for configuration
# and ISAPI for PTZ control.
#
package ZoneMinder::Control::Uniview;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Control;

our @ISA = qw(ZoneMinder::Control);

# ==========================================================================
#
# Uniview LAPI / ISAPI Control Protocol
#
# Set the following:
# ControlAddress: username:password@camera_webaddress:port
# ControlDevice: (optional) realm string or numeric channel ID
#
# ==========================================================================

use ZoneMinder::Logger qw(:all);

use Time::HiRes qw( usleep );

use LWP::UserAgent;
use HTTP::Cookies;
use JSON;

my $ChannelID = 1;              # Usually...
my $DefaultFocusSpeed = 50;     # Should be between 1 and 100
my $DefaultIrisSpeed = 50;      # Should be between 1 and 100

my %config_types = (
  'LAPI/V1.0/System/DeviceBasicInfo'           => {},
  'LAPI/V1.0/System/Time'                      => {},
  'LAPI/V1.0/System/Time/NtpInfo'              => {},
  'LAPI/V1.0/System/Network/Interfaces'        => {},
  'LAPI/V1.0/Channels/0/Media/Video/Streams'   => {},
  'LAPI/V1.0/Channels/0/Media/Video/Streams/0' => {},
  'LAPI/V1.0/Channels/0/Media/Video/Streams/1' => {},
);

sub open {
  my $self = shift;
  $self->loadMonitor();
  $$self{port} = 80;

  # Create a UserAgent for the requests
  $self->{ua} = LWP::UserAgent->new();
  $self->{ua}->cookie_jar( {} );

  $ChannelID = $self->{Monitor}{ControlDevice} if $self->{Monitor}{ControlDevice} and ($self->{Monitor}{ControlDevice} =~ /^\d+$/);
  $$self{realm} = defined($self->{Monitor}->{ControlDevice}) ? $self->{Monitor}->{ControlDevice} : '';

  if (!$self->guess_credentials()) {
    Error('Failed to parse credentials from ControlAddress or Path');
    return undef;
  }

  # Try LAPI first (Uniview native JSON API), fall back to ISAPI
  if ($self->get_realm('/LAPI/V1.0/System/DeviceBasicInfo')) {
    $$self{has_lapi} = 1;
    $self->{state} = 'open';
    return !undef;
  }

  Debug('LAPI not available, trying ISAPI');
  if ($self->get_realm('/ISAPI/System/deviceInfo')) {
    $$self{has_lapi} = 0;
    $self->{state} = 'open';
    return !undef;
  }

  return undef;
} # end sub open

sub PutCmd {
  my $self = shift;
  my $cmd = shift;
  my $content = shift;
  if (!$cmd) {
    Error('No cmd specified in PutCmd');
    return;
  }
  my $req = HTTP::Request->new(PUT => $$self{BaseURL}.'/'.$cmd);
  if ( defined($content) ) {
    $req->content_type('application/x-www-form-urlencoded; charset=UTF-8');
    $req->content('<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $content);
  }
  my $res = $self->{ua}->request($req);
  if (!$res->is_success) {
    if ( $res->code == 401 ) {
      # The camera timeouts connections at short intervals. When this
      # happens the user agent connects again and uses the same auth tokens.
      # The camera rejects this and asks for another token but the UserAgent
      # just gives up. Create a new ua and retry.
      $self->{ua} = LWP::UserAgent->new();
      $self->{ua}->cookie_jar( {} );
      $self->{ua}->credentials("$$self{host}:$$self{port}", $$self{realm}, $$self{username}, $$self{password});

      $res = $self->{ua}->request($req);
      if (!$res->is_success) {
        if ( $self->{Monitor}{ControlAddress} =~ /.+:(.+)@.+/ ) {
          Info('Check username/password is correct');
        } elsif ( $self->{Monitor}{ControlAddress} =~ /^[^:]+@.+/ ) {
          Info('No password in Control Address. Should there be one?');
        } elsif ( $self->{Monitor}{ControlAddress} =~ /^:.+@.+/ ) {
          Info('Password but no username in Control Address.');
        } else {
          Info('Missing username and password in Control Address.');
        }
        Error($res->status_line);
      }
    } else {
      Error($res->status_line);
    }
  } else {
    Debug("Success sending $cmd: ".$res->content);
  } # end unless res->is_success
  Debug($res->content);
} # end sub PutCmd

# ==========================================================================
# LAPI JSON helper methods
# ==========================================================================

sub lapi_get {
  my $self = shift;
  my $endpoint = shift;
  my $response = $self->get('/'.$endpoint);
  if (!$response->is_success()) {
    Error("LAPI GET $endpoint failed: " . $response->status_line);
    return undef;
  }
  my $json;
  eval { $json = decode_json($response->content) };
  if ($@) {
    Error("Failed to decode JSON from $endpoint: $@");
    return undef;
  }
  return $json;
}

sub lapi_put {
  my $self = shift;
  my $endpoint = shift;
  my $data = shift;
  my $json_content = encode_json($data);
  my $response = $self->put('/'.$endpoint, $json_content, { 'Content-Type' => 'application/json' });
  if (!$response || !$response->is_success()) {
    Error("LAPI PUT $endpoint failed: " . ($response ? $response->status_line : 'no response'));
    return 0;
  }
  return 1;
}

# ==========================================================================
# Configuration get/set via LAPI
# ==========================================================================

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
    my $json = $self->lapi_get($category);
    next if !$json;
    if ($json->{Response} && $json->{Response}{Data}) {
      $config{$category} = $json->{Response}{Data};
    } elsif ($json->{Data}) {
      $config{$category} = $json->{Data};
    } else {
      $config{$category} = $json;
    }
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
    Debug("Applying $category");

    my $json = $self->lapi_get($category);
    if (!$json) {
      Error("Failed to get current config for $category");
      return undef;
    }
    # Merge changes into the data portion
    my $data;
    if ($json->{Response} && $json->{Response}{Data}) {
      $data = $json->{Response}{Data};
    } elsif ($json->{Data}) {
      $data = $json->{Data};
    } else {
      $data = $json;
    }
    _deep_merge($data, $$diff{$category});

    if (!$self->lapi_put($category, $json)) {
      Error("Failed to set config for $category");
      return undef;
    }
  }
  return !undef;
}

# ==========================================================================
# PTZ continuous movement via ISAPI
# ==========================================================================
#
# The move continuous functions all call moveVector
# with the direction to move in. This includes zoom
#
sub moveVector {
  my $self = shift;
  my $pandirection  = shift;
  my $tiltdirection = shift;
  my $zoomdirection = shift;
  my $params = shift;
  my $command;                    # The ISAPI/PTZ command

  # Calculate autostop time
  my $duration = $self->getParam( $params, 'autostop', 0 ) * $self->{Monitor}{AutoStopTimeout};
  # Change from microseconds to milliseconds
  $duration = int($duration/1000);
  my $momentxml;
  if( $duration ) {
    $momentxml = "<Momentary><duration>$duration</duration></Momentary>";
    $command = "ISAPI/PTZCtrl/channels/$ChannelID/momentary";
  }
  else {
    $momentxml = "";
    $command = "ISAPI/PTZCtrl/channels/$ChannelID/continuous";
  }
  # Calculate movement speeds
  my $x = $pandirection  * $self->getParam( $params, 'panspeed', 0 );
  my $y = $tiltdirection * $self->getParam( $params, 'tiltspeed', 0 );
  my $z = $zoomdirection * $self->getParam( $params, 'speed', 0 );
  # Create the XML
  my $xml = "<PTZData><pan>$x</pan><tilt>$y</tilt><zoom>$z</zoom>$momentxml</PTZData>";
  # Send it to the camera
  $self->PutCmd($command,$xml);
}
sub zoomStop         { $_[0]->moveVector(  0,  0, 0, splice(@_,1)); }
sub moveStop         { $_[0]->moveVector(  0,  0, 0, splice(@_,1)); }
sub moveConUp        { $_[0]->moveVector(  0,  1, 0, splice(@_,1)); }
sub moveConUpRight   { $_[0]->moveVector(  1,  1, 0, splice(@_,1)); }
sub moveConRight     { $_[0]->moveVector(  1,  0, 0, splice(@_,1)); }
sub moveConDownRight { $_[0]->moveVector(  1, -1, 0, splice(@_,1)); }
sub moveConDown      { $_[0]->moveVector(  0, -1, 0, splice(@_,1)); }
sub moveConDownLeft  { $_[0]->moveVector( -1, -1, 0, splice(@_,1)); }
sub moveConLeft      { $_[0]->moveVector( -1,  0, 0, splice(@_,1)); }
sub moveConUpLeft    { $_[0]->moveVector( -1,  1, 0, splice(@_,1)); }
sub zoomConTele      { $_[0]->moveVector(  0,  0, 1, splice(@_,1)); }
sub zoomConWide      { $_[0]->moveVector(  0,  0,-1, splice(@_,1)); }
#
# Presets including Home set and clear
#
sub presetGoto {
  my $self = shift;
  my $params = shift;
  my $preset = $self->getParam($params,'preset');
  $self->PutCmd("ISAPI/PTZCtrl/channels/$ChannelID/presets/$preset/goto");
}
sub presetSet {
  my $self = shift;
  my $params = shift;
  my $preset = $self->getParam($params,'preset');
  my $xml = "<PTZPreset><id>$preset</id></PTZPreset>";
  $self->PutCmd("ISAPI/PTZCtrl/channels/$ChannelID/presets/$preset",$xml);
}
sub presetHome {
  my $self = shift;
  my $params = shift;
  $self->PutCmd("ISAPI/PTZCtrl/channels/$ChannelID/homeposition/goto");
}
#
# Focus controls all call Focus with a +/- speed
#
sub Focus {
  my $self = shift;
  my $speed = shift;
  my $xml = "<FocusData><focus>$speed</focus></FocusData>";
  $self->PutCmd("ISAPI/System/Video/inputs/channels/$ChannelID/focus",$xml);
}
sub focusConNear {
  my $self = shift;
  my $params = shift;

  # Calculate autostop time
  my $duration = $self->getParam( $params, 'autostop', 0 ) * $self->{Monitor}{AutoStopTimeout};
  # Get the focus speed
  my $speed = $self->getParam( $params, 'speed', $DefaultFocusSpeed );
  $self->Focus(-$speed);
  if($duration) {
    usleep($duration);
    $self->moveStop($params);
  }
}
sub Near {
  my $self = shift;
  my $params = shift;
  $self->Focus(-$DefaultFocusSpeed);
}
sub focusAbsNear {
  my $self = shift;
  my $params = shift;

  # Get the focus speed
  my $speed = $self->getParam( $params, 'speed', $DefaultFocusSpeed );
  $self->Focus(-$speed);
}
sub focusRelNear {
  my $self = shift;
  my $params = shift;
  # Get the focus speed
  my $speed = $self->getParam( $params, 'speed', $DefaultFocusSpeed );
  $self->Focus(-$speed);
}
sub focusConFar {
  my $self = shift;
  my $params = shift;

  # Calculate autostop time
  my $duration = $self->getParam( $params, 'autostop', 0 ) * $self->{Monitor}{AutoStopTimeout};
  # Get the focus speed
  my $speed = $self->getParam( $params, 'speed', $DefaultFocusSpeed );
  $self->Focus($speed);
  if($duration) {
    usleep($duration);
    $self->moveStop($params);
  }
}
sub Far {
  my $self = shift;
  my $params = shift;
  $self->Focus($DefaultFocusSpeed);
}
sub focusAbsFar {
  my $self = shift;
  my $params = shift;

  # Get the focus speed
  my $speed = $self->getParam( $params, 'speed', $DefaultFocusSpeed );
  $self->Focus($speed);
}
sub focusRelFar {
  my $self = shift;
  my $params = shift;

  # Get the focus speed
  my $speed = $self->getParam( $params, 'speed', $DefaultFocusSpeed );
  $self->Focus($speed);
}
#
# Iris controls all call Iris with a +/- speed
#
sub Iris {
  my $self = shift;
  my $speed = shift;

  my $xml = "<IrisData><iris>$speed</iris></IrisData>";
  $self->PutCmd("ISAPI/System/Video/inputs/channels/$ChannelID/iris",$xml);
}
sub irisConClose {
  my $self = shift;
  my $params = shift;

  # Calculate autostop time
  my $duration = $self->getParam( $params, 'autostop', 0 ) * $self->{Monitor}{AutoStopTimeout};
  # Get the iris speed
  my $speed = $self->getParam( $params, 'speed', $DefaultIrisSpeed );
  $self->Iris(-$speed);
  if($duration) {
    usleep($duration);
    $self->moveStop($params);
  }
}
sub Close {
  my $self = shift;
  my $params = shift;

  $self->Iris(-$DefaultIrisSpeed);
}
sub irisAbsClose {
  my $self = shift;
  my $params = shift;

  # Get the iris speed
  my $speed = $self->getParam( $params, 'speed', $DefaultIrisSpeed );
  $self->Iris(-$speed);
}
sub irisRelClose {
  my $self = shift;
  my $params = shift;

  # Get the iris speed
  my $speed = $self->getParam( $params, 'speed', $DefaultIrisSpeed );
  $self->Iris(-$speed);
}
sub irisConOpen {
  my $self = shift;
  my $params = shift;

  # Calculate autostop time
  my $duration = $self->getParam( $params, 'autostop', 0 ) * $self->{Monitor}{AutoStopTimeout};
  # Get the iris speed
  my $speed = $self->getParam( $params, 'speed', $DefaultIrisSpeed );
  $self->Iris($speed);
  if($duration) {
    usleep($duration);
    $self->moveStop($params);
  }
}
sub Open {
  my $self = shift;
  my $params = shift;

  $self->Iris($DefaultIrisSpeed);
}
sub irisAbsOpen {
  my $self = shift;
  my $params = shift;

  # Get the iris speed
  my $speed = $self->getParam( $params, 'speed', $DefaultIrisSpeed );
  $self->Iris($speed);
}
sub irisRelOpen {
  my $self = shift;
  my $params = shift;

  # Get the iris speed
  my $speed = $self->getParam( $params, 'speed', $DefaultIrisSpeed );
  $self->Iris($speed);
}

#
# reset (reboot) the device
#

sub reboot {
  my $self = shift;

  if ($$self{has_lapi}) {
    $self->lapi_put('LAPI/V1.0/System/Reboot', {});
  } else {
    $self->PutCmd('ISAPI/System/reboot');
  }
}

sub probe {
  my ($ip, $username, $password) = @_;

  my $self = new ZoneMinder::Control::Uniview();
  $self->{ua} = LWP::UserAgent->new();
  $self->{ua}->cookie_jar( {} );
  $$self{username} = $username;
  $$self{password} = $password;
  $$self{realm} = '';

  foreach my $port ( '80', '443' ) {
    $$self{port} = $port;
    $$self{host} = $ip;
    $$self{BaseURL} = "http://$ip:$port";
    $$self{address} = "$ip:$port";
    $self->{ua}->credentials("$ip:$port", '', $username, $password);

    # Try LAPI first
    if ($self->get_realm('/LAPI/V1.0/System/DeviceBasicInfo')) {
      return {
        url => "rtsp://$ip/media/video1",
        realm => $$self{realm},
      };
    }
    # Fall back to ISAPI
    if ($self->get_realm('/ISAPI/System/deviceInfo')) {
      return {
        url => "rtsp://$ip/media/video1",
        realm => $$self{realm},
      };
    }
  } # end foreach port
  return undef;
}

sub rtsp_url {
  my ($self, $ip) = @_;
  return 'rtsp://'.$ip.'/media/video1';
}

sub profiles {
}

1;
__END__
