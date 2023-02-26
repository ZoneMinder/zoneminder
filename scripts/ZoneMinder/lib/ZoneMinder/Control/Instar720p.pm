# ==========================================================================
#
# ZoneMinder Instar 720p CGI Control Protocol Module
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

package ZoneMinder::Control::Instar720p;

use 5.006;
use strict;
use warnings;

use Time::HiRes qw( usleep );

require ZoneMinder::Base;
require ZoneMinder::Control;
require LWP::UserAgent;
use URI;

our @ISA = qw(ZoneMinder::Control);

our %CamParams = ();
our %CamServParams = ();

# ==========================================================================
#
# INSTAR HTTP CGI Control Protocol for 720p models command set
#
# On Control Address use the format :
#   USERNAME:PASSWORD@ADDRESS:PORT
#   eg : admin:@10.1.2.1:80
#        zoneminder:zonepass@10.0.100.1:40000
#
# Control Device is likely to be kept empty
#
# ==========================================================================

use ZoneMinder::Logger qw(:all);
use ZoneMinder::Config qw(:all);

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
  if ( $self->{Monitor}->{ControlAddress} !~ /^\w+:\/\// ) {
    # Has no scheme at the beginning, so won't parse as a URI
    $self->{Monitor}->{ControlAddress} = 'http://'.$self->{Monitor}->{ControlAddress};
  }
  my $uri = URI->new($self->{Monitor}->{ControlAddress});

  $self->{ua} = LWP::UserAgent->new;
  $self->{ua}->agent('ZoneMinder Control Agent/'.ZoneMinder::Base::ZM_VERSION);
  my ( $username, $password );
  my $realm = 'Login to ' . $self->{Monitor}->{ControlDevice};
  if ( $self->{Monitor}->{ControlAddress} ) {
    ( $username, $password ) = $uri->authority() =~ /^(.*):(.*)@(.*)$/;

    $$self{address} = $uri->host_port();
    $self->{ua}->credentials($uri->host_port(), $realm, $username, $password);
    # Testing seems to show that we need the username/password in each url as well as credentials
    $$self{base_url} = $uri->canonical();
    Debug('Using initial credentials for '.$uri->host_port().", $realm, $username, $password, base_url: $$self{base_url} auth:".$uri->authority());
  }

  # Detect REALM wiresharked from webinterface. maybe its easier somehow
  my $res = $self->{ua}->get($$self{base_url}.'param.cgi?cmd=get_instar_guest&-index=13&cmd=get_instar_guest&-index=47&cmd=get_instar_guest&-index=48');
  if ( $res->is_success ) {
    $self->{state} = 'open';
    return;
  }

  if ( $res->status_line() eq '401 Unauthorized' ) {

    my $headers = $res->headers();
    foreach my $k ( keys %$headers ) {
      Debug("Initial Header $k => $$headers{$k}");
    }

    if ( $$headers{'www-authenticate'} ) {
      my ( $auth, $tokens ) = $$headers{'www-authenticate'} =~ /^(\w+)\s+(.*)$/;
      if ( $tokens =~ /realm="([^"]+)"/i ) {
        if ( $realm ne $1 ) {
          $realm = $1;
          Debug("Changing REALM to ($realm)");
          $self->{ua}->credentials($$self{address}, $realm, $username, $password);
          $res = $self->{ua}->get($$self{base_url}.'param.cgi?cmd=getuserinfo');
          if ( $res->is_success() ) {
            $self->{state} = 'open';
            return;
          } elsif ( $res->status_line eq '400 Bad Request' ) {
          # In testing, this second request fails with Bad Request, I assume because we didn't actually give it a command.
            $self->{state} = 'open';
            return;
          } else {
            Error('Authentication still failed after updating REALM: ' . $res->status_line);
            $headers = $res->headers();
            foreach my $k ( keys %$headers ) {
              Debug("Header $k => $$headers{$k}");
            }  # end foreach
          }
        } else {
          Error('Authentication failed, not a REALM problem');
        }
      } else {
        Error('Failed to match realm in tokens');
      } # end if
    } else {
      Debug('No headers line');
    } # end if headers
  } else {
    Error("Failed to get $$self{base_url}param.cgi?cmd=get_instar_guest&-index=13&cmd=get_instar_guest&-index=47&cmd=get_instar_guest&-index=    48 ".$res->status_line());

  } # end if $res->status_line() eq '401 Unauthorized'

  $self->{state} = 'closed';
}

