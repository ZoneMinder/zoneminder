# ==========================================================================
#
# ZoneMinder Netcat IP Control Protocol Module, $Date: 2009-11-25 09:20:00 +0000 (Wed, 04 Nov 2009) $, $Revision: 0001 $
# Copyright (C) 2001-2008 Philip Coombes
# Converted for use with Netcat IP Camera by Andrew Bauer (knnniggett@users.sourceforge.net)
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
# This module contains the first implementation of the Netcat IP camera control
# protocol
#
package ZoneMinder::Control::Netcat;

use 5.006;
use strict;
use warnings;
use MIME::Base64;
use Digest::SHA;
use DateTime;

require ZoneMinder::Base;
require ZoneMinder::Control;

our @ISA = qw(ZoneMinder::Control);

our %CamParams = ();

our ($profileToken, $address, $port, %identity);

# ==========================================================================
#
# Netcat IP Control Protocol
# This script sends ONVIF compliant commands and may work with other cameras
#
# The Netcat camera gladly accepts any command with or without authentication,
# which prevented me from developing Onvif authentication in this control script.
#
# Basic preset functions are supported, but more advanced features, which make
# use of abnormally high preset numbers (ir lamp control, tours, pan speed, etc)
# may or may not work.
# 
#
# Possible future improvements (for anyone to improve upon):
#    - Build the SOAP commands at runtime rather than use templates
#    - Implement previously mentioned advanced features
#
# Implementing the first two will require additional Perl modules, and adding
# more dependencies to ZoneMinder is always a concern.
#
# On ControlAddress use the format :
#   [USERNAME:PASSWORD@]ADDRESS:PORT
#   eg : 10.1.2.1:8899
#        10.0.100.1:8899
#        username:password@10.0.100.1:8899
#
# Use port 8899 for the Netcat camera
#
# Make sure and place a value in the Auto Stop Timeout field.
# Recommend starting with a value of 1 second, and adjust accordingly.
#
# ==========================================================================

use ZoneMinder::Logger qw(:all);
use ZoneMinder::Config qw(:all);

use Time::HiRes qw( usleep );

sub open {
  my $self = shift;

  $self->loadMonitor();

  $profileToken = $self->{Monitor}->{ControlDevice};
  if ($profileToken eq '') { $profileToken = '000'; }

  parseControlAddress($self->{Monitor}->{ControlAddress});

  use LWP::UserAgent;
  $self->{ua} = LWP::UserAgent->new;
  $self->{ua}->agent('ZoneMinder Control Agent/'.ZoneMinder::Base::ZM_VERSION);

  $self->{state} = 'open';
}

sub parseControlAddress {
  my $controlAddress = shift;
  my ($usernamepassword, $addressport) = split /@/, $controlAddress;
  if ( !defined $addressport ) {
    # If value of "Control address" does not consist of two parts, then only address is given
    $addressport = $usernamepassword;
  } else {
    my ($username , $password) = split /:/, $usernamepassword;
    %identity = (username => $username, password => $password);
  }
  ($address, $port) = split /:/, $addressport;
}

sub digestBase64 {
  my ($nonce, $date, $password) = @_;
  my $shaGenerator = Digest::SHA->new(1);
  $shaGenerator->add($nonce . $date . $password);
  return encode_base64($shaGenerator->digest, '');
}

sub authentificationHeader {
  my ($username, $password) = @_;
  my $nonce = chr(int(rand(254))) for (0 .. 20);
  my $nonceBase64 = encode_base64($nonce, '');
  my $currentDate = DateTime->now()->iso8601().'Z';

  return '<s:Header><Security s:mustUnderstand="1" xmlns="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd"><UsernameToken xmlns="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd"><Username>' . $username . '</Username><Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordDigest">' . digestBase64($nonce, $currentDate, $password) . '</Password><Nonce EncodingType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary">' . $nonceBase64 . '</Nonce><Created xmlns="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">' . $currentDate . '</Created></UsernameToken></Security></s:Header>';
}

