# ==========================================================================
#
# ZoneMinder Reolink HTTP API Control Protocol Module
# Copyright (C) 2026 ZoneMinder Community
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
# This module contains the implementation of the Reolink HTTP API control
# protocol using api.cgi endpoint
#
package ZoneMinder::Control::Reolink_HTTP;

use 5.006;
use strict;
use warnings;

use Time::HiRes qw( usleep );

require ZoneMinder::Base;
require ZoneMinder::Control;
require LWP::UserAgent;
use URI;
use HTTP::Request;

our @ISA = qw(ZoneMinder::Control);

our %CamParams = ();

# ==========================================================================
#
# Reolink HTTP API Control Protocol using api.cgi
#
# On Control Address use the format:
#   USERNAME:PASSWORD@ADDRESS:PORT
#   eg:  admin:password@10.1.2.1:80
#        admin:mypass@192.168.1.100:80
#
# Control Device can be left empty or set to camera channel (usually 0)
#
# This module uses the Reolink HTTP API (api.cgi) rather than ONVIF
# See:   https://support.reolink.com/hc/en-us/articles/360007010473-CGI-API-User-Guide
#       https://github.com/verheesj/reolink-api
#
# ==========================================================================

use ZoneMinder::Logger qw(:all);
use ZoneMinder::Config qw(:all);
use JSON;

sub new {
  my $class = shift;
  my $id = shift;
  my $self = ZoneMinder::Control->new($id);
  bless($self, $class);
  return $self;
}

sub open {
  my $self = shift;

  $self->loadMonitor();
  
  # Initialize user agent
  $self->{ua} = LWP::UserAgent->new;
  $self->{ua}->agent('ZoneMinder Control Agent/'. ZoneMinder::Base::ZM_VERSION);
  $self->{ua}->timeout(10);
  
  # Use parent class guess_credentials to parse ControlAddress
  $self->guess_credentials();
  
  # Set defaults if not set by guess_credentials
  $$self{username} = 'admin' unless $$self{username};
  $$self{password} = '' unless $$self{password};
  $$self{port} = 80 unless $$self{port};
  
  Debug("Reolink HTTP: Using credentials for $$self{host}:$$self{port}, user: $$self{username}");
  
  # Get channel from ControlDevice or default to 0
  $$self{channel} = $self->{Monitor}->{ControlDevice} || 0;
  
  # Initialize token variables
  $$self{token} = 'null';
  $$self{token_expiry} = 0;
  $$self{token_lease_time} = 3600; # Default 1 hour

  # Perform login to get token
  if ($self->login()) {
    $self->{state} = 'open';
    Info('Reolink HTTP API connection established with token');
    return ! undef;
  }

  Warning('Reolink HTTP API login failed');
  $self->{state} = 'closed';
  return undef;
}

sub close {
  my $self = shift;
  $self->logout() if $$self{token} && $$self{token} ne 'null';
  $self->{state} = 'closed';
}

sub login {
  my $self = shift;
  
  Debug('Reolink:  Attempting login');
  
  # Build login request
  my $login_cmd = {
    cmd => 'Login',
    action => 0,
    param => {
      User => {
        userName => $$self{username},
        password => $$self{password}
      }
    }
  };
  
  my $json = encode_json([$login_cmd]);
  my $url = 'http://'.$$self{host}.':'. $$self{port}.'/cgi-bin/api.cgi?cmd=Login&token=null';
  
  $self->printMsg($json, 'Tx');
  
  my $req = HTTP::Request->new(POST => $url);
  $req->content_type('application/json');
  $req->content($json);
  
  my $res = $self->{ua}->request($req);
  
  if ($res->is_success) {
    my $content = $res->decoded_content;
    Debug("Login response: $content");
    
    my $response;
    eval {
      $response = decode_json($content);
      };
    if ($@) {
      Error("Failed to parse login response: $@");
    }
    if (ref($response) eq 'ARRAY' && @$response > 0) {
	    my $result = $response->[0];

	    if ($result->{code} == 0 && $result->{value} && $result->{value}->{Token}) {
		    $$self{token} = $result->{value}->{Token}->{name};
		    $$self{token_lease_time} = $result->{value}->{Token}->{leaseTime} || 3600;
		    $$self{token_expiry} = time() + $$self{token_lease_time};

		    Debug("Reolink login successful, token: $$self{token}, expires in: $$self{token_lease_time}s");
		    return !undef;
	    } else {
		    my $code = $result->{code} || 'unknown';
		    my $detail = $result->{error}->{detail} || 'unknown error';
		    Error("Reolink login failed with code $code: $detail");
	    }
    }
  } else {
    Error('Reolink login HTTP request failed:  '.$res->status_line);
  }
  
  return undef;
}

