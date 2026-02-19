# ==========================================================================
#
# Zoneminder Tapo C520WS Control Protocol Module
#
# Based on ZoneMinder ONVIF Control Protocol Module
# Based on the Netcat onvif script by Andrew Bauer (knnniggett@users.sourceforge.net)
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
# There are perl modules required for this script
# Perl modules can be installed using cpan
# To install perl modules using cpan requires the make package so (as root)
#	apt install make
#	cpan
#		o conf make /usr/bin/make
#		o conf commit
# Make sure you have the current cpan version by running (as root)
#	cpan CPAN
# And make sure your changes will be persistent by running
#	cpan YAML
#
# When you run cpan, you will see a recommendation 
# to install a better logging module
# which is recommended but not required (your call)
#	Log::Log4perl
#		cpan Log::Log4perl
#
# This script requires the following perl modules
# If the modules are already installed 
# there is no problem running the cpan installer
# It will tell you when you run the cpan command if it already exists
#	MIME::Base64
#		cpan MIME::Base64
#	Digest::SHA
#		cpan Digest::SHA
#	Data::Dumper
#		cpan Data::Dumper
#	URI
#		cpan URI
#	DateTime
#		cpan DateTime
#	Time::HiRes
#		cpan Time::HiRes
#	LWP::UserAgent
#		cpan LWP::UserAgent
#
# This script is placed into (as of 2023 with Zoneminder 1.36 on Debian 11
#    /usr/share/perl5/ZoneMinder/Control
# after which you restart Zoneminder to initialize (load) this new script
#
# For debugging new scripts, view the log in the Zoneminder GUI
# or (since the Zoneminder log goes off screen quickly)
# Look in /var/log/zm at the control log in file zmcontrol_{monitorID}.log
#
# ==========================================================================
#
# This module contains the implementation of onvif protocol
#
package ZoneMinder::Control::TapoC520WS_ONVIF;

use 5.006;
use strict;
use warnings;
use MIME::Base64;
use Digest::SHA;
use DateTime;
use URI;
use URI::Escape;
use Data::Dumper;

require ZoneMinder::Base;
require ZoneMinder::Control;

our @ISA = qw(ZoneMinder::Control);

our %CamParams = ();

my ( $controlUri, $scheme );

# ==========================================================================
#
# This script sends ONVIF compliant commands and may work with other cameras
#
# Configuration options (Source->Control tab)
# - Control Type: ONVIF
# - Control Device: prof0 - this is dependant on camera. It maybe required to sniff the traffic using Wireshark to find this out. If left empty value of "000" will be used.
# - Control Address: <scheme>://[<user>:<password>@]<ip_or_host_of_onvif_enabled_camera>[:port][control_uri]
# - Auto Stop Timeout: 1.00 - how long shold the camera move for when move command is issued. Value of 1.00 means 1s.
# - Track Motion: NOT IMPLEMENTED - this suppose to be a feature for automatic camera scrolling (moving).
# - Track Delay: NOT IMPLEMENTED
# - Return Location: Home|Preset 1 - NOT IMPLEMENTED
#
# Absolute minimum required supported "Control Address" would be:
#   - 192.168.1.199
# This will use the following defaults:
#   - port: 80
#   - Control Device: 000
#   - Control URI: /onvif/PTZ
#   - No authentication
#   - No Auto Stop Timeout (on movement command the camera will keep moving until it reaches it's edge)
#
# Example Control Address values:
#   - http://user:password@192.168.1.199:888/onvif/device_control :Connect to camera at IP: 192.168.1.199 on port 888 with "username" and "password" credentials using /onvif/device_control URI
#   - user:password@192.168.1.199                                 :Connect to camera at IP: 192.168.1.199 on default port 80 with "username" and "password" credentials using default /onvif/PTZ URI
#   - 192.168.1.199                                               :Connect to camera at IP: 192.168.1.199 without any authentication and use the default /onvif/PTZ URI over HTTP.
#
# ==========================================================================