sub close {
  my $self = shift;
  $self->{state} = 'closed';
}

sub sendCmd {
  my $self = shift;
  my $cmd = shift;
  my $result = undef;
  $self->printMsg($cmd, 'Tx');
  my $res = $self->{ua}->get($$self{base_url}.$cmd);
  if ( $res->is_success ) {
    $result = !undef;
    # Command to camera appears successful, write Info item to log
    Info('Camera control: \''.$res->status_line().'\' for URL '.$$self{base_url}.$cmd);
  } else {
    # Try again
    $res = $self->{ua}->get($$self{base_url}.$cmd);
    if ( $res->is_success ) {
      # Command to camera appears successful, write Info item to log
      Info('Camera control 2: \''.$res->status_line().'\' for URL '.$$self{base_url}.$cmd);
    } else {
      Error('Camera control command FAILED: \''.$res->status_line().'\' for URL '.$$self{base_url}.$cmd);
      $res = $self->{ua}->get('http://'.$self->{Monitor}->{ControlAddress}.'/'.$cmd);
    }
  }

  return $result;
}

# Reading parameters could maybe included into the sendCmd function but this was simpler to copy/paste here
sub getCamParams {
  my $self = shift;
  my $cmd = "param.cgi?cmd=getimageattr";
#  my $req = $self->sendCmd( $cmd );
  my $res = $self->{ua}->get($$self{base_url}.$cmd);
  if ( $res->is_success ) {
    # Parse results setting values in %FCParams
    my $content = $res->decoded_content;
    while ($content =~ s/var\s+([^=]+)=([^;]+);//ms) {
      $CamParams{$1} = $2;
    }
  }
  else {
    Error( "Error check failed:'".$res->status_line()."'" );
    }
}

# Not used anywhere right now but for future usage.
sub getServerInfo {
  my $self = shift;
  my $cmd = "param.cgi?cmd=getserverinfo";
#  my $req = $self->sendCmd( $cmd );
  my $res = $self->{ua}->get($$self{base_url}.$cmd);
  if ( $res->is_success ) {
    # Parse results setting values in %FCParams
    my $content = $res->decoded_content;
    while ($content =~ s/var\s+([^=]+)=([^;]+);//ms) {
      $CamServParams{$1} = $2;
    }
  }
  else {
    Error( "Error check failed:'".$res->status_line()."'" );
    }
}

sub reboot {
  my $self = shift;
  $self->sendCmd('param.cgi?cmd=sysreboot');
}

# Be careful. This option hasn't been tested due to obvious reasons!
sub reset {
  my $self = shift;
  $self->sendCmd('sysreset.cgi');
}

# Single step buttons are not included into the web gui (yet?).
sub up {
  my $self = shift;
  Debug('Move Up');
  $$self{Monitor}->suspendMotionDetection() if !$self->{Monitor}->{ModectDuringPTZ};
  $self->sendCmd('ptzctrl.cgi?-step=1&-act=up');
}

sub down {
  my $self = shift;
  Debug('Move Down');
  $$self{Monitor}->suspendMotionDetection() if !$self->{Monitor}->{ModectDuringPTZ};
  $self->sendCmd('ptzctrl.cgi?-step=1&-act=down');
}

sub left {
  my $self = shift;
  Debug('Move Left');
  $$self{Monitor}->suspendMotionDetection() if !$self->{Monitor}->{ModectDuringPTZ};
  $self->sendCmd('ptzctrl.cgi?-step=1&-act=left');
}

sub right {
  my $self = shift;
  Debug('Move Right');
  $$self{Monitor}->suspendMotionDetection() if !$self->{Monitor}->{ModectDuringPTZ};
  $self->sendCmd('ptzctrl.cgi?-step=1&-act=right');
}