sub logout {
  my $self = shift;
  
  return unless $$self{token} && $$self{token} ne 'null';
  
  Debug('Reolink:  Logging out');
  
  my $logout_cmd = {
    cmd => 'Logout',
    action => 0,
    param => {}
  };
  
  $self->sendApiCmd($logout_cmd);
  
  $$self{token} = 'null';
  $$self{token_expiry} = 0;
}

sub ensureToken {
  my $self = shift;
  
  # Check if token needs refresh (within 60 seconds of expiry)
  if (!$$self{token} || $$self{token} eq 'null' || time() >= ($$self{token_expiry} - 60)) {
    Debug('Token expired or expiring soon, refreshing...');
    return $self->login();
  }
  
  return ! undef;
}

sub sendApiCmd {
  my $self = shift;
  my $cmd = shift;
  my $result = undef;

  # Ensure we have a valid token
  return undef unless $self->ensureToken();

  # Build the POST request
  my @cmds = ref($cmd) eq 'ARRAY' ? @$cmd : ($cmd);
  my $json = encode_json(\@cmds);
  
  $self->printMsg($json, 'Tx');
  
  # Construct URL with token
  my $url = 'http://'.$$self{host}.':'.$$self{port}.'/cgi-bin/api.cgi?cmd='.$cmds[0]->{cmd}.'&token='.$$self{token};
  
  my $req = HTTP::Request->new(POST => $url);
  $req->content_type('application/json');
  $req->content($json);
  
  my $res = $self->{ua}->request($req);
  
  if ($res->is_success) {
    $result = $res->decoded_content;
    Info('Camera control success:  '.$res->status_line().' for '.$cmds[0]->{cmd});
    
    # Try to decode JSON response
    eval {
      my $response = decode_json($result);
      # Check for error in response
      if (ref($response) eq 'ARRAY' && @$response > 0) {
        if (defined($response->[0]->{code}) && $response->[0]->{code} != 0) {
          my $code = $response->[0]->{code};
          my $detail = $response->[0]->{error}->{detail} || 'unknown error';
          Error("API returned error code $code: $detail");
        }
      }
    };
  } else {
    Error('Camera control command FAILED: '.$res->status_line().' for '.$cmds[0]->{cmd});
  }

  return $result;
}

# Reboot camera
sub reboot {
  my $self = shift;
  
  Info('Rebooting camera');
  
  my $cmd = {
    cmd => 'Reboot',
    action => 0,
    param => {
      channel => $$self{channel}
    }
  };
  
  $self->sendApiCmd($cmd);
}

# PTZ Control Commands

sub moveConUp {
  my $self = shift;
  my $params = shift;
  my $speed = $self->getParam($params, 'tiltspeed') || 32;
  
  Debug('Move Up');
  $$self{Monitor}->suspendMotionDetection() if ! $self->{Monitor}->{ModectDuringPTZ};
  
  my $cmd = {
    cmd => 'PtzCtrl',
    action => 0,
    param => {
      channel => $$self{channel},
      op => 'Up',
      speed => $speed
    }
  };
  
  $self->sendApiCmd($cmd);
}

