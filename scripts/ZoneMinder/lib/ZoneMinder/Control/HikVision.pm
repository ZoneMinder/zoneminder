# ==========================================================================
#
# ZoneMinder HikVision Control Protocol Module
# Copyright (C) 2016 Terry Sanders
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
# This module contains an implementation of the HikVision ISAPI camera control
# protocol
#
package ZoneMinder::Control::HikVision;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Control;

our @ISA = qw(ZoneMinder::Control);

# ==========================================================================
#
# HiKVision ISAPI Control Protocol
#
# Set the following:
# ControlAddress: username:password@camera_webaddress:port
# ControlDevice: IP Camera Model
#
# ==========================================================================

use ZoneMinder::Logger qw(:all);

use Time::HiRes qw( usleep );

use LWP::UserAgent;
use HTTP::Cookies;

my $ChannelID = 1;              # Usually...
my $DefaultFocusSpeed = 50;     # Should be between 1 and 100
my $DefaultIrisSpeed = 50;      # Should be between 1 and 100
my ($user,$pass,$host,$port);

sub open {
  my $self = shift;
  $self->loadMonitor();
  #
  # Create a UserAgent for the requests
  #
  $self->{UA} = LWP::UserAgent->new();
  $self->{UA}->cookie_jar( {} );
  #
  # Extract the username/password host/port from ControlAddress
  #
  if ( $self->{Monitor}{ControlAddress} =~ /^([^:]+):([^@]+)@(.+)/ ) { # user:pass@host...
    $user = $1;
    $pass = $2;
    $host = $3;
  } elsif ( $self->{Monitor}{ControlAddress} =~ /^([^@]+)@(.+)/ ) { # user@host...
    $user = $1;
    $host = $2;
  } else { # Just a host
    $host = $self->{Monitor}{ControlAddress};
  }
  # Check if it is a host and port or just a host
  if ( $host =~ /([^:]+):(.+)/ ) {
    $host = $1;
    $port = $2;
  } else {
    $port = 80;
  }
  # Save the credentials
  if ( defined($user) ) {
    $self->{UA}->credentials("$host:$port", $self->{Monitor}{ControlDevice}, $user, $pass);
  }
  # Save the base url
  $self->{BaseURL} = "http://$host:$port";
}

sub PutCmd {
  my $self = shift;
  my $cmd = shift;
  my $content = shift;
  my $req = HTTP::Request->new(PUT => $self->{BaseURL}.'/'.$cmd);
  if ( defined($content) ) {
    $req->content_type('application/x-www-form-urlencoded; charset=UTF-8');
    $req->content('<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $content);
  }
  my $res = $self->{UA}->request($req);
  unless( $res->is_success ) {
    #
    # The camera timeouts connections at short intervals. When this
    # happens the user agent connects again and uses the same auth tokens.
    # The camera rejects this and asks for another token but the UserAgent
    # just gives up. Because of this I try the request again and it should
    # succeed the second time if the credentials are correct.
    #
    if ( $res->code == 401 ) {
      $res = $self->{UA}->request($req);
      unless( $res->is_success ) {
        #
        # It has failed authentication. The odds are
        # that the user has set some parameter incorrectly
        # so check the realm against the ControlDevice
        # entry and send a message if different
        #
        my $auth = $res->headers->www_authenticate;
        foreach (split(/\s*,\s*/,$auth)) {
          if ( $_ =~ /^realm\s*=\s*"([^"]+)"/i ) {
            if ( $self->{Monitor}{ControlDevice} ne $1 ) {
              Warning("Control Device appears to be incorrect.
                Control Device should be set to \"$1\".
                Control Device currently set to \"$self->{Monitor}{ControlDevice}\".");
              $self->{Monitor}{ControlDevice} = $1;
              $self->{UA}->credentials("$host:$port", $self->{Monitor}{ControlDevice}, $user, $pass);
              return PutCmd($self,$cmd,$content);
            }
          }
        }
        #
        # Check for username/password
        #
        if ( $self->{Monitor}{ControlAddress} =~ /.+:(.+)@.+/ ) {
          Info('Check username/password is correct');
        } elsif ( $self->{Monitor}{ControlAddress} =~ /^[^:]+@.+/ ) {
          Info('No password in Control Address. Should there be one?');
        } elsif ( $self->{Monitor}{ControlAddress} =~ /^:.+@.+/ ) {
          Info('Password but no username in Control Address.');
        } else {
          Info('Missing username and password in Control Address.');
        }
        Fatal($res->status_line);
      }
    } else {
      Fatal($res->status_line);
    }
  } # end unless res->is_success
} # end sub putCmd
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
sub reset {
  my $self = shift;

  $self->PutCmd('ISAPI/System/reboot');
}

1;
__END__