# Continuous movement functions
sub moveConUp {
  my $self = shift;
  Debug('Tilt Up');
  $$self{Monitor}->suspendMotionDetection() if !$self->{Monitor}->{ModectDuringPTZ};
  $self->sendCmd('ptzctrl.cgi?-step=0&-act=up');
}

sub moveConDown {
  my $self = shift;
  Debug('Tilt Down');
  $$self{Monitor}->suspendMotionDetection() if !$self->{Monitor}->{ModectDuringPTZ};
  $self->sendCmd('ptzctrl.cgi?-step=0&-act=down');
}

sub moveConLeft {
  my $self = shift;
  Debug('Pan Left');
  $$self{Monitor}->suspendMotionDetection() if !$self->{Monitor}->{ModectDuringPTZ};
  $self->sendCmd('ptzctrl.cgi?-step=0&-act=left');
}

sub moveConRight {
  my $self = shift;
  Debug('Pan Right');
  $$self{Monitor}->suspendMotionDetection() if !$self->{Monitor}->{ModectDuringPTZ};
  $self->sendCmd('ptzctrl.cgi?-step=0&-act=right');
}

sub horizontalPatrol {
  my $self = shift;
  Debug('Pan Right');
  $$self{Monitor}->suspendMotionDetection() if !$self->{Monitor}->{ModectDuringPTZ};
  $self->sendCmd('ptzctrl.cgi?-step=0&-act=hscan');
}

sub verticalPatrol {
  my $self = shift;
  Debug('Pan Right');
  $$self{Monitor}->suspendMotionDetection() if !$self->{Monitor}->{ModectDuringPTZ};
  $self->sendCmd('ptzctrl.cgi?-step=0&-act=vscan');
}

sub moveConUpRight {
  my $self = shift;
  Debug('Move Diagonally Up Right');
  $$self{Monitor}->suspendMotionDetection() if !$self->{Monitor}->{ModectDuringPTZ};
  $self->sendCmd('ptzctrl.cgi?-step=0&-act=up');
  $self->sendCmd('ptzctrl.cgi?-step=0&-act=right');
}

sub moveConDownRight {
  my $self = shift;
  Debug('Move Diagonally Down Right');
  $$self{Monitor}->suspendMotionDetection() if !$self->{Monitor}->{ModectDuringPTZ};
  $self->sendCmd('ptzctrl.cgi?-step=0&-act=down');
  $self->sendCmd('ptzctrl.cgi?-step=0&-act=right');
}

sub moveConUpLeft {
  my $self = shift;
  Debug('Move Diagonally Up Left');
  $$self{Monitor}->suspendMotionDetection() if !$self->{Monitor}->{ModectDuringPTZ};
  $self->sendCmd('ptzctrl.cgi?-step=0&-act=up');
  $self->sendCmd('ptzctrl.cgi?-step=0&-act=left');
}

sub moveConDownLeft {
  my $self = shift;
  Debug('Move Diagonally Down Left');
  $$self{Monitor}->suspendMotionDetection() if !$self->{Monitor}->{ModectDuringPTZ};
  $self->sendCmd('ptzctrl.cgi?-step=0&-act=down');
  $self->sendCmd('ptzctrl.cgi?-step=0&-act=left');
}

sub moveStop {
  my $self = shift;
  Debug('Pan/Tilt stop');
  $$self{Monitor}->suspendMotionDetection() if !$self->{Monitor}->{ModectDuringPTZ};
  $self->sendCmd('ptzctrl.cgi?-step=0&-act=stop');
}

sub presetHome {
  my $self = shift;
  Debug('Home Preset');
  $$self{Monitor}->suspendMotionDetection() if !$self->{Monitor}->{ModectDuringPTZ};
  $self->sendCmd('ptzctrl.cgi?-step=0&-act=home');
}

# Slight modification cause the camera counts the preset places differently than ZM
sub presetGoto {
  my $self = shift;
  my $params = shift;
  my $preset = $self->getParam($params, 'preset');
  $preset = $preset-1;
  Debug("Go To Preset $preset");
  $self->sendCmd('preset.cgi?-act=goto&-status=1&-number='.$preset);
}