use ZoneMinder::Logger qw(:all);
use ZoneMinder::Config qw(:all);

use Time::HiRes qw( usleep );
use LWP::UserAgent;
use IO::Socket::SSL;

my $profileToken;

sub open {
  my $self = shift;

  $self->loadMonitor();

  $profileToken = $self->{Monitor}->{ControlDevice};
  if (!$profileToken) { $profileToken = '000'; }

  $self->parseControlAddress($self->{Monitor}->{ControlAddress});

  $self->{ua} = LWP::UserAgent->new;
  # Try with SSL verification enabled first
  $self->{ua}->ssl_opts(
      verify_hostname => 1,
      SSL_verify_mode => IO::Socket::SSL::SSL_VERIFY_PEER,
    );
  $self->{ua}->agent('ZoneMinder Control Agent/'.ZoneMinder::Base::ZM_VERSION);

  $self->{state} = 'open';
  $self->{ssl_verified} = 1;  # Track if we're using SSL verification
}

sub parseControlAddress {
  my $self = shift;
  my $controlAddress = shift;

  #make sure url start with a scheme
  if ( $controlAddress !~ m'^https?://') {
    $controlAddress = 'http://'.$controlAddress;
  }

  my $url = URI->new($controlAddress);
  $$self{scheme} = $url->scheme;
  @$self{'username','password'} = split /:/, $url->userinfo if $url->userinfo;
  $$self{password} = URI::Escape::uri_unescape($$self{password});

  #If we have no explicitly defined port
  $$self{port} = $url->port ? $url->port : $url->default_port;
  $$self{path} = $url->path ? $url->path : '/onvif/PTZ';

  $$self{address} = $url->host;
  $$self{BaseURL} = $$self{scheme}.'://'.$$self{address}.':'.$$self{port};
  Debug("Using address $$self{BaseURL}$$self{path}, username $$self{username}, password $$self{password}");
}

sub digestBase64 {
  my ($nonce, $date, $password) = @_;
  my $shaGenerator = Digest::SHA->new(1);
  $shaGenerator->add($nonce . $date . $password);
  return encode_base64($shaGenerator->digest, '');
}

sub authentificationHeader {
  my ($username, $password) = @_;
  my @set = ('0' ..'9', 'A' .. 'Z', 'a' .. 'z');
  my $nonce = join '' => map $set[rand @set], 1 .. 20;

  my $nonceBase64 = encode_base64($nonce, '');
  my $currentDate = DateTime->now()->iso8601().'Z';

  return '
<s:Header>
  <Security s:mustUnderstand="1" xmlns="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
    <UsernameToken xmlns="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
      <Username>' . $username . '</Username>
      <Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordDigest">' . digestBase64($nonce, $currentDate, $password) . '</Password>
      <Nonce EncodingType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary">' . $nonceBase64 . '</Nonce>
      <Created xmlns="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">' . $currentDate . '</Created>
    </UsernameToken>
  </Security>
</s:Header>';
}

sub sendCmd {
  my $self = shift;
  my $cmd = shift;
  my $msg_body = shift;
  my $content_type = shift;
  my $result = undef;

  my $msg = '
    <s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope">'.
        ($$self{username} ? authentificationHeader(@$self{'username','password'}) : '') .
    $msg_body . '
    </s:Envelope>';

  $self->printMsg($cmd, 'Tx');

  my $server_endpoint = $$self{BaseURL}.$$self{path};
  my $req = HTTP::Request->new(POST => $server_endpoint);
  $req->header('content-type' => $content_type);
  $req->header('Host' => $$self{address} . ':' . $$self{port});
  $req->header('content-length' => length($msg));
  $req->header('accept-encoding' => 'gzip, deflate');
  $req->header('connection' => 'Close');
  $req->content($msg);

  my $res = $self->{ua}->request($req);
  
  # If SSL verification failed, retry without verification
  if (!$res->is_success && $self->{ssl_verified} && $res->status_line =~ /SSL|certificate|verify/i) {
    Warning("SSL certificate verification failed for $server_endpoint (" . $res->status_line . "), retrying without verification");
    $self->{ua}->ssl_opts(
      verify_hostname => 0,
      SSL_verify_mode => IO::Socket::SSL::SSL_VERIFY_NONE,
      SSL_hostname => ''
    );
    $self->{ssl_verified} = 0;
    $res = $self->{ua}->request($req);
  }
  
  if ( $res->is_success ) {
    Debug("Success sending PTZ command, :".$res->content);
    $result = !undef;
  } else {
    Error("After sending PTZ command, camera returned the following error:'".$res->status_line()."'\nMSG:$msg\nResponse:".$res->content);
  }
  return $result;
}