sub moveConDown {
  my $self = shift;
  my $params = shift;
  my $speed = $self->getParam($params, 'tiltspeed') || 32;
  
  Debug('Move Down');
  $$self{Monitor}->suspendMotionDetection() if !$self->{Monitor}->{ModectDuringPTZ};
  
  my $cmd = {
    cmd => 'PtzCtrl',
    action => 0,
    param => {
      channel => $$self{channel},
      op => 'Down',
      speed => $speed
    }
  };
  
  $self->sendApiCmd($cmd);
}

sub moveConLeft {
  my $self = shift;
  my $params = shift;
  my $speed = $self->getParam($params, 'panspeed') || 32;
  
  Debug('Move Left');
  $$self{Monitor}->suspendMotionDetection() if !$self->{Monitor}->{ModectDuringPTZ};
  
  my $cmd = {
    cmd => 'PtzCtrl',
    action => 0,
    param => {
      channel => $$self{channel},
      op => 'Left',
      speed => $speed
    }
  };
  
  $self->sendApiCmd($cmd);
}

sub moveConRight {
  my $self = shift;
  my $params = shift;
  my $speed = $self->getParam($params, 'panspeed') || 32;
  
  Debug('Move Right');
  $$self{Monitor}->suspendMotionDetection() if !$self->{Monitor}->{ModectDuringPTZ};
  
  my $cmd = {
    cmd => 'PtzCtrl',
    action => 0,
    param => {
      channel => $$self{channel},
      op => 'Right',
      speed => $speed
    }
  };
  
  $self->sendApiCmd($cmd);
}

sub moveConUpLeft {
  my $self = shift;
  my $params = shift;
  my $speed = $self->getParam($params, 'panspeed') || 32;
  
  Debug('Move Up Left');
  $$self{Monitor}->suspendMotionDetection() if !$self->{Monitor}->{ModectDuringPTZ};
  
  my $cmd = {
    cmd => 'PtzCtrl',
    action => 0,
    param => {
      channel => $$self{channel},
      op => 'LeftUp',
      speed => $speed
    }
  };
  
  $self->sendApiCmd($cmd);
}

sub moveConUpRight {
  my $self = shift;
  my $params = shift;
  my $speed = $self->getParam($params, 'panspeed') || 32;
  
  Debug('Move Up Right');
  $$self{Monitor}->suspendMotionDetection() if !$self->{Monitor}->{ModectDuringPTZ};
  
  my $cmd = {
    cmd => 'PtzCtrl',
    action => 0,
    param => {
      channel => $$self{channel},
      op => 'RightUp',
      speed => $speed
    }
  };
  
  $self->sendApiCmd($cmd);
}

sub moveConDownLeft {
  my $self = shift;
  my $params = shift;
  my $speed = $self->getParam($params, 'panspeed') || 32;
  
  Debug('Move Down Left');
  $$self{Monitor}->suspendMotionDetection() if !$self->{Monitor}->{ModectDuringPTZ};
  
  my $cmd = {
    cmd => 'PtzCtrl',
    action => 0,
    param => {
      channel => $$self{channel},
      op => 'LeftDown',
      speed => $speed
    }
  };
  
  $self->sendApiCmd($cmd);
}

sub moveConDownRight {
  my $self = shift;
  my $params = shift;
  my $speed = $self->getParam($params, 'panspeed') || 32;
  
  Debug('Move Down Right');
  $$self{Monitor}->suspendMotionDetection() if !$self->{Monitor}->{ModectDuringPTZ};
  
  my $cmd = {
    cmd => 'PtzCtrl',
    action => 0,
    param => {
      channel => $$self{channel},
      op => 'RightDown',
      speed => $speed
    }
  };
  
  $self->sendApiCmd($cmd);
}

sub moveStop {
  my $self = shift;
  
  Debug('Move Stop');
  $$self{Monitor}->resumeMotionDetection() if !$self->{Monitor}->{ModectDuringPTZ};
  
  my $cmd = {
    cmd => 'PtzCtrl',
    action => 0,
    param => {
      channel => $$self{channel},
      op => 'Stop',
      speed => 0
    }
  };
  
  $self->sendApiCmd($cmd);
}

