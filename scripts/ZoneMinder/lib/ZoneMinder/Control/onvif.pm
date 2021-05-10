# ==========================================================================
#
# ZoneMinder ONVIF Control Protocol Module
# Copyright (C) Jan M. Hochstein
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
# This module contains the implementation of the ONVIF device control protocol
#
package ZoneMinder::Control::onvif;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Control;

our @ISA = qw(ZoneMinder::Control);

our %CamParams = ();

# ==========================================================================
#
# ONVIF Control Protocol
#
# On ControlAddress use the format :
#   USERNAME:PASSWORD@ADDRESS:PORT
#   eg : admin:@10.1.2.1:80
#        zoneminder:zonepass@10.0.100.1:40000
#
# ==========================================================================

use ZoneMinder::Logger qw(:all);
use ZoneMinder::Config qw(:all);

use Time::HiRes qw( usleep );

sub open {
  my $self = shift;

  $self->loadMonitor();

  use LWP::UserAgent;
  $self->{ua} = LWP::UserAgent->new;
  $self->{ua}->agent('ZoneMinder Control Agent/'.ZoneMinder::Base::ZM_VERSION);

  $self->{state} = 'open';
}

sub sendCmd {
  my $self = shift;
  my $cmd = shift;
  my $result = undef;
  printMsg($cmd, 'Tx');

  my $req = HTTP::Request->new(GET=>'http://'.$self->{Monitor}->{ControlAddress}.'/'.$cmd);
  my $res = $self->{ua}->request($req);

  if ( $res->is_success ) {
    $result = !undef;
  } else {
    Error("Error check failed:'".$res->status_line()."'" );
  }

  return $result;
}

sub getCamParams {
  my $self = shift;

  my $req = HTTP::Request->new(GET=>'http://'.$self->{Monitor}->{ControlAddress}.'/get_camera_params.cgi');
  my $res = $self->{ua}->request($req);

  if ( $res->is_success ) {
    # Parse results setting values in %FCParams
    my $content = $res->decoded_content;

    while ($content =~ s/var\s+([^=]+)=([^;]+);//ms) {
      $CamParams{$1} = $2;
    }
  } else {
    Error("Error check failed:'".$res->status_line()."'");
  }
}

#autoStop
#This makes use of the ZoneMinder Auto Stop Timeout on the Control Tab
sub autoStop {
  my $self = shift;
  my $stop_command = shift;
  my $autostop = shift;
  if ( $stop_command && $autostop ) {
    Debug('Auto Stop');
    usleep($autostop);
    my $cmd = 'decoder_control.cgi?command='.$stop_command;
    $self->sendCmd($cmd);
  }
}

# Reset the Camera
sub reset {
  my $self = shift;
  Debug('Camera Reset');
  my $cmd = 'reboot.cgi?';
  $self->sendCmd($cmd);
}

#Up Arrow
sub moveConUp
{
  my $self = shift;
  my $stop_command = "1";
  Debug( "Move Up" );
  my $cmd = "decoder_control.cgi?command=0";
  $self->sendCmd( $cmd );
  $self->autoStop( $stop_command, $self->{Monitor}->{AutoStopTimeout} );
}

#Down Arrow
sub moveConDown
{
  my $self = shift;
  my $stop_command = "3";
  Debug( "Move Down" );
  my $cmd = "decoder_control.cgi?command=2";
  $self->sendCmd( $cmd );
  $self->autoStop( $stop_command, $self->{Monitor}->{AutoStopTimeout} );
}

#Left Arrow
sub moveConLeft
{
  my $self = shift;
  my $stop_command = "5";
  Debug( "Move Left" );
  my $cmd = "decoder_control.cgi?command=4";
  $self->sendCmd( $cmd );
  $self->autoStop( $stop_command, $self->{Monitor}->{AutoStopTimeout} );
}

#Right Arrow
sub moveConRight
{
  my $self = shift;
  my $stop_command = "7";
  Debug( "Move Right" );
  my $cmd = "decoder_control.cgi?command=6";
  $self->sendCmd( $cmd );
  $self->autoStop( $stop_command, $self->{Monitor}->{AutoStopTimeout} );
}

#Zoom In
sub zoomConTele
{
  my $self = shift;
  my $stop_command = "17";
  Debug( "Zoom Tele" );
  my $cmd = "decoder_control.cgi?command=18";
  $self->sendCmd( $cmd );
  $self->autoStop( $stop_command, $self->{Monitor}->{AutoStopTimeout} );
}

#Zoom Out
sub zoomConWide
{
  my $self = shift;
  my $stop_command = "19";
  Debug( "Zoom Wide" );
  my $cmd = "decoder_control.cgi?command=16";
  $self->sendCmd( $cmd );
  $self->autoStop( $stop_command, $self->{Monitor}->{AutoStopTimeout} );
}

#Diagonally Up Right Arrow
#This camera does not have builtin diagonal commands so we emulate them
sub moveConUpRight
{
  my $self = shift;
  Debug( "Move Diagonally Up Right" );
  $self->moveConUp( );
  $self->moveConRight( );
}

