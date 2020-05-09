# ==========================================================================
#
# ZoneMinder Keekoon Control Protocol Module
# This code was mostly derived from other ZM Control modules
#
# ==========================================================================
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
# ==========================================================================
#
# Tested: KK002 (22 July 2016)
#
# Usage:
# ======
#
# Copy this file to say /usr/share/perl5/ZoneMinder/Control (Debian/Ubuntu)
#
# Create a new Control Capabilities:
#   Main:    Name Keekoon, Type = Remote, Protocol = Keekoon
#   Move:    Can Move, Can Move Diagonally, Can Move Continous
#   Pan:     Can Pan
#   Tilt:    Can Tilt
#   Presets: Has Presets, Num Presets = 6, Can Set Presets
#
# Set the ControlAddress in the camera definition, use the format:
#   http(s)://username:password@address:port
#
#   eg : http://admin:adminpass@10.10.10.1:80
#   or : https://admin:password@mycamera.example.co.uk:80
#
#   Return Location to Preset 1
#   Auto Stop Timeout = 0.5      is a good starting point
#
# ===========================================================================
# Problems: Enable debug and watch /tmp/zm_debug.log.<int> The
#           correct debug log can be found by date stamp.
#           Enable/disable the Source for the camera in the web GUI
#           each time you edit this script.  If the pid doesn't
#           change then you have not restarted it.    
# Errors like this:
# [Error in response to Request:'400 URL must be absolute']
# means that you have not specified all the parts in ControlAddress or the
# Regex has failed to parse it correctly
#
# =========================================================================
# Notes:  
# Example command from docs, at http://www.keekoonvision.com/for-developers-a: 
# Up: http://camera_ip:web_port/decoder_control.cgi?command=0&user=username&pwd=password
# However the camera actually uses basic auth and not user= etc
#
# Test URLs with something like this
# curl -XGET -u user:pass "http://cam.example.co.uk:80/decoder_control.cgi?command=1
#
# These cameras have a default admin user but can have six more defined
# with membership of three groups
# https is not directly supported but could be via say HA Proxy, so that
# is included rather than hardstrapping http://
# ==========================================================================

package ZoneMinder::Control::Keekoon;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Control;

our @ISA = qw(ZoneMinder::Control);

use ZoneMinder::Logger qw(:all);
use ZoneMinder::Config qw(:all);
use Time::HiRes qw( usleep );

sub open
{
    my $self = shift;

    $self->loadMonitor();

    use LWP::UserAgent;

    $self->{ua} = LWP::UserAgent->new;
    $self->{ua}->agent( "ZoneMinder Control Agent/".ZoneMinder::Base::ZM_VERSION );

    $self->{state} = 'open';

    Info( "Open" );

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
    my $result = undef;

    my ( $PROTOCOL, $USER, $PASS, $ADDR, $PORT ) 
        = $self->{Monitor}->{ControlAddress} =~ /^(https?):\/\/(.*):(.*)@(.*):(\d+)$/;
    my $URL = $PROTOCOL."://".$ADDR.":".$PORT."/decoder_control.cgi?command=".$cmd;

    Debug( "ControlAddress from camera Control setting:".$self->{Monitor}->{ControlAddress} );
    Debug( "URL parsed from ControlAddress:".$URL);

    my $req = HTTP::Request->new( GET=>$URL );
    
    # Do Basic Auth
    $req->authorization_basic($USER, $PASS);
    
    my $res = $self->{ua}->request($req);

    if ( $res->is_success )
    {
        $result = !undef;
    }
    else
    {
        Error( "Error in response to Request:'".$res->status_line()."'" );
    }

    return( $result );
}

# Set autoStop timeout on the Control tab for the camera
sub autoStop
{
    my $self = shift;
    my $stop_command = shift;
    my $autostop = shift;
    
    if( $stop_command && $autostop)
    {
        Debug( "Auto Stop" );
        usleep( $autostop );
        my $cmd = $stop_command;
        $self->sendCmd( $cmd );
    }
}

sub moveConUp
{
    my $self = shift;
    my $cmd = "0";
    my $stop_command = "1";
    Debug( "Move Up" );
    $self->sendCmd( $cmd );
    $self->autoStop( $stop_command, $self->{Monitor}->{AutoStopTimeout} );
}

sub moveConDown
{
    my $self = shift;
    my $cmd = "2";
    my $stop_command = "3";
    Debug( "Move Down" );
    $self->sendCmd( $cmd );
    $self->autoStop( $stop_command, $self->{Monitor}->{AutoStopTimeout} );
}

sub moveConLeft
{
    my $self = shift;
    my $cmd = "4";
    my $stop_command = "5";
    Debug( "Move Left" );
    $self->sendCmd( $cmd );
    $self->autoStop( $stop_command, $self->{Monitor}->{AutoStopTimeout} );
}

sub moveConRight
{
    my $self = shift;
    my $cmd = "6";
    my $stop_command = "7";
    Debug( "Move Right" );
    $self->sendCmd( $cmd );
    $self->autoStop( $stop_command, $self->{Monitor}->{AutoStopTimeout} );
}

sub moveConUpRight
{
    my $self = shift;
    Debug( "Move Diagonally Up Right" );
    $self->moveConUp( );
    $self->moveConRight( );
}

sub moveConDownRight
{
    my $self = shift;
    Debug( "Move Diagonally Down Right" );
    $self->moveConDown( );
    $self->moveConRight( );
}

sub moveConUpLeft
{
    my $self = shift;
    Debug( "Move Diagonally Up Left" );
    $self->moveConUp( );
    $self->moveConLeft( );
}

sub moveConDownLeft
{
    my $self = shift;
    Debug( "Move Diagonally Down Left" );
    $self->moveConDown( );
    $self->moveConLeft( );
}

# SET: 30,32,34,36,38,40 for presets 1-6
sub presetSet
{
    my $self = shift;
    my $params = shift;
    my $preset = $self->getParam( $params, 'preset' );

    Debug( "Set Preset No: " . $preset );

    if (( $preset >= 1 ) && ( $preset <= 6 )) {
        my $cmd = (($preset*2) + 28);
        $self->sendCmd( $cmd );
        Debug( "Set preset cmd: " . $cmd );
    }
    
}

# GOTO: 31,33,35,37,39,41 for presets 1-6
sub presetGoto
{
    my $self = shift;
    my $params = shift;
    my $preset = $self->getParam( $params, 'preset' );
    Debug( "Goto Preset No: " . $preset );

    if (( $preset >= 1 ) && ( $preset <= 6 )) {
        my $cmd = (($preset*2) + 29);
        $self->sendCmd( $cmd );
        Debug( "Goto Preset cmd: " . $cmd );
    }
    
}

1;
