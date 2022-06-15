# ==========================================================================
#
# Basic ZoneMinder ieGeek IE20 ONVIF IP PTZ Camera Control Module 9-June-2022
# By Clipo (ZoneMinder Forums)
#
# This file is based up on the Reolink IP Control Module 2016-01-19
# Copyright (C) 2016 Chris Swertfeger
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
# This module contains the first implementation of the ieGeek ONVIF IP camera control
# protocol
#
package ZoneMinder::Control::ieGeek;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Control;

our @ISA = qw(ZoneMinder::Control);

our %CamParams = ();

# ==========================================================================
#
# ieGeek ONVIF IP Control Protocol
# The ieGeek camera support ONVIF in a limited way with 
# this script sends ONVIF style commands and may work with other cameras
# that require authentication
#
# The script was developed against an IE20 Camera.
#
# Basic preset functions are supported only.
# #
# On ControlAddress use the format :
#   USERNAME:PASSWORD@ADDRESS:PORT
#   eg : admin:pass@10.1.2.1:8080
#        
# ieGeek cameras use port 8080 by default
#
# The Auto Stop Timeout field.
# Recommend starting with a value of 1 second, and adjust accordingly.
#
# ==========================================================================

use ZoneMinder::Logger qw(:all);
use ZoneMinder::Config qw(:all);

use Time::HiRes qw( usleep );

use MIME::Base64;
use Digest::SHA;
use DateTime;

my ($username,$password,$host,$port);

sub open
{
    my $self = shift;

    $self->loadMonitor();
    #
    # Extract the username/password host/port from ControlAddress
    #
    if( $self->{Monitor}{ControlAddress} =~ /^([^:]+):([^@]+)@(.+)/ ) 
    { # user:pass@host...
      $username = $1;
      $password = $2;
      $host = $3;
    }
    elsif( $self->{Monitor}{ControlAddress} =~ /^([^@]+)@(.+)/ )  
    { # user@host...
      $username = $1;
      $host = $2;
    }
    else { # Just a host
      $host = $self->{Monitor}{ControlAddress};
    }
    # Check if it is a host and port or just a host
    if( $host =~ /([^:]+):(.+)/ ) 
    {
      $host = $1;
      $port = $2;
    }
    else 
    {
      $port = 80;
    }

    use LWP::UserAgent;
    $self->{ua} = LWP::UserAgent->new;
    $self->{ua}->agent( "ZoneMinder Control Agent/".ZoneMinder::Base::ZM_VERSION );

    $self->{state} = 'open';
}

sub printMsg
{
    my $self = shift;
    my $msg = shift;
    my $msg_len = length($msg);

    Debug( $msg."[".$msg_len."]" );
}

sub sendCmd
{
    my $self = shift;
    my $cmd = shift;
    my $msg = shift;
    my $content_type = shift;
    my $result = undef;

    printMsg( $cmd, "Tx" );

    my $server_endpoint = "http://".$host.":".$port."/$cmd";
    my $req = HTTP::Request->new( POST => $server_endpoint );
    $req->header('content-type' => $content_type);
    $req->header('Host' => $host.':'.$port);
    $req->header('content-length' => length($msg));
    $req->header('accept-encoding' => 'gzip, deflate');
    $req->header('connection' => 'close');
    $req->content($msg);

    my $res = $self->{ua}->request($req);

    if ( $res->is_success ) {
        $result = !undef;
    } else {
        Error("After sending PTZ command to $server_endpoint, camera returned the following error:'".$res->status_line()."'" );
    }
    return $result;
}