sub sendCmd {
  my $self = shift;
  my $cmd = shift;
  my $msg = shift;
  my $content_type = shift;
  my $result = undef;

  $self->printMsg($cmd, 'Tx');

  my $server_endpoint = 'http://'.$address.':'.$port.'/'.$cmd;
  my $req = HTTP::Request->new(POST => $server_endpoint);
  $req->header('content-type' => $content_type);
  $req->header('Host' => $address . ':' . $port);
  $req->header('content-length' => length($msg));
  $req->header('accept-encoding' => 'gzip, deflate');
  $req->header('connection' => 'Close');
  $req->content($msg);

  my $res = $self->{ua}->request($req);

  if ( $res->is_success ) {
    $result = !undef;
  } else {
    Error("After sending PTZ command, camera returned the following error:'".$res->status_line()."'\nMSG:$msg\nResponse:".$res->content);
  }
  return $result;
}

sub getCamParams {
  my $self = shift;
  my $msg = '<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope"><s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"><GetImagingSettings xmlns="http://www.onvif.org/ver20/imaging/wsdl"><VideoSourceToken>000</VideoSourceToken></GetImagingSettings></s:Body></s:Envelope>';
  my $server_endpoint = 'http://'.$address.':'.$port.'/onvif/imaging';
  my $req = HTTP::Request->new(POST => $server_endpoint);
  $req->header('content-type' => 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/imaging/wsdl/GetImagingSettings"');
  $req->header('Host' => $address . ':' . $port);
  $req->header('content-length' => length($msg));
  $req->header('accept-encoding' => 'gzip, deflate');
  $req->header('connection' => 'Close');
  $req->content($msg);

  my $res = $self->{ua}->request($req);

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

  if ( $autostop ) {
    Debug('Auto Stop');
    my $cmd = 'onvif/PTZ';
    my $msg = '<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope">' . ((%identity) ? authentificationHeader($identity{username}, $identity{password}) : '') . '<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"><Stop xmlns="http://www.onvif.org/ver20/ptz/wsdl"><ProfileToken>' . $profileToken . '</ProfileToken><PanTilt>true</PanTilt><Zoom>false</Zoom></Stop></s:Body></s:Envelope>';
    my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/ptz/wsdl/ContinuousMove"';
    usleep($autostop);
    $self->sendCmd($cmd, $msg, $content_type);
  }
}

# Reset the Camera
sub reset {
  Debug('Camera Reset');
  my $self = shift;
  my $cmd = '';
  my $msg = '<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope">' . ((%identity) ? authentificationHeader($identity{username}, $identity{password}) : '') . '<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"><SystemReboot xmlns="http://www.onvif.org/ver10/device/wsdl"/></s:Body></s:Envelope>';
  my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver10/device/wsdl/SystemReboot"';
  $self->sendCmd($cmd, $msg, $content_type);
}

#Up Arrow
sub moveConUp {
  Debug('Move Up');
  my $self = shift;
  my $cmd = 'onvif/PTZ';
  my $msg ='<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope">' . ((%identity) ? authentificationHeader($identity{username}, $identity{password}) : '') . '<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"><ContinuousMove xmlns="http://www.onvif.org/ver20/ptz/wsdl"><ProfileToken>' . $profileToken . '</ProfileToken><Velocity><PanTilt x="0" y="0.5" xmlns="http://www.onvif.org/ver10/schema"/></Velocity></ContinuousMove></s:Body></s:Envelope>';
  my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/ptz/wsdl/ContinuousMove"';
  $self->sendCmd($cmd, $msg, $content_type);
  $self->autoStop($self->{Monitor}->{AutoStopTimeout});
}

#Down Arrow
sub moveConDown {
  Debug('Move Down');
  my $self = shift;
  my $cmd = 'onvif/PTZ';
  my $msg ='<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope">'.((%identity) ? authentificationHeader($identity{username}, $identity{password}) : '').'<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"><ContinuousMove xmlns="http://www.onvif.org/ver20/ptz/wsdl"><ProfileToken>' . $profileToken . '</ProfileToken><Velocity><PanTilt x="0" y="-0.5" xmlns="http://www.onvif.org/ver10/schema"/></Velocity></ContinuousMove></s:Body></s:Envelope>';
  my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/ptz/wsdl/ContinuousMove"';
  $self->sendCmd($cmd, $msg, $content_type);
  $self->autoStop($self->{Monitor}->{AutoStopTimeout});
}

#Left Arrow
sub moveConLeft {
  Debug('Move Left');
  my $self = shift;
  my $cmd = 'onvif/PTZ';
  my $msg ='<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope">' . ((%identity) ? authentificationHeader($identity{username}, $identity{password}) : '') . '<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"><ContinuousMove xmlns="http://www.onvif.org/ver20/ptz/wsdl"><ProfileToken>' . $profileToken . '</ProfileToken><Velocity><PanTilt x="-0.49" y="0" xmlns="http://www.onvif.org/ver10/schema"/></Velocity></ContinuousMove></s:Body></s:Envelope>';
  my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/ptz/wsdl/ContinuousMove"';
  $self->sendCmd($cmd, $msg, $content_type);
  $self->autoStop($self->{Monitor}->{AutoStopTimeout});
}

#Right Arrow
sub moveConRight {
  Debug('Move Right');
  my $self = shift;
  my $cmd = 'onvif/PTZ';
  my $msg ='<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope">' . ((%identity) ? authentificationHeader($identity{username}, $identity{password}) : '') . '<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"><ContinuousMove xmlns="http://www.onvif.org/ver20/ptz/wsdl"><ProfileToken>' . $profileToken . '</ProfileToken><Velocity><PanTilt x="0.49" y="0" xmlns="http://www.onvif.org/ver10/schema"/></Velocity></ContinuousMove></s:Body></s:Envelope>';
  my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/ptz/wsdl/ContinuousMove"';
  $self->sendCmd($cmd, $msg, $content_type);
  $self->autoStop($self->{Monitor}->{AutoStopTimeout});
}

#Zoom In
sub zoomConTele {
  Debug('Zoom Tele');
  my $self = shift;
  my $cmd = 'onvif/PTZ';
  my $msg ='<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope">' . ((%identity) ? authentificationHeader($identity{username}, $identity{password}) : '') . '<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"><ContinuousMove xmlns="http://www.onvif.org/ver20/ptz/wsdl"><ProfileToken>' . $profileToken . '</ProfileToken><Velocity><Zoom x="0.49" xmlns="http://www.onvif.org/ver10/schema"/></Velocity></ContinuousMove></s:Body></s:Envelope>';
  my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/ptz/wsdl/ContinuousMove"';
  $self->sendCmd($cmd, $msg, $content_type);
  $self->autoStop($self->{Monitor}->{AutoStopTimeout});
}

#Zoom Out
sub zoomConWide {
  Debug('Zoom Wide');
  my $self = shift;
  my $cmd = 'onvif/PTZ';
  my $msg ='<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope">' . ((%identity) ? authentificationHeader($identity{username}, $identity{password}) : '') . '<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"><ContinuousMove xmlns="http://www.onvif.org/ver20/ptz/wsdl"><ProfileToken>' . $profileToken . '</ProfileToken><Velocity><Zoom x="-0.49" xmlns="http://www.onvif.org/ver10/schema"/></Velocity></ContinuousMove></s:Body></s:Envelope>';
  my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/ptz/wsdl/ContinuousMove"';
  $self->sendCmd($cmd, $msg, $content_type);
  $self->autoStop($self->{Monitor}->{AutoStopTimeout});
}

#Diagonally Up Right Arrow
#This camera does not have builtin diagonal commands so we emulate them
sub moveConUpRight {
  Debug('Move Diagonally Up Right');
  my $self = shift;
  my $cmd = 'onvif/PTZ';
  my $msg ='<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope">' . ((%identity) ? authentificationHeader($identity{username}, $identity{password}) : '') . '<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"><ContinuousMove xmlns="http://www.onvif.org/ver20/ptz/wsdl"><ProfileToken>' . $profileToken . '</ProfileToken><Velocity><PanTilt x="0.5" y="0.5" xmlns="http://www.onvif.org/ver10/schema"/></Velocity></ContinuousMove></s:Body></s:Envelope>';
  my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/ptz/wsdl/ContinuousMove"';
  $self->sendCmd($cmd, $msg, $content_type);
  $self->autoStop($self->{Monitor}->{AutoStopTimeout});
}

#Diagonally Down Right Arrow
#This camera does not have builtin diagonal commands so we emulate them
sub moveConDownRight {
  Debug('Move Diagonally Down Right');
  my $self = shift;
  my $cmd = 'onvif/PTZ';
  my $msg ='<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope">' . ((%identity) ? authentificationHeader($identity{username}, $identity{password}) : '') . '<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"><ContinuousMove xmlns="http://www.onvif.org/ver20/ptz/wsdl"><ProfileToken>' . $profileToken . '</ProfileToken><Velocity><PanTilt x="0.5" y="-0.5" xmlns="http://www.onvif.org/ver10/schema"/></Velocity></ContinuousMove></s:Body></s:Envelope>';
  my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/ptz/wsdl/ContinuousMove"';
  $self->sendCmd($cmd, $msg, $content_type);
  $self->autoStop($self->{Monitor}->{AutoStopTimeout});
}

#Diagonally Up Left Arrow
#This camera does not have builtin diagonal commands so we emulate them
sub moveConUpLeft {
  Debug('Move Diagonally Up Left');
  my $self = shift;
  my $cmd = 'onvif/PTZ';
  my $msg ='<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope">'.((%identity) ? authentificationHeader($identity{username}, $identity{password}) : '').'<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"><ContinuousMove xmlns="http://www.onvif.org/ver20/ptz/wsdl"><ProfileToken>' . $profileToken . '</ProfileToken><Velocity><PanTilt x="-0.5" y="0.5" xmlns="http://www.onvif.org/ver10/schema"/></Velocity></ContinuousMove></s:Body></s:Envelope>';
  my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/ptz/wsdl/ContinuousMove"';
  $self->sendCmd($cmd, $msg, $content_type);
  $self->autoStop($self->{Monitor}->{AutoStopTimeout});
}

#Diagonally Down Left Arrow
#This camera does not have builtin diagonal commands so we emulate them
sub moveConDownLeft {
  Debug('Move Diagonally Down Left');
  my $self = shift;
  my $cmd = 'onvif/PTZ';
  my $msg ='<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope">' . ((%identity) ? authentificationHeader($identity{username}, $identity{password}) : '') . '<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"><ContinuousMove xmlns="http://www.onvif.org/ver20/ptz/wsdl"><ProfileToken>' . $profileToken . '</ProfileToken><Velocity><PanTilt x="-0.5" y="-0.5" xmlns="http://www.onvif.org/ver10/schema"/></Velocity></ContinuousMove></s:Body></s:Envelope>';
  my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/ptz/wsdl/ContinuousMove"';
  $self->sendCmd($cmd, $msg, $content_type);
  $self->autoStop($self->{Monitor}->{AutoStopTimeout});
}

#Stop
sub moveStop {
  Debug('Move Stop');
  my $self = shift;
  my $cmd = 'onvif/PTZ';
  my $msg ='<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope">' . ((%identity) ? authentificationHeader($identity{username}, $identity{password}) : '') . '<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"><Stop xmlns="http://www.onvif.org/ver20/ptz/wsdl"><ProfileToken>' . $profileToken . '</ProfileToken><PanTilt>true</PanTilt><Zoom>false</Zoom></Stop></s:Body></s:Envelope>';
  my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/ptz/wsdl/ContinuousMove"';
  $self->sendCmd($cmd, $msg, $content_type);
}

#Set Camera Preset
sub presetSet {
  my $self = shift;
  my $params = shift;
  my $preset = $self->getParam($params, 'preset');
  Debug("Set Preset $preset");
  my $cmd = 'onvif/PTZ';
  my $msg ='<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope">' . ((%identity) ? authentificationHeader($identity{username}, $identity{password}) : '') . '<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"><SetPreset xmlns="http://www.onvif.org/ver20/ptz/wsdl"><ProfileToken>' . $profileToken . '</ProfileToken><PresetToken>'.$preset.'</PresetToken></SetPreset></s:Body></s:Envelope>';
  my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/ptz/wsdl/SetPreset"';
  $self->sendCmd($cmd, $msg, $content_type);
}

#Recall Camera Preset
sub presetGoto {
  my $self = shift;
  my $params = shift;
  my $preset = $self->getParam($params, 'preset');
  Debug("Goto Preset $preset");
  my $cmd = 'onvif/PTZ';
  my $msg ='<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope">' . ((%identity) ? authentificationHeader($identity{username}, $identity{password}) : '') . '<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"><GotoPreset xmlns="http://www.onvif.org/ver20/ptz/wsdl"><ProfileToken>' . $profileToken . '</ProfileToken><PresetToken>'.$preset.'</PresetToken></GotoPreset></s:Body></s:Envelope>';
  my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/ptz/wsdl/GotoPreset"';
  $self->sendCmd( $cmd, $msg, $content_type );
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
  my $msg ='<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope">' . ((%identity) ? authentificationHeader($identity{username}, $identity{password}) : '') . '<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"><SetImagingSettings xmlns="http://www.onvif.org/ver20/imaging/wsdl"><VideoSourceToken>000</VideoSourceToken><ImagingSettings><Brightness xmlns="http://www.onvif.org/ver10/schema">'.$CamParams{Brightness}.'</Brightness></ImagingSettings><ForcePersistence>true</ForcePersistence></SetImagingSettings></s:Body></s:Envelope>';
  my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/imaging/wsdl/SetImagingSettings"';
  $self->sendCmd($cmd, $msg, $content_type);
}

# Decrease Brightness
sub irisAbsClose {
  Debug("Iris $CamParams{Brightness}");
  my $self = shift;
  my $params = shift;
  $self->getCamParams() unless($CamParams{brightness});
  my $step = $self->getParam($params, 'step');
  my $min = 0;

  $CamParams{Brightness} -= $step;
  $CamParams{Brightness} = $min if ($CamParams{Brightness} < $min);

  my $cmd = 'onvif/imaging';
  my $msg ='<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope">' . ((%identity) ? authentificationHeader($identity{username}, $identity{password}) : '') . '<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"><SetImagingSettings xmlns="http://www.onvif.org/ver20/imaging/wsdl"><VideoSourceToken>000</VideoSourceToken><ImagingSettings><Brightness xmlns="http://www.onvif.org/ver10/schema">'.$CamParams{Brightness}.'</Brightness></ImagingSettings><ForcePersistence>true</ForcePersistence></SetImagingSettings></s:Body></s:Envelope>';
  my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/imaging/wsdl/SetImagingSettings"';
  $self->sendCmd($cmd, $msg, $content_type);
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
  my $msg ='<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope">' . ((%identity) ? authentificationHeader($identity{username}, $identity{password}) : '') . '<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"><SetImagingSettings xmlns="http://www.onvif.org/ver20/imaging/wsdl"><VideoSourceToken>000</VideoSourceToken><ImagingSettings><Contrast xmlns="http://www.onvif.org/ver10/schema">'.$CamParams{Contrast}.'</Contrast></ImagingSettings><ForcePersistence>true</ForcePersistence></SetImagingSettings></s:Body></s:Envelope>';
  my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/imaging/wsdl/SetImagingSettings"';
  $self->sendCmd($cmd, $msg, $content_type);
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
  my $msg ='<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope">' . ((%identity) ? authentificationHeader($identity{username}, $identity{password}) : '') . '<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"><SetImagingSettings xmlns="http://www.onvif.org/ver20/imaging/wsdl"><VideoSourceToken>000</VideoSourceToken><ImagingSettings><Contrast xmlns="http://www.onvif.org/ver10/schema">'.$CamParams{Contrast}.'</Contrast></ImagingSettings><ForcePersistence>true</ForcePersistence></SetImagingSettings></s:Body></s:Envelope>';
  my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/imaging/wsdl/SetImagingSettings"';
  $self->sendCmd($cmd, $msg, $content_type);
}

1;
__END__