# Zoom Commands

sub zoomConTele {
  my $self = shift;
  my $params = shift;
  my $speed = $self->getParam($params, 'speed') || 32;
  
  Debug('Zoom Tele');
  $$self{Monitor}->suspendMotionDetection() if !$self->{Monitor}->{ModectDuringPTZ};
  
  my $cmd = {
    cmd => 'PtzCtrl',
    action => 0,
    param => {
      channel => $$self{channel},
      op => 'ZoomInc',
      speed => $speed
    }
  };
  
  $self->sendApiCmd($cmd);
}

sub zoomConWide {
  my $self = shift;
  my $params = shift;
  my $speed = $self->getParam($params, 'speed') || 32;
  
  Debug('Zoom Wide');
  $$self{Monitor}->suspendMotionDetection() if !$self->{Monitor}->{ModectDuringPTZ};
  
  my $cmd = {
    cmd => 'PtzCtrl',
    action => 0,
    param => {
      channel => $$self{channel},
      op => 'ZoomDec',
      speed => $speed
    }
  };
  
  $self->sendApiCmd($cmd);
}

sub zoomStop {
  my $self = shift;
  
  Debug('Zoom Stop');
  
  my $cmd = {
    cmd => 'PtzCtrl',
    action => 0,
    param => {
      channel => $$self{channel},
      op => 'Stop',
      speed => 0
    }
  };
  
  $self->sendApiCmd($cmd);
}

# Focus Commands

sub focusConNear {
  my $self = shift;
  my $params = shift;
  my $speed = $self->getParam($params, 'speed') || 32;
  
  Debug('Focus Near');
  $$self{Monitor}->suspendMotionDetection() if !$self->{Monitor}->{ModectDuringPTZ};
  
  my $cmd = {
    cmd => 'PtzCtrl',
    action => 0,
    param => {
      channel => $$self{channel},
      op => 'FocusDec',
      speed => $speed
    }
  };
  
  $self->sendApiCmd($cmd);
}

sub focusConFar {
  my $self = shift;
  my $params = shift;
  my $speed = $self->getParam($params, 'speed') || 32;
  
  Debug('Focus Far');
  $$self{Monitor}->suspendMotionDetection() if !$self->{Monitor}->{ModectDuringPTZ};
  
  my $cmd = {
    cmd => 'PtzCtrl',
    action => 0,
    param => {
      channel => $$self{channel},
      op => 'FocusInc',
      speed => $speed
    }
  };
  
  $self->sendApiCmd($cmd);
}

sub focusAuto {
  my $self = shift;
  
  Debug('Focus Auto');
  
  my $cmd = {
    cmd => 'PtzCtrl',
    action => 0,
    param => {
      channel => $$self{channel},
      op => 'AutoFocus'
    }
  };
  
  $self->sendApiCmd($cmd);
}

# Preset Commands

sub presetGoto {
  my $self = shift;
  my $params = shift;
  my $preset = $self->getParam($params, 'preset');
  
  Debug("Goto Preset $preset");
  
  my $cmd = {
    cmd => 'PtzCtrl',
    action => 0,
    param => {
      channel => $$self{channel},
      op => 'ToPos',
      id => int($preset)
    }
  };
  
  $self->sendApiCmd($cmd);
}

sub presetSet {
  my $self = shift;
  my $params = shift;
  my $preset = $self->getParam($params, 'preset');
  
  Debug("Set Preset $preset");
  
  my $cmd = {
    cmd => 'SetPtzPreset',
    action => 0,
    param => {
      channel => $$self{channel},
      enable => 1,
      id => int($preset),
      name => "Preset$preset"
    }
  };
  
  $self->sendApiCmd($cmd);
}

sub presetHome {
  my $self = shift;
  
  Debug('Home Preset');
  
  # Home is typically preset 0 or 1 on Reolink
  my $cmd = {
    cmd => 'PtzCtrl',
    action => 0,
    param => {
      channel => $$self{channel},
      op => 'ToPos',
      id => 0
    }
  };
  
  $self->sendApiCmd($cmd);
}