#Diagonally Down Right Arrow
#This camera does not have builtin diagonal commands so we emulate them
sub moveConDownRight
{
  my $self = shift;
  Debug( "Move Diagonally Down Right" );
  $self->moveConDown( );
  $self->moveConRight( );
}

#Diagonally Up Left Arrow
#This camera does not have builtin diagonal commands so we emulate them
sub moveConUpLeft
{
  my $self = shift;
  Debug( "Move Diagonally Up Left" );
  $self->moveConUp( );
  $self->moveConLeft( );
}

#Diagonally Down Left Arrow
#This camera does not have builtin diagonal commands so we emulate them
sub moveConDownLeft
{
  my $self = shift;
  Debug( "Move Diagonally Down Left" );
  $self->moveConDown( );
  $self->moveConLeft( );
}

#Stop
sub moveStop
{
  my $self = shift;
  Debug( "Move Stop" );
  my $cmd = "decoder_control.cgi?command=1";
  $self->sendCmd( $cmd );
}

#Set Camera Preset
#Presets must be translated into values internal to the camera
#Those values are: 30,32,34,36,38,40,42,44 for presets 1-8 respectively
sub presetSet
{
  my $self = shift;
  my $params = shift;
  my $preset = $self->getParam( $params, 'preset' );
  Debug( "Set Preset $preset" );

  if (( $preset >= 1 ) && ( $preset <= 8 )) {
    my $cmd = "decoder_control.cgi?command=".(($preset*2) + 28);
    $self->sendCmd( $cmd );
  }
}

#Recall Camera Preset
#Presets must be translated into values internal to the camera
#Those values are: 31,33,35,37,39,41,43,45 for presets 1-8 respectively
sub presetGoto
{
  my $self = shift;
  my $params = shift;
  my $preset = $self->getParam( $params, 'preset' );
  Debug( "Goto Preset $preset" );

  if (( $preset >= 1 ) && ( $preset <= 8 )) {
    my $cmd = "decoder_control.cgi?command=".(($preset*2) + 29);
    $self->sendCmd( $cmd );
  }

  if ( $preset == 9 ) {
    $self->horizontalPatrol();
  }

  if ( $preset == 10 ) {
    $self->horizontalPatrolStop();
  }
}

#Horizontal Patrol - Vertical Patrols are not supported
sub horizontalPatrol
{
  my $self = shift;
  Debug( "Horizontal Patrol" );
  my $cmd = "decoder_control.cgi?command=20";
  $self->sendCmd( $cmd );
}

#Horizontal Patrol Stop
sub horizontalPatrolStop
{
  my $self = shift;
  Debug( "Horizontal Patrol Stop" );
  my $cmd = "decoder_control.cgi?command=21";
  $self->sendCmd( $cmd );
}

# Increase Brightness
sub irisAbsOpen
{
  my $self = shift;
  my $params = shift;
  $self->getCamParams() unless($CamParams{'brightness'});
  my $step = $self->getParam( $params, 'step' );

  $CamParams{'brightness'} += $step;
  $CamParams{'brightness'} = 255 if ($CamParams{'brightness'} > 255);
  Debug( "Iris $CamParams{'brightness'}" );
  my $cmd = "camera_control.cgi?param=1&value=".$CamParams{'brightness'};
  $self->sendCmd( $cmd );
}

# Decrease Brightness
sub irisAbsClose
{
  my $self = shift;
  my $params = shift;
  $self->getCamParams() unless($CamParams{'brightness'});
  my $step = $self->getParam( $params, 'step' );

  $CamParams{'brightness'} -= $step;
  $CamParams{'brightness'} = 0 if ($CamParams{'brightness'} < 0);
  Debug( "Iris $CamParams{'brightness'}" );
  my $cmd = "camera_control.cgi?param=1&value=".$CamParams{'brightness'};
  $self->sendCmd( $cmd );
}

# Increase Contrast
sub whiteAbsIn
{
  my $self = shift;
  my $params = shift;
  $self->getCamParams() unless($CamParams{'contrast'});
  my $step = $self->getParam( $params, 'step' );

  $CamParams{'contrast'} += $step;
  $CamParams{'contrast'} = 6 if ($CamParams{'contrast'} > 6);
  Debug( "Iris $CamParams{'contrast'}" );
  my $cmd = "camera_control.cgi?param=2&value=".$CamParams{'contrast'};
  $self->sendCmd( $cmd );
}

# Decrease Contrast
sub whiteAbsOut
{
  my $self = shift;
  my $params = shift;
  $self->getCamParams() unless($CamParams{'contrast'});
  my $step = $self->getParam( $params, 'step' );

  $CamParams{'contrast'} -= $step;
  $CamParams{'contrast'} = 0 if ($CamParams{'contrast'} < 0);
  Debug( "Iris $CamParams{'contrast'}" );
  my $cmd = "camera_control.cgi?param=2&value=".$CamParams{'contrast'};
  $self->sendCmd( $cmd );
}

1;