sub getCamParams {
  my $self = shift;
  my $msg = '
<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope">
  <s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
    <GetImagingSettings xmlns="http://www.onvif.org/ver20/imaging/wsdl">
      <VideoSourceToken>000</VideoSourceToken>
    </GetImagingSettings>
  </s:Body>
</s:Envelope>';

  my $server_endpoint = $$self{BaseURL}.'/onvif/imaging';
  my $req = HTTP::Request->new(POST => $server_endpoint);
  $req->header('content-type' => 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/imaging/wsdl/GetImagingSettings"');
  $req->header('Host' => $$self{address} . ':' . $$self{port});
  $req->header('content-length' => length($msg));
  $req->header('accept-encoding' => 'gzip, deflate');
  $req->header('connection' => 'Close');
  $req->content($msg);

  my $res = $self->{ua}->request($req);

  # If SSL verification failed, retry without verification
  if (!$res->is_success && $self->{ssl_verified} && $res->status_line =~ /SSL|certificate|verify/i) {
    Warning("SSL certificate verification failed for $server_endpoint (" . $res->status_line . "), retrying without verification");
    $self->{ua}->ssl_opts(
      verify_hostname => 0,
      SSL_verify_mode => IO::Socket::SSL::SSL_VERIFY_NONE,
      SSL_hostname => ''
    );
    $self->{ssl_verified} = 0;
    $res = $self->{ua}->request($req);
  }

  if ( $res->is_success ) {
    # We should really use an xml or soap library to parse the xml tags
    my $content = $res->decoded_content;

    if ( $content =~ /.*<tt:(Brightness)>(.+)<\/tt:Brightness>.*/ ) {
      $CamParams{$1} = $2;
    }
    if ( $content =~ /.*<tt:(Contrast)>(.+)<\/tt:Contrast>.*/ ) {
      $CamParams{$1} = $2;
    }
  } else {
    Error("Unable to retrieve camera image settings:'".$res->status_line()."'");
  }
}

#autoStop
#This makes use of the ZoneMinder Auto Stop Timeout on the Control Tab
sub autoStop {
  my $self = shift;
  my $autostop = shift;
  my $iszoom = shift;

  if ( $autostop ) {
    my $duration = $autostop * $self->{Monitor}{AutoStopTimeout};
    $duration = ($duration < 1000) ? $duration * 1000 : int($duration/1000);
    # Change from microseconds to milliseconds or seconds to milliseconds
    Debug("Calculate duration $duration from autostop($autostop) and AutoStopTimeout ".$self->{Monitor}{AutoStopTimeout});
    my $cmd = $controlUri;
    my $msg_body;
    if ( $iszoom) {
      $msg_body = '
<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
  <Stop xmlns="http://www.onvif.org/ver20/ptz/wsdl">
    <ProfileToken>'.$profileToken.'</ProfileToken>
    <PanTilt>false</PanTilt>
    <Zoom>true</Zoom>
  </Stop>
</s:Body>';
    } else {
      $msg_body = '
<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
  <Stop xmlns="http://www.onvif.org/ver20/ptz/wsdl">
    <ProfileToken>'.$profileToken.'</ProfileToken>
    <PanTilt>true</PanTilt>
    <Zoom>false</Zoom>
  </Stop>
</s:Body>';
    }

    my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/ptz/wsdl/ContinuousMove"';
    usleep($duration);
    $self->sendCmd($cmd, $msg_body, $content_type);
  }
}

# Reboot
sub reboot {
  my $self = shift;
  my $cmd = '';
  my $msg_body = '
<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
  <SystemReboot xmlns="http://www.onvif.org/ver10/device/wsdl"/>
</s:Body>
';

  my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver10/device/wsdl/SystemReboot"';
  $self->sendCmd($cmd, $msg_body, $content_type);
}

# Reset(Reboot) the Camera
sub reset {
  my $self = shift;
  my $cmd = '';
  my $msg_body = '
<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
  <SystemReboot xmlns="http://www.onvif.org/ver10/device/wsdl"/>
</s:Body>
';

  my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver10/device/wsdl/SystemReboot"';
  $self->sendCmd($cmd, $msg_body, $content_type);
}

sub moveMap {
  my $self = shift;
  my $params = shift;
  my $x = $self->getParam($params,'xcoord');
  my $y = $self->getParam($params,'ycoord');
  Debug("Move map to $x x $y");

  my $cmd = $controlUri;
  my $msg_body ='
<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
  <AbsoluteMove xmlns="http://www.onvif.org/ver20/ptz/wsdl">
    <ProfileToken>' . $profileToken . '</ProfileToken>
    <Position>
      <PanTilt x="'.$x.'" y="'.$y.'" xmlns="http://www.onvif.org/ver10/schema"/>
    </Position>
    <Speed>
      <Zoom x="1" xmlns="http://www.onvif.org/ver10/schema"/>
    </Speed>
  </AbsoluteMove>
</s:Body>';
  my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/ptz/wsdl/ContinuousMove"';
  $self->sendCmd($cmd, $msg_body, $content_type);
}

sub moveRel {
  my $self = shift;
  my $params = shift;
  my $x = $self->getParam($params,'xcoord');
  my $speed = $self->getParam($params,'speed');
  my $y = $self->getParam($params,'ycoord');
  Debug("Move rel to $x x $y");

  my $cmd = $controlUri;
  my $msg_body ='
<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
  <RelativeMove xmlns="http://www.onvif.org/ver20/ptz/wsdl">
    <ProfileToken>' . $profileToken . '</ProfileToken>
    <Translation>
      <PanTilt x="'.$x.'" y="'.$y.'" xmlns="http://www.onvif.org/ver10/schema" space="http://www.onvif.org/ver10/tptz/PanTiltSpaces/PositionGenericSpace"/>
      <Zoom x="1"/>
    </Translation>
  </RelativeMove>
</s:Body>';
  my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/ptz/wsdl/ContinuousMove"';
  $self->sendCmd($cmd, $msg_body, $content_type);
}

sub moveCamera {
  my $type = shift;
  my $x = shift;
  my $y = shift;
  my $msg_move_body = '';

  if ( $type eq 'move' ) {
    $msg_move_body = '
<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
  <ContinuousMove xmlns="http://www.onvif.org/ver20/ptz/wsdl">
    <ProfileToken>'.$profileToken.'</ProfileToken>
      <Velocity>
        <PanTilt x="'.$x.'" y="'.$y.'" xmlns="http://www.onvif.org/ver10/schema"/>
      </Velocity>
  </ContinuousMove>
</s:Body>';
  } elsif ( $type eq 'zoom' ) {
    $msg_move_body = '
<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
  <ContinuousMove xmlns="http://www.onvif.org/ver20/ptz/wsdl">
    <ProfileToken>'.$profileToken.'</ProfileToken>
    <Velocity>
      <Zoom x="'.$x.'" xmlns="http://www.onvif.org/ver10/schema"/>
    </Velocity>
  </ContinuousMove>
</s:Body>';
  }

  return $msg_move_body;
}

#Up Arrow
sub moveConUp {
  Debug('Move Up');
  my $self = shift;
  my $cmd = $controlUri;
  my $msg_body = moveCamera("move", "0", "0.5");
  my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/ptz/wsdl/ContinuousMove"';
  $self->sendCmd($cmd, $msg_body, $content_type);
  $self->autoStop($self->{Monitor}->{AutoStopTimeout}, 0);
}

#Down Arrow
sub moveConDown {
  Debug('Move Down');
  my $self = shift;
  my $cmd = $controlUri;
  my $msg_body = moveCamera("move", "0", "-0.5");
  my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/ptz/wsdl/ContinuousMove"';
  $self->sendCmd($cmd, $msg_body, $content_type);
  $self->autoStop($self->{Monitor}->{AutoStopTimeout}, 0);
}

#Left Arrow
sub moveConLeft {
  Debug('Move Left');
  my $self = shift;
  my $cmd = $controlUri;
  my $msg_body = moveCamera("move", "-0.49", "0");
  my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/ptz/wsdl/ContinuousMove"';
  $self->sendCmd($cmd, $msg_body, $content_type);
  $self->autoStop($self->{Monitor}->{AutoStopTimeout}, 0);
}

#Right Arrow
sub moveConRight {
  Debug('Move Right');
  my $self = shift;
  my $cmd = $controlUri;
  my $msg_body = moveCamera("move","0.49","0");
  my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/ptz/wsdl/ContinuousMove"';
  $self->sendCmd($cmd, $msg_body, $content_type);
  $self->autoStop($self->{Monitor}->{AutoStopTimeout},0);
}

#Zoom In
sub zoomConTele {
  Debug('Zoom Tele');
  my $self = shift;
  my $cmd = $controlUri;
  my $msg_body = moveCamera("zoom","0.49","0");
  my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/ptz/wsdl/ContinuousMove"';
  $self->sendCmd($cmd, $msg_body, $content_type);
  $self->autoStop($self->{Monitor}->{AutoStopTimeout},1);
}

#Zoom Out
sub zoomConWide {
  Debug('Zoom Wide');
  my $self = shift;
  my $cmd = $controlUri;
  my $msg_body = moveCamera("zoom","-0.49","0");
  my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/ptz/wsdl/ContinuousMove"';
  $self->sendCmd($cmd, $msg_body, $content_type);
  $self->autoStop($self->{Monitor}->{AutoStopTimeout},1);
}

sub zoomStop {
  Debug('Zoom Stop');
  my $self = shift;
  my $cmd = $controlUri;
  $self->autoStop($self->{Monitor}->{AutoStopTimeout},1);
  Error('Zoom Stop not implemented');
}

#Diagonally Up Right Arrow
#This camera does not have builtin diagonal commands so we emulate them
sub moveConUpRight {
  Debug('Move Diagonally Up Right');
  my $self = shift;
  my $cmd = $controlUri;
  my $msg_body = moveCamera("move","0.5","0.5");
  my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/ptz/wsdl/ContinuousMove"';
  $self->sendCmd($cmd, $msg_body, $content_type);
  $self->autoStop($self->{Monitor}->{AutoStopTimeout},0);
}

#Diagonally Down Right Arrow
#This camera does not have builtin diagonal commands so we emulate them
sub moveConDownRight {
  Debug('Move Diagonally Down Right');
  my $self = shift;
  my $cmd = $controlUri;
  my $msg_body = moveCamera("move","0.5","-0.5");
  my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/ptz/wsdl/ContinuousMove"';
  $self->sendCmd($cmd, $msg_body, $content_type);
  $self->autoStop($self->{Monitor}->{AutoStopTimeout},0);
}

#Diagonally Up Left Arrow
#This camera does not have builtin diagonal commands so we emulate them
sub moveConUpLeft {
  Debug('Move Diagonally Up Left');
  my $self = shift;
  my $cmd = $controlUri;
  my $msg_body = moveCamera("move","-0.5","0.5");
  my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/ptz/wsdl/ContinuousMove"';
  $self->sendCmd($cmd, $msg_body, $content_type);
  $self->autoStop($self->{Monitor}->{AutoStopTimeout},0);
}

#Diagonally Down Left Arrow
#This camera does not have builtin diagonal commands so we emulate them
sub moveConDownLeft {
  Debug('Move Diagonally Down Left');
  my $self = shift;
  my $cmd = $controlUri;
  my $msg_body = moveCamera("move","-0.5","-0.5");
  my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/ptz/wsdl/ContinuousMove"';
  $self->sendCmd($cmd, $msg_body, $content_type);
  $self->autoStop($self->{Monitor}->{AutoStopTimeout},0);
}

#Stop
sub moveStop {
  Debug('Move Stop');
  my $self = shift;
  my $cmd = $controlUri;
  my $msg_body = '
<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
  <Stop xmlns="http://www.onvif.org/ver20/ptz/wsdl">
    <ProfileToken>'.$profileToken.'</ProfileToken>
    <PanTilt>true</PanTilt>
    <Zoom>true</Zoom>
  </Stop>
</s:Body>';
  my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/ptz/wsdl/ContinuousMove"';
  $self->sendCmd($cmd, $msg_body, $content_type);
}

#Set Camera Preset
sub presetSet {
  my $self = shift;
  my $params = shift;
  my $preset = $self->getParam($params, 'preset');
  Debug("Set Preset $preset");
  my $cmd = $controlUri;
  my $msg_body ='
<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
  <SetPreset xmlns="http://www.onvif.org/ver20/ptz/wsdl">
    <ProfileToken>' . $profileToken . '</ProfileToken>
    <PresetToken>'.$preset.'</PresetToken>
  </SetPreset>
</s:Body>';
  my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/ptz/wsdl/SetPreset"';
  $self->sendCmd($cmd, $msg_body, $content_type);
}

#Recall Camera Preset
sub presetGoto {
  my $self = shift;
  my $params = shift;
  my $preset = $self->getParam($params, 'preset');

  Debug("Goto Preset $preset");
  my $cmd = $controlUri;
  my $msg_body ='
<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
  <GotoPreset xmlns="http://www.onvif.org/ver20/ptz/wsdl">
    <ProfileToken>' . $profileToken . '</ProfileToken>
    <PresetToken>'.$preset.'</PresetToken>
  </GotoPreset>
</s:Body>';
  my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/ptz/wsdl/GotoPreset"';
  $self->sendCmd( $cmd, $msg_body, $content_type );
}

#Recall Camera Preset
sub presetHome {
  my $self = shift;
  my $params = shift;

  Debug("Goto Home preset");
  my $cmd = $controlUri;
  my $msg_body ='
<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
  <GotoHomePosition xmlns="http://www.onvif.org/ver20/ptz/wsdl">
    <ProfileToken>' . $profileToken . '</ProfileToken>
  </GotoHomePosition>
</s:Body>';
  my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/ptz/wsdl/GotoPreset"';
  $self->sendCmd( $cmd, $msg_body, $content_type );
}

#Horizontal Patrol
#To be determined if this camera supports this feature
sub horizontalPatrol {
  Debug('Horizontal Patrol');
  my $self = shift;
  my $cmd = '';
  my $msg ='';
  my $content_type = '';
  # $self->sendCmd( $cmd, $msg, $content_type );
  Error('PTZ Command not implemented in control script.');
}

#Horizontal Patrol Stop
#To be determined if this camera supports this feature
sub horizontalPatrolStop {
  Debug('Horizontal Patrol Stop');
  my $self = shift;
  my $cmd = '';
  my $msg ='';
  my $content_type = '';
  #    $self->sendCmd( $cmd, $msg, $content_type );
  Error('PTZ Command not implemented in control script.');
}

# Increase Brightness
sub irisAbsOpen {
  Debug("Iris $CamParams{Brightness}");
  my $self = shift;
  my $params = shift;
  $self->getCamParams() unless($CamParams{Brightness});
  my $step = $self->getParam($params, 'step');
  my $max = 100;

  $CamParams{Brightness} += $step;
  $CamParams{Brightness} = $max if ($CamParams{Brightness} > $max);

  my $cmd = 'onvif/imaging';
  my $msg_body ='
<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
  <SetImagingSettings xmlns="http://www.onvif.org/ver20/imaging/wsdl">
    <VideoSourceToken>000</VideoSourceToken>
    <ImagingSettings>
      <Brightness xmlns="http://www.onvif.org/ver10/schema">'.$CamParams{Brightness}.'</Brightness>
    </ImagingSettings>
    <ForcePersistence>true</ForcePersistence>
  </SetImagingSettings>
</s:Body>';
  my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/imaging/wsdl/SetImagingSettings"';
  $self->sendCmd($cmd, $msg_body, $content_type);
}

# Decrease Brightness
sub irisAbsClose {
  Debug("Iris $CamParams{Brightness}");
  my $self = shift;
  my $params = shift;
  $self->getCamParams() unless($CamParams{Brightness});
  my $step = $self->getParam($params, 'step');
  my $min = 0;

  $CamParams{Brightness} -= $step;
  $CamParams{Brightness} = $min if ($CamParams{Brightness} < $min);

  my $cmd = 'onvif/imaging';
  my $msg_body ='
<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
  <SetImagingSettings xmlns="http://www.onvif.org/ver20/imaging/wsdl">
    <VideoSourceToken>000</VideoSourceToken>
    <ImagingSettings>
      <Brightness xmlns="http://www.onvif.org/ver10/schema">'.$CamParams{Brightness}.'</Brightness>
    </ImagingSettings>
    <ForcePersistence>true</ForcePersistence>
  </SetImagingSettings>
</s:Body>';
  my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/imaging/wsdl/SetImagingSettings"';
  $self->sendCmd($cmd, $msg_body, $content_type);
}

# Increase Contrast
sub whiteAbsIn {
  Debug("Iris $CamParams{Contrast}");
  my $self = shift;
  my $params = shift;
  $self->getCamParams() unless($CamParams{Contrast});
  my $step = $self->getParam($params, 'step');
  my $max = 100;

  $CamParams{Contrast} += $step;
  $CamParams{Contrast} = $max if ($CamParams{Contrast} > $max);

  my $cmd = 'onvif/imaging';
  my $msg_body ='
<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
  <SetImagingSettings xmlns="http://www.onvif.org/ver20/imaging/wsdl">
    <VideoSourceToken>000</VideoSourceToken>
    <ImagingSettings>
      <Contrast xmlns="http://www.onvif.org/ver10/schema">'.$CamParams{Contrast}.'</Contrast>
    </ImagingSettings>
    <ForcePersistence>true</ForcePersistence>
  </SetImagingSettings>
</s:Body>';
  my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/imaging/wsdl/SetImagingSettings"';
  $self->sendCmd($cmd, $msg_body, $content_type);
}

# Decrease Contrast
sub whiteAbsOut {
  Debug("Iris $CamParams{Contrast}");
  my $self = shift;
  my $params = shift;
  $self->getCamParams() unless($CamParams{Contrast});
  my $step = $self->getParam($params, 'step');
  my $min = 0;

  $CamParams{Contrast} -= $step;
  $CamParams{Contrast} = $min if ($CamParams{Contrast} < $min);

  my $cmd = 'onvif/imaging';
  my $msg_body ='
<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
  <SetImagingSettings xmlns="http://www.onvif.org/ver20/imaging/wsdl">
    <VideoSourceToken>000</VideoSourceToken>
    <ImagingSettings>
      <Contrast xmlns="http://www.onvif.org/ver10/schema">'.$CamParams{Contrast}.'</Contrast>
    </ImagingSettings>
    <ForcePersistence>true</ForcePersistence>
  </SetImagingSettings>
</s:Body>';
  my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/imaging/wsdl/SetImagingSettings"';
  $self->sendCmd($cmd, $msg_body, $content_type);
}

1;
__END__