sub presetSet {
  my $self = shift;
  my $params = shift;
  my $preset = $self->getParam($params, 'preset');
  $preset = $preset-1;
  Debug('Set Preset');
  $self->sendCmd('preset.cgi?-act=set&-status=1&-number='.$preset);
}

sub presetClear {
  my $self = shift;
  my $params = shift;
  my $preset = $self->getParam($params, 'preset');
  $preset = $preset-1;
  Debug('Clear Preset');
  $self->sendCmd('preset.cgi?-act=set&-status=0&-number='.$preset);
}

# Increase Brightness  [0-6]
sub irisAbsOpen {
#  Error('asa ');
  my $self = shift;
  my $params = shift;
  $self->getCamParams() unless($CamParams{'brightness'});
  my $step = $self->getParam( $params, 'step' );
  Info('asa '.$step);
  $CamParams{'brightness'} += $step;
  $CamParams{'brightness'} = 6 if ($CamParams{'brightness'} > 6);
  Info(%CamParams);
  Debug( "Increase Brightness" );
  $self->sendCmd("param.cgi?cmd=setimageattr&-brightness=".$CamParams{'brightness'});
}

# Decrease Brightness
sub irisAbsClose {
  my $self = shift;
  my $params = shift;
  $self->getCamParams() unless($CamParams{'brightness'});
  my $step = $self->getParam( $params, 'step' );
   $CamParams{'brightness'} -= $step;
  $CamParams{'brightness'} = 0 if ($CamParams{'brightness'} < 0);
  Debug( "Decrease Brightness" );
  $self->sendCmd("param.cgi?cmd=setimageattr&-brightness=".$CamParams{'brightness'});
}

# Increase Contrast  [0-7]
sub whiteAbsIn {
  my $self = shift;
  my $params = shift;
  $self->getCamParams() unless($CamParams{'contrast'});
  my $step = $self->getParam( $params, 'step' );
  $CamParams{'contrast'} += $step;
  $CamParams{'contrast'} = 7 if ($CamParams{'contrast'} > 7);
  Debug( "Increase Contrast" );
  $self->sendCmd("param.cgi?cmd=setimageattr&-contrast=".$CamParams{'contrast'});
}

# Decrease Contrast
sub whiteAbsOut {
  my $self = shift;
  my $params = shift;
  $self->getCamParams() unless($CamParams{'contrast'});
  my $step = $self->getParam( $params, 'step' );
  $CamParams{'contrast'} -= $step;
  $CamParams{'contrast'} = 0 if ($CamParams{'contrast'} < 0);
  Debug( "Decrease Contrast" );
  $self->sendCmd("param.cgi?cmd=setimageattr&-contrast=".$CamParams{'contrast'});
}

# Hue, sharpness and saturation are not included in the web gui yet.
#TODO Saturation param.cgi?cmd=setimageattr&-saturation=44 [0-6]
sub satIncrease {
  my $self = shift;
  my $params = shift;
  $self->getCamParams() unless($CamParams{'saturation'});
  my $step = $self->getParam( $params, 'step' );
  $CamParams{'saturation'} += $step;
  $CamParams{'saturation'} = 6 if ($CamParams{'saturation'} > 6);
  Debug( "Increase Saturation" );
  $self->sendCmd("param.cgi?cmd=setimageattr&-saturation=".$CamParams{'saturation'});
}

sub satDecrease {
  my $self = shift;
  my $params = shift;
  $self->getCamParams() unless($CamParams{'saturation'});
  my $step = $self->getParam( $params, 'step' );
  $CamParams{'saturation'} -= $step;
  $CamParams{'saturation'} = 0 if ($CamParams{'saturation'} < 0);
  Debug( "Decrease Saturation" );
  $self->sendCmd("param.cgi?cmd=setimageattr&-saturation=".$CamParams{'saturation'});
}
#TODO Sharpness param.cgi?cmd=setimageattr&-sharpness=37 [0-100]
sub sharpIncrease {
  my $self = shift;
  my $params = shift;
  $self->getCamParams() unless($CamParams{'sharpness'});
  my $step = $self->getParam( $params, 'step' );
  $CamParams{'sharpness'} += $step;
  $CamParams{'sharpness'} = 4 if ($CamParams{'sharpness'} > 4);
  Debug( "Increase Sharpness" );
  $self->sendCmd("param.cgi?cmd=setimageattr&-sharpness=".$CamParams{'sharpness'});
}