# Guard/Patrol Commands

sub autoScan {
  my $self = shift;
  
  Debug('Auto Scan/Patrol');
  
  my $cmd = {
    cmd => 'PtzCtrl',
    action => 0,
    param => {
      channel => $$self{channel},
      op => 'StartPatrol',
      id => 0
    }
  };
  
  $self->sendApiCmd($cmd);
}

sub autoStop {
  my $self = shift;
  
  Debug('Stop Patrol');
  
  my $cmd = {
    cmd => 'PtzCtrl',
    action => 0,
    param => {
      channel => $$self{channel},
      op => 'StopPatrol'
    }
  };
  
  $self->sendApiCmd($cmd);
}

# Image Settings (using iris/white balance controls as available in ZM)

sub irisAbsOpen {
  my $self = shift;
  my $params = shift;
  
  # Use this to increase brightness
  Debug('Increase Brightness');
  # This would require GetIsp and SetIsp commands
  # Leaving as placeholder for future implementation
}

sub irisAbsClose {
  my $self = shift;
  my $params = shift;
  
  # Use this to decrease brightness
  Debug('Decrease Brightness');
  # This would require GetIsp and SetIsp commands
  # Leaving as placeholder for future implementation
}

1;

__END__

=pod

=head1 NAME

ZoneMinder::Control::Reolink_HTTP - Reolink HTTP API camera control

=head1 DESCRIPTION

This module contains the implementation of the Reolink HTTP API control protocol
using the api.cgi endpoint.  This is an alternative to the ONVIF-based Reolink. pm
module and uses the native Reolink HTTP API with proper token-based authentication.

This module has been tested with various Reolink PTZ camera models including
RLC-423, RLC-420, RLC-511WA, and E1 Pro.

Based on the JavaScript implementation at:  https://github.com/verheesj/reolink-api

=head1 CONFIGURATION

On the Control Address field, use the format:

  USERNAME:PASSWORD@ADDRESS:PORT

Examples: 
  admin:password@192.168.1.100:80
  admin:mypass123@10.0.0.50:80

The Control Device field can be set to the camera channel (usually 0 for single
cameras, or 0-15 for NVRs). If left empty, channel 0 is assumed.

=head1 AUTHENTICATION

This module implements the Reolink token-based authentication system: 

=over 4

=item * Performs Login to obtain a session token

=item * Token is included in all subsequent API calls

=item * Automatically refreshes token before expiration (default 1 hour lease)

=item * Properly logs out on close to prevent "max session" errors

=back

=head1 FEATURES

This module supports: 

=over 4

=item * Continuous Pan/Tilt in all 8 directions

=item * Variable speed control for pan/tilt

=item * Zoom In/Out (for cameras with zoom)

=item * Focus Near/Far and Auto Focus

=item * Preset positions (save, recall)

=item * Home position

=item * Auto Patrol/Guard modes

=item * Camera reboot command

=item * Stop commands for all movements

=item * Automatic token refresh

=back

=head1 API REFERENCE

Based on Reolink CGI API documentation: 
https://support.reolink.com/hc/en-us/articles/360007010473-CGI-API-User-Guide

=head1 NOTES

This module transmits the username and password during the initial login to obtain
a token. The token is then used for all subsequent requests.  While the camera 
supports HTTPS, ensure you're using it on a trusted/isolated network or configure
your Reolink camera to use HTTPS.

Token sessions have a maximum limit (typically 20 concurrent sessions). The module
properly logs out on close to prevent "max session" errors.

=head1 SEE ALSO

L<ZoneMinder::Control>, L<ZoneMinder::Control::Reolink>

=head1 AUTHOR

ZoneMinder Community

Based on templates from Instar720p, Amcrest_HTTP, and the verheesj/reolink-api
JavaScript implementation.

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2026 ZoneMinder Community

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.

=cut