sub getCamParams
{
    my $self = shift;
    my $nonce;
    for (0..20){$nonce .= chr(int(rand(254)));}
    my $mydate = DateTime->now()->iso8601().'Z';
    my $sha = Digest::SHA->new(1);
    $sha->add($nonce.$mydate.$password);
    my $digest = encode_base64($sha->digest,"");
    my $msg = '<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope">
    	<s:Header>
	<Security s:mustUnderstand="1" xmlns="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
	<UsernameToken>
	<Username>'.$username.'</Username>
	<Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordDigest">'.$digest.'</Password>
	<Nonce EncodingType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary">'.encode_base64($nonce,"").'</Nonce>
	<Created xmlns="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">'.$mydate.'</Created>
	</UsernameToken>
	</Security>
	</s:Header>
	<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
	<GetImagingSettings xmlns="http://www.onvif.org/ver20/imaging/wsdl">
	<VideoSourceToken>000</VideoSourceToken>
	</GetImagingSettings>
	</s:Body>
	</s:Envelope>';
    my $server_endpoint = "http://".$self->{Monitor}->{ControlAddress}."/onvif/imaging";
    my $req = HTTP::Request->new( POST => $server_endpoint );
    $req->header('content-type' => 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/imaging/wsdl/GetImagingSettings"');
    $req->header('Host' => $host.":".$port);
    $req->header('content-length' => length($msg));
    $req->header('accept-encoding' => 'gzip, deflate');
    $req->header('connection' => 'Close');
    $req->content($msg);

    my $res = $self->{ua}->request($req);

    if ( $res->is_success ) {
        # We should really use an xml or soap library to parse the xml tags
        my $content = $res->decoded_content;

        if ($content =~ /.*<tt:(Brightness)>(.+)<\/tt:Brightness>.*/) {
            $CamParams{$1} = $2;
        }
        if ($content =~ /.*<tt:(Contrast)>(.+)<\/tt:Contrast>.*/) {
            $CamParams{$1} = $2;
        }
    } 
    else
    {
        Error( "Unable to retrieve camera image settings:'".$res->status_line()."'" );
    }
}

#autoStop
#This makes use of the ZoneMinder Auto Stop Timeout on the Control Tab
sub autoStop
{
    my $self = shift;
    my $autostop = shift;

    if( $autostop ) {
        Debug( "Auto Stop" );
        my $cmd = 'onvif/PTZ';
        my $nonce;
        for (0..20){$nonce .= chr(int(rand(254)));}
        my $mydate = DateTime->now()->iso8601().'Z';
        my $sha = Digest::SHA->new(1);
        $sha->add($nonce.$mydate.$password);
        my $digest = encode_base64($sha->digest,"");
        my $msg ='<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope">
		<s:Header>
		<Security s:mustUnderstand="1" xmlns="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
		<UsernameToken>
		<Username>'.$username.'</Username>
		<Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordDigest">'.$digest.'</Password>
		<Nonce EncodingType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary">'.encode_base64($nonce,"").'</Nonce>
		<Created xmlns="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">'.$mydate.'</Created>
		</UsernameToken>
		</Security>
		</s:Header>
		<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
		<ContinuousMove xmlns="http://www.onvif.org/ver20/ptz/wsdl">
		<ProfileToken>000</ProfileToken>
		<Velocity>
		<PanTilt x="0" y="0" xmlns="http://www.onvif.org/ver10/schema"/>
		</Velocity>
		</ContinuousMove>
		</s:Body>
		</s:Envelope>';
    	my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/ptz/wsdl/ContinuousMove"';
        usleep( $autostop );
        $self->sendCmd( $cmd, $msg, $content_type );
    }
}

# Reboot the Camera
sub reboot
{
    Debug( "Camera Reboot" );
    my $self = shift;
    my $nonce;
    for (0..20){$nonce .= chr(int(rand(254)));}
    my $mydate = DateTime->now()->iso8601().'Z';
    my $sha = Digest::SHA->new(1);
    $sha->add($nonce.$mydate.$password);
    my $digest = encode_base64($sha->digest,"");
    my $cmd = "";
    my $msg = '<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope">
    	<s:Header>
	<Security s:mustUnderstand="1" xmlns="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
	<UsernameToken>
	<Username>'.$username.'</Username>
	<Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordDigest">'.$digest.'</Password>
	<Nonce EncodingType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary">'.encode_base64($nonce,"").'</Nonce>
	<Created xmlns="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">'.$mydate.'</Created>
	</UsernameToken>
	</Security>
	</s:Header>
	<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
	<SystemReboot xmlns="http://www.onvif.org/ver10/device/wsdl"/>
	</s:Body>
	</s:Envelope>';
    my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver10/device/wsdl/SystemReboot"';
    $self->sendCmd( $cmd, $msg, $content_type );
}

#Up Arrow
sub moveConUp
{
    Debug( "Move Up" );
    my $self = shift;
    my $cmd = 'onvif/PTZ';
    my $nonce;
    for (0..20){$nonce .= chr(int(rand(254)));}
    my $mydate = DateTime->now()->iso8601().'Z';
    my $sha = Digest::SHA->new(1);
    $sha->add($nonce.$mydate.$password);
    my $digest = encode_base64($sha->digest,"");
    my $msg ='<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope">
    		<s:Header>
		<Security s:mustUnderstand="1" xmlns="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
		<UsernameToken>
		<Username>'.$username.'</Username>
		<Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordDigest">'.$digest.'</Password>
		<Nonce EncodingType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary">'.encode_base64($nonce,"").'</Nonce>
		<Created xmlns="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">'.$mydate.'</Created>
		</UsernameToken>
		</Security>
		</s:Header>
		<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
		<ContinuousMove xmlns="http://www.onvif.org/ver20/ptz/wsdl">
		<ProfileToken>000</ProfileToken>
		<Velocity>
		<PanTilt x="0" y="0.5" xmlns="http://www.onvif.org/ver10/schema"/>
		</Velocity>
		</ContinuousMove>
		</s:Body>
		</s:Envelope>';
    my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/ptz/wsdl/ContinuousMove"';
    $self->sendCmd( $cmd, $msg, $content_type );
    #AutoStop Commnented out to give fine control
    #$self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}

#Down Arrow
sub moveConDown
{
    Debug( "Move Down" );
    my $self = shift;
    my $cmd = 'onvif/PTZ';
    my $nonce;
    for (0..20){$nonce .= chr(int(rand(254)));}
    my $mydate = DateTime->now()->iso8601().'Z';
    my $sha = Digest::SHA->new(1);
    $sha->add($nonce.$mydate.$password);
    my $digest = encode_base64($sha->digest,"");
    my $msg ='<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope">
    	<s:Header>
	<Security s:mustUnderstand="1" xmlns="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
	<UsernameToken>
	<Username>'.$username.'</Username>
	<Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordDigest">'.$digest.'</Password>
	<Nonce EncodingType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary">'.encode_base64($nonce,"").'</Nonce>
	<Created xmlns="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">'.$mydate.'</Created>
	</UsernameToken>
	</Security>
	</s:Header>
	<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
	<ContinuousMove xmlns="http://www.onvif.org/ver20/ptz/wsdl">
	<ProfileToken>000</ProfileToken
	><Velocity>
	<PanTilt x="0" y="-0.5" xmlns="http://www.onvif.org/ver10/schema"/>
	</Velocity>
	</ContinuousMove>
	</s:Body>
	</s:Envelope>';
    my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/ptz/wsdl/ContinuousMove"';
    $self->sendCmd( $cmd, $msg, $content_type );
    #AutoStop Commnented out to give fine control
    #$self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}

#Left Arrow
sub moveConLeft
{
    Debug( "Move Left" );
    my $self = shift;
    my $cmd = 'onvif/PTZ';
    my $nonce;
    for (0..20){$nonce .= chr(int(rand(254)));}
    my $mydate = DateTime->now()->iso8601().'Z';
    my $sha = Digest::SHA->new(1);
    $sha->add($nonce.$mydate.$password);
    my $digest = encode_base64($sha->digest,"");
    my $msg ='<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope">
    	<s:Header>
	<Security s:mustUnderstand="1" xmlns="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
	<UsernameToken>
	<Username>'.$username.'</Username>
	<Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordDigest">'.$digest.'</Password>
	<Nonce EncodingType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary">'.encode_base64($nonce,"").'</Nonce>
	<Created xmlns="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">'.$mydate.'</Created>
	</UsernameToken>
	</Security>
	</s:Header>
	<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
	<ContinuousMove xmlns="http://www.onvif.org/ver20/ptz/wsdl">
	<ProfileToken>000</ProfileToken>
	<Velocity>
	<PanTilt x="-0.49" y="0" xmlns="http://www.onvif.org/ver10/schema"/>
	</Velocity>
	</ContinuousMove>
	</s:Body>
	</s:Envelope>';
    my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/ptz/wsdl/ContinuousMove"';
    $self->sendCmd( $cmd, $msg, $content_type );
    #AutoStop Commnented out to give fine control
    #$self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}

#Right Arrow
sub moveConRight
{
    Debug( "Move Right" );
    my $self = shift;
    my $cmd = 'onvif/PTZ';
    my $nonce;
    for (0..20){$nonce .= chr(int(rand(254)));}
    my $mydate = DateTime->now()->iso8601().'Z';
    my $sha = Digest::SHA->new(1);
    $sha->add($nonce.$mydate.$password);
    my $digest = encode_base64($sha->digest,"");
     my $msg ='<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope">
     	<s:Header>
	<Security s:mustUnderstand="1" xmlns="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
	<UsernameToken>
	<Username>'.$username.'</Username>
	<Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordDigest">'.$digest.'</Password>
	<Nonce EncodingType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary">'.encode_base64($nonce,"").'</Nonce>
	<Created xmlns="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">'.$mydate.'</Created>
	</UsernameToken>
	</Security>
	</s:Header>
	<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
	<ContinuousMove xmlns="http://www.onvif.org/ver20/ptz/wsdl">
	<ProfileToken>000</ProfileToken>
	<Velocity>
	<PanTilt x="0.5" y="0" xmlns="http://www.onvif.org/ver10/schema"/>
	</Velocity>
	</ContinuousMove>
	</s:Body>
	</s:Envelope>';
    my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/ptz/wsdl/ContinuousMove"';
    $self->sendCmd( $cmd, $msg, $content_type );
    #AutoStop Commnented out to give fine control
    #$self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}

#Stop
#The ieGeek implimentaion of ONVIF seems to not support the Stop command so a zero motion contious move command is sent.
sub moveStop
{
    Debug( "Move Stop" );
    my $self = shift;
    my $cmd = 'onvif/PTZ';
    my $nonce;
    for (0..20){$nonce .= chr(int(rand(254)));}
    my $mydate = DateTime->now()->iso8601().'Z';
    my $sha = Digest::SHA->new(1);
    $sha->add($nonce.$mydate.$password);
    my $digest = encode_base64($sha->digest,"");
    my $msg ='<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope">
    	<s:Header>
	<Security s:mustUnderstand="1" xmlns="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
	<UsernameToken>
	<Username>'.$username.'</Username>
	<Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordDigest">'.$digest.'</Password>
	<Nonce EncodingType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary">'.encode_base64($nonce,"").'</Nonce>
	<Created xmlns="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">'.$mydate.'</Created>
	</UsernameToken>
	</Security>
	</s:Header>
	<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
	<ContinuousMove xmlns="http://www.onvif.org/ver20/ptz/wsdl">
	<ProfileToken>000</ProfileToken>
	<Velocity>
	<PanTilt x="0" y="0" xmlns="http://www.onvif.org/ver10/schema"/>
	</Velocity>
	</ContinuousMove>
	</s:Body>
	</s:Envelope>';
    my $content_type = 'application/soap+xml; charset=utf-8; action="http://www.onvif.org/ver20/ptz/wsdl/ContinuousMove"';
    $self->sendCmd( $cmd, $msg, $content_type );
}

1;