sub sharpDecrease {
  my $self = shift;
  my $params = shift;
  $self->getCamParams() unless($CamParams{'sharpness'});
  my $step = $self->getParam( $params, 'step' );
  $CamParams{'sharpness'} -= $step;
  $CamParams{'sharpness'} = 0 if ($CamParams{'sharpness'} < 0);
  Debug( "Decrease Sharpness" );
  $self->sendCmd("param.cgi?cmd=setimageattr&-sharpness=".$CamParams{'sharpness'});
}

#TODO Hue param.cgi?cmd=setimageattr&-hue=37 [0-255]
sub hueIncrease {
  my $self = shift;
  my $params = shift;
  $self->getCamParams() unless($CamParams{'hue'});
  my $step = $self->getParam( $params, 'step' );
  $CamParams{'hue'} += $step;
  $CamParams{'hue'} = 255 if ($CamParams{'hue'} > 255);
  Debug( "Increase Hue" );
  $self->sendCmd("param.cgi?cmd=setimageattr&-hue=".$CamParams{'hue'});
}

sub hueDecrease {
  my $self = shift;
  my $params = shift;
  $self->getCamParams() unless($CamParams{'hue'});
  my $step = $self->getParam( $params, 'step' );
  $CamParams{'hue'} -= $step;
  $CamParams{'hue'} = 0 if ($CamParams{'hue'} < 0);
  Debug( "Decrease Hue" );
  $self->sendCmd("param.cgi?cmd=setimageattr&-hue=".$CamParams{'hue'});
}

# No zoom in the 720ps as far as I know, unfortunately
#sub zoomConTele {
#  my $self = shift;
#  Debug('Zoom continuous tele');
#  $$self{Monitor}->suspendMotionDetection() if !$self->{Monitor}->{ModectDuringPTZ};
#  $$self{LastCmd} = 'code=ZoomTele&channel=0&arg1=0&arg2=0&arg3=0&arg4=0';
#  $self->sendCmd('cgi-bin/ptz.cgi?action=start&'.$$self{LastCmd});
#}
#
#sub zoomConWide {
#  my $self = shift;
#  Debug('Zoom continuous wide');
#  $$self{Monitor}->suspendMotionDetection() if !$self->{Monitor}->{ModectDuringPTZ};
#  $$self{LastCmd} = 'code=ZoomWide&channel=0&arg1=0&arg2=0&arg3=0&arg4=0';
#  $self->sendCmd('cgi-bin/ptz.cgi?action=start&'.$$self{LastCmd});
#}

1;

__END__

=pod

=head1 NAME

ZoneMinder::Control::Instar720p - Instar CGI 720p camera control

=head1 DESCRIPTION

This module contains the implementation of the CGI 720p Instar Cameras.
Build with the model IN-7011 HD, which - thank you Instar - ist no HD-Camera.

NOTE: This module implements interaction with the camera in clear text.

The login and password are transmitted from ZM to the camera in clear text,
and as such, this module should be used ONLY on a blind LAN implementation
where interception of the packets is very low risk.


param.cgi?cmd=getimageattr
var brightness="6"; var saturation="3"; var sharpness="4"; var contrast="4"; var hue="0"; var wdr="on"; var night="on"; var shutter="0"; var flip="off"; var mirror="off"; var gc="100"; var noise="on";

=head1 SEE ALSO

https://wiki.instar.com/en/Advanced_User/CGI_Commands/

=head1 AUTHORS

github at beeit dot de

Templates used:
Amcrest_HTTP
DericamP2

So thx to these authors, too

=head1 COPYRIGHT AND LICENSE

(C) 2022 github at beeit dot de

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.

=cut
