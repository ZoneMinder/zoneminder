# Modified by PP to clean up user/auth dependencies inside the script
# Also, you can specify your auth credentials in the Control tab of the monitor
# In "ControlDevice" put in 
# usr=xxxx&pwd=xxx 
# where xxx is the auth credentials to your foscam camera
# The Foscam CGI manual referred to was v1.0.10
# All other notices below may be stale
#
# ==========================================================================
#
# ZoneMinder FOSCAM version 1.0 API Control Protocol Module, $Date$, $Revision$
# Copyright (C) 2001-2008  Philip Coombes
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
# =========================================================================================
#
# This module FI8620_Y2k.pm contains the implementation of API camera control
# For FOSCAM FI8620 Dome PTZ Camera (This cam support only H264 streaming)
# V1.0  Le 09 AOUT 2013 - production usable for the script but not for the camera "reboot itself"
# If you wan't to contact me i understand French and English, precise ZoneMinder in subject
# My name is Christophe DAPREMONT my email is christophe_y2k@yahoo.fr
#
# =========================================================================================
#
package ZoneMinder::Control::FI9831W;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Control;

our @ISA = qw(ZoneMinder::Control);
# ===================================================================================================================================
#
# FI9821 FOSCAM PT H264 Control Protocol
# with Firmware version V1.2.1.1 (latest at 09/08/2013)
# based with the latest buggy CGI doc from FOSCAM ( http://foscam.us/forum/cgi-sdk-for-hd-camera-t6045.html )
# This IPCAM work under ZoneMinder V1.25 from alternative source of code
# from this svn at https://svn.unixmedia.net/public/zum/trunk/zum/
# Many Thanks to "MASTERTHEKNIFE" for the excellent speed optimisation ( http://www.zoneminder.com/forums/viewtopic.php?f=9&t=17652 )
# And to "NEXTIME" for the recent source update and incredible plugins ( http://www.zoneminder.com/forums/viewtopic.php?f=9&t=20587 )
# And all people helping ZoneMinder dev.
#
# -FUNCTION: display on OSD
# speed is progressive in function of where you click on arrow ========>
#                                                    speed low=/       \=speed high
# ===================================================================================================================================
use ZoneMinder::Logger qw(:all);
use ZoneMinder::Config qw(:all);
use Time::HiRes qw( usleep );

# Set $osd to "off" if you wan't disabled OSD i need to place this variable in another script because
# this script is reload at every command ,if i want the button on/off (Focus MAN) for OSD works...
# PP - changed this to off - it achieves OSD by renaming the Device and what happens is at times
# it does not reset the name if a command fails. Net result: Your camera gets a name like "Move Left" which
# I bet you won't like
my $osd = "off";
my $cmd;

sub new
{
    my $class = shift;
    my $id = shift;
    my $self = ZoneMinder::Control->new( $id );
    bless( $self, $class );
    srand( time() );
    return $self;
}

our $AUTOLOAD;

sub AUTOLOAD
{
    my $self = shift;
    my $class = ref($self) || croak( "$self not object" );
    my $name = $AUTOLOAD;
    $name =~ s/.*://;
    if ( exists($self->{$name}) )
    {
        return( $self->{$name} );
    }
    Fatal( "Can't access $name member of object of class $class" );
}

sub open
{
    my $self = shift;
    $self->loadMonitor();
    use LWP::UserAgent;
    $self->{ua} = LWP::UserAgent->new;
    #PP
    #$self->{ua}->agent( "ZoneMinder Control Agent/".ZoneMinder::Base::ZM_VERSION );
    $self->{ua}->agent( "ZoneMinder Control Agent/" );
    $self->{state} = 'open';
}

sub close
{
    my $self = shift;
    $self->{state} = 'closed';
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
    printMsg( $cmd, "Tx" );
    my $temps = time();
    #PP - cleaned this up so it picks up the full auth from Control Devices
    my $req = HTTP::Request->new( GET=>"http://".$self->{Monitor}->{ControlAddress}."/cgi-bin/CGIProxy.fcgi?cmd=".$cmd."&".$self->{Monitor}->{ControlDevice} );
    #my $req = HTTP::Request->new( GET=>"http://".$self->{Monitor}->{ControlAddress}."/cgi-bin/CGIProxy.fcgi?usr%3Dadmin%26pwd%3D".$self->{Monitor}->{ControlDevice}."%26cmd%3D".$cmd."%26".$temps );
    my $res = $self->{ua}->request($req);
    if ( $res->is_success )
    {
        $result = !undef;
    }
    else
    {
        Error( "Error check failed: '".$res->status_line()."'" );
    }
    return( $result );
}

# PP - changed this to a system reboot. Its harmful to reset here. Settings may change
# with different firmware versions. Better to make this a reboot and use the camera
# interface to reset streams
sub reset
{   my $self = shift;
   Debug ( "Reboot= setup camera FoscamHD" );
   $cmd = "rebootSystem";
   #my $cmd = "setOSDSetting%26isEnableTimeStamp%3D0%26isEnableDevName%3D1%26dispPos%3D0%26isEnabledOSDMask%3D0";
   Info ("Sending reboot $cmd");
   $self->sendCmd( $cmd );
   # Setup For Stream=0 Resolution=720p Bandwidth=4M FPS=30 KeyFrameInterval/GOP=100 VBR=ON
   #$cmd = "setVideoStreamParam%26streamType%3D0%26resolution%3D0%26bitRate%3D4194304%26frameRate%3D30%26GOP%3D100%26isVBR%3D1";
   #$self->sendCmd( $cmd );
   # Setup For Infrared AUTO
   #$cmd = "setInfraLedConfig%26Mode%3D1";
   #$self->sendCmd( $cmd );
   # Reset image settings
   #$cmd = "resetImageSetting";
   #$self->sendCmd( $cmd );
}

sub moveStop
{
   my $self = shift;
   Debug( "Move Stop" );
        my $cmd = "ptzStopRun";
   $self->sendCmd( $cmd );
   if ($osd eq "on")
   {
        $cmd = "setDevName%26devName%3D.";
        $self->sendCmd( $cmd );
   	$cmd = "setOSDSetting%26isEnableDevName%3D1";
   	$self->sendCmd( $cmd );
   }
}

sub autoStop
{
    my $self = shift;
    my $autostop = shift;
    if( $autostop )
    {
       Debug( "Auto Stop" );
       usleep( $autostop );
       my $cmd = "ptzStopRun";
       $self->sendCmd( $cmd );
    }
}

sub moveConUp
{
    my $self = shift;
    my $params = shift;
    my $tiltspeed = $self->getParam( $params, 'tiltspeed' );
    # speed inverter 4-->0 , 3-->1 , 2-->2 , 1-->3 , 0-->4
    $tiltspeed = abs($tiltspeed - 4);
    # Normalisation en cas de valeur erronée dans la base de données
    if ( $tiltspeed > 4 ) {
            $tiltspeed = 4;
                }
    if ( $tiltspeed < 0 ) {
            $tiltspeed = 0;
                }
    Debug( "Move Up" );
    if ( $osd eq "on" )
   {
    my $cmd = "setDevName%26devName%3DMove Up $tiltspeed";
         $self->sendCmd( $cmd );
        }
    my $cmd = "setPTZSpeed%26speed%3D$tiltspeed";
    $self->sendCmd( $cmd );
    $cmd = "ptzMoveUp";
    $self->sendCmd( $cmd );
    $self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}

sub moveConDown
{
    my $self = shift;
    my $params = shift;
    my $tiltspeed = $self->getParam( $params, 'tiltspeed' );
    # speed inverter 4-->0 , 3-->1 , 2-->2 , 1-->3 , 0-->4
    $tiltspeed = abs($tiltspeed - 4);
    # Normalization
    if ( $tiltspeed > 4 ) {
            $tiltspeed = 4;
                }
    if ( $tiltspeed < 0 ) {
              $tiltspeed = 0;
                }
    Debug( "Move Down" );
    if ( $osd eq "on" )
   {
         my $cmd = "setDevName%26devName%3DMove Down $tiltspeed";
         $self->sendCmd( $cmd );
        }
    my $cmd = "setPTZSpeed%26speed%3D$tiltspeed";
    $self->sendCmd( $cmd );
    $cmd = "ptzMoveDown";
    $self->sendCmd( $cmd );
    $self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}

sub moveConLeft
{
    my $self = shift;
    my $params = shift;
    my $panspeed = $self->getParam( $params, 'panspeed' );
    # Normalisation en cas de valeur erronée dans la base de données
    if ( $panspeed > 4 ) {
            $panspeed = 4;
                }
    if ( $panspeed < 0 ) {
           $panspeed = 0;
               }
    Debug( "Move Left" );
    if ( $osd eq "on" )
   {
         my $cmd = "setDevName%26devName%3DMove Left $panspeed";
         $self->sendCmd( $cmd );
        }
    my $cmd = "setPTZSpeed%26speed%3D$panspeed";
    $self->sendCmd( $cmd );
    $cmd = "ptzMoveLeft";
    $self->sendCmd( $cmd );
    $self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}


sub moveConRight
{
    my $self = shift;
    my $params = shift;
    my $panspeed = $self->getParam( $params, 'panspeed' );
    # speed inverter 4-->0 , 3-->1 , 2-->2 , 1-->3 , 0-->4
    $panspeed = abs($panspeed - 4);
    # Normalisation en cas de valeur erronée dans la base de données
    if ( $panspeed > 4 ) {
                $panspeed = 4;
               }
    if ( $panspeed < 0 ) {
           $panspeed = 0;
               }
    Debug( "Move Right" );
    if ( $osd eq "on" )
   {
         my $cmd = "setDevName%26devName%3DMove Right $panspeed";
         $self->sendCmd( $cmd );
        }
    my $cmd = "setPTZSpeed%26speed%3D$panspeed";
    $self->sendCmd( $cmd );
    $cmd = "ptzMoveRight";
    $self->sendCmd( $cmd );
    $self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}

sub moveConUpLeft
{
    my $self = shift;
    my $params = shift;
    my $tiltspeed = $self->getParam( $params, 'tiltspeed' );
    # speed inverter 4-->0 , 3-->1 , 2-->2 , 1-->3 , 0-->4
    $tiltspeed = abs($tiltspeed - 4);
    # Normalisation en cas de valeur erronée dans la base de données
    if ( $tiltspeed > 4 ) {
            $tiltspeed = 4;
                }
    if ( $tiltspeed < 0 ) {
            $tiltspeed = 0;
                }
        Debug( "Move Con Up Left" );
    if ( $osd eq "on" )
   {
         my $cmd = "setDevName%26devName%3DMove Up Left $tiltspeed";
         $self->sendCmd( $cmd );
        }
    my $cmd = "setPTZSpeed%26speed%3D$tiltspeed";
    $self->sendCmd( $cmd );
    $cmd = "ptzMoveTopLeft";
    $self->sendCmd( $cmd );
    $self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}

sub moveConUpRight
{
    my $self = shift;
    my $params = shift;
    my $tiltspeed = $self->getParam( $params, 'tiltspeed' );
    # speed inverter 4-->0 , 3-->1 , 2-->2 , 1-->3 , 0-->4
    $tiltspeed = abs($tiltspeed - 4);
    # Normalisation en cas de valeur erronée dans la base de données
    if ( $tiltspeed > 4 ) {
            $tiltspeed = 4;
                }
    if ( $tiltspeed < 0 ) {
            $tiltspeed = 0;
                }
    Debug( "Move Con Up Right" );
    if ( $osd eq "on" )
   {
         my $cmd = "setDevName%26devName%3DMove Up Right $tiltspeed";
         $self->sendCmd( $cmd );
        }
    my $cmd = "setPTZSpeed%26speed%3D$tiltspeed";
    $self->sendCmd( $cmd );
    $cmd = "ptzMoveTopRight";
    $self->sendCmd( $cmd );
    $self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}

sub moveConDownLeft
{
    my $self = shift;
    my $params = shift;
    my $tiltspeed = $self->getParam( $params, 'tiltspeed' );
    # speed inverter 4-->0 , 3-->1 , 2-->2 , 1-->3 , 0-->4
    $tiltspeed = abs($tiltspeed - 4);
    # Normalisation en cas de valeur erronée dans la base de données
    if ( $tiltspeed > 4 ) {
            $tiltspeed = 4;
                }
    if ( $tiltspeed < 0 ) {
            $tiltspeed = 0;
                }
    Debug( "Move Con Down Left" );
    if ( $osd eq "on" )
   {
         my $cmd = "setDevName%26devName%3DMove Down Left $tiltspeed";
         $self->sendCmd( $cmd );
        }
    my $cmd = "setPTZSpeed%26speed%3D$tiltspeed";
    $self->sendCmd( $cmd );
    $cmd = "ptzMoveBottomLeft";
    $self->sendCmd( $cmd );
    $self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}

sub moveConDownRight
{
    my $self = shift;
    my $params = shift;
    my $tiltspeed = $self->getParam( $params, 'tiltspeed' );
    # speed inverter 4-->0 , 3-->1 , 2-->2 , 1-->3 , 0-->4
    $tiltspeed = abs($tiltspeed - 4);
    # Normalisation en cas de valeur erronée dans la base de données
    if ( $tiltspeed > 4 ) {
            $tiltspeed = 4;
                }
    if ( $tiltspeed < 0 ) {
            $tiltspeed = 0;
                }
    Debug( "Move Con Down Right" );
    if ( $osd eq "on" )
   {
    my $cmd = "setDevName%26devName%3DMove Down Right $tiltspeed";
         $self->sendCmd( $cmd );
        }
    my $cmd = "setPTZSpeed%26speed%3D$tiltspeed";
    $self->sendCmd( $cmd );
    $cmd = "ptzMoveBottomRight";
    $self->sendCmd( $cmd );
    $self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}

sub zoomConTele
{
    my $self = shift;
    Debug( "Zoom-Tele=MANU IR LED ON" );
    if ( $osd eq "on" )
   {
         my $cmd = "setDevName%26devName%3DManual IR LED Switch ON";
         $self->sendCmd( $cmd );
        }
    my $cmd = "setInfraLedConfig%26mode%3D1";
    $self->sendCmd( $cmd );
    $cmd = "openInfraLed";
    $self->sendCmd( $cmd );
}

sub zoomConWide
{
    my $self = shift;
    Debug( "Zoom-Wide=MANU IR LED OFF" );
    if ( $osd eq "on" )
   {
         my $cmd = "setDevName%26devName%3DManual IR LED Switch OFF";
         $self->sendCmd( $cmd );
        }
    my $cmd = "setInfraLedConfig%26mode%3D1";
    $self->sendCmd( $cmd );
    $cmd = "closeInfraLed";
    $self->sendCmd( $cmd );
}

sub wake
{
    my $self = shift;
    Debug( "Wake=AUTO IR LED" );
    if ( $osd eq "on" )
   {
         my $cmd = "setDevName%26devName%3DAuto IR LED Mode";
         $self->sendCmd( $cmd );
        }
    my $cmd = "setInfraLedConfig%26mode%3D0";
    $self->sendCmd( $cmd );
}

sub focusConNear
{
    my $self = shift;
    my $params = shift;
    my $speed = $self->getParam( $params, 'speed' );
    # Normalisation en cas de valeur erronée dans la base de données
    if ( $speed > 100 ) {
                $speed = 100;
                  }
    if ( $speed < 0 ) {
             $speed = 0;
            }
    Debug( "Focus Near=Sharpness" );
    if ( $osd eq "on" )
   {
         my $cmd = "setDevName%26devName%3DSharpness $speed";
         $self->sendCmd( $cmd );
         $cmd = "setOSDSetting%26isEnableDevName%3D1";
    $self->sendCmd( $cmd );
        }
    my $cmd = "setSharpness%26sharpness%3D$speed";
    $self->sendCmd( $cmd );
    # La variable speed ne fonctionne pas en paramètre du focus, alors je l'utilise pour définir la durée de la commande
    # le résulat est identique
    $self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}

sub focusConFar
{
    my $self = shift;
    my $params = shift;
    my $speed = $self->getParam( $params, 'speed' );
    # Normalisation en cas de valeur erronée dans la base de données
    if ( $speed > 100 ) {
               $speed = 100;
              }
    if ( $speed < 0 ) {
             $speed = 0;
            }
    Debug( "Focus Far" );
    if ( $osd eq "on" )
   {
         my $cmd = "setDevName%26devName%3DSharpness $speed";
         $self->sendCmd( $cmd );
        }
    my $cmd = "setSharpness%26sharpness%3D$speed";
    $self->sendCmd( $cmd );
    # La variable speed ne fonctionne pas en paramètre du focus alors je l'utilise pour définir la durée de la commande
    # le résulat est identique
    $self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}

sub focusAuto
{
    my $self = shift;
    Debug( "Focus Auto=Reset Sharpness" );
    if ( $osd eq "on" )
   {
         my $cmd = "setDevName%26devName%3DReset Sharpness";
         $self->sendCmd( $cmd );
        }
    my $cmd = "setSharpness%26sharpness%3D10";
    $self->sendCmd( $cmd );
    $self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}

sub focusMan
{
    my $self = shift;
    Debug( "Focus Manu=Reset Sharpness" );
    if ( $osd eq "on" )
   {
         my $cmd = "setDevName%26devName%3DFOSCAM FI9821W Script V1.0 By Christophe_y2k";
         $self->sendCmd( $cmd );
        }
}

sub whiteConIn
{
    my $self = shift;
    my $params = shift;
    my $speed = $self->getParam( $params, 'speed' );
    # Normalisation en cas de valeur erronée dans la base de données
    if ( $speed > 100 ) {
                $speed = 100;
              }
    if ( $speed < 0 ) {
             $speed = 0;
            }
    Debug( "White ConIn=brightness" );
    if ( $osd eq "on" )
   {
         my $cmd = "setDevName%26devName%3DBrightness $speed";
         $self->sendCmd( $cmd );
        }
    my $cmd = "setBrightness%26brightness%3D$speed";
    $self->sendCmd( $cmd );
    $self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}

sub whiteConOut
{
    my $self = shift;
    my $params = shift;
    my $speed = $self->getParam( $params, 'speed' );
    # Normalisation en cas de valeur erronée dans la base de données
    if ( $speed > 100 ) {
                $speed = 100;
              }
    if ( $speed < 0 ) {
             $speed = 0;
            }
    Debug( "White ConOut=Contrast" );
    if ( $osd eq "on" )
   {
         my $cmd = "setDevName%26devName%3DContrast $speed";
         $self->sendCmd( $cmd );
        }
    my $cmd = "setContrast%26constrast%3D$speed";
    $self->sendCmd( $cmd );
    $self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}

sub whiteAuto
{
    my $self = shift;
    Debug( "White Auto=Brightness Reset" );
    if ( $osd eq "on" )
   {
         my $cmd = "setDevName%26devName%3DBrightness Reset";
         $self->sendCmd( $cmd );
        }
    my $cmd = "setBrightness%26brightness%3D50";
    $self->sendCmd( $cmd );
    $self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}

sub whiteMan
{
    my $self = shift;
    Debug( "White Manuel=Contrast Reset" );
    if ( $osd eq "on" )
   {
         my $cmd = "setDevName%26devName%3DContrast Reset";
         $self->sendCmd( $cmd );
        }
    my $cmd = "setContrast%26constrast%3D44";
    $self->sendCmd( $cmd );
    $self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}

sub irisConOpen
{
    my $self = shift;
    my $params = shift;
    my $speed = $self->getParam( $params, 'speed' );
    # Normalisation en cas de valeur erronée dans la base de données
    if ( $speed > 100 ) {
               $speed = 100;
              }
    if ( $speed < 0 ) {
             $speed = 0;
            }
    Debug( "Iris ConOpen=Saturation" );
    if ( $osd eq "on" )
   {
         my $cmd = "setDevName%26devName%3DSaturation $speed";
         $self->sendCmd( $cmd );
        }
    my $cmd = "setSaturation%26saturation%3D$speed";
    $self->sendCmd( $cmd );
    $self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}

sub irisConClose
{
    my $self = shift;
    my $params = shift;
    my $speed = $self->getParam( $params, 'speed' );
    # Normalisation en cas de valeur erronée dans la base de données
    if ( $speed > 100 ) {
               $speed = 100;
              }
    if ( $speed < 0 ) {
             $speed = 0;
            }
    Debug( "Iris ConClose=Hue" );
    if ( $osd eq "on" )
   {
         my $cmd = "setDevName%26devName%3DHue $speed";
         $self->sendCmd( $cmd );
        }
    my $cmd = "setHue%26hue%3D$speed";
    $self->sendCmd( $cmd );
    $self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}

sub irisAuto
{
    my $self = shift;
    Debug( "Iris Auto=Saturation Reset" );
    if ( $osd eq "on" )
   {
         my $cmd = "setDevName%26devName%3DSaturation Reset";
         $self->sendCmd( $cmd );
        }
    my $cmd = "setSaturation%26saturation%3D30";
    $self->sendCmd( $cmd );
    $self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}

sub irisMan
{
    my $self = shift;
    Debug( "Iris Manuel=Hue Reset" );
    if ( $osd eq "on" )
   {
         my $cmd = "setDevName%26devName%3DHue Reset";
         $self->sendCmd( $cmd );
        }
    my $cmd = "setHue%26hue%3D6";
    $self->sendCmd( $cmd );
    $self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}

sub presetSet
{
    my $self = shift;
    my $params = shift;
    my $preset = $self->getParam( $params, 'preset' );
    if ( ( $preset >= 1 ) && ( $preset <= 16 ) ) {
                        Debug( "Clear Preset $preset" );
                        my $cmd = "ptzDeletePresetPoint%26name%3D$preset";
                               $self->sendCmd( $cmd );
                                                  Debug( "Set Preset $preset" );
                         if ( $osd eq "on" )
                       {
                                    my $cmd = "setDevName%26devName%3DSet Preset $preset";
                                       $self->sendCmd( $cmd );
                      }
                                                  $cmd = "ptzAddPresetPoint%26name%3D$preset";
                                                  $self->sendCmd( $cmd );
                        }
}

sub presetGoto
{
    my $self = shift;
    my $params = shift;
    my $preset = $self->getParam( $params, 'preset' );
    if ( ( $preset >= 1 ) && ( $preset <= 16 ) ) {
                        Debug( "Goto Preset $preset" );
                        if ( $osd eq "on" )
                     {
                      my $cmd = "setDevName%26devName%3DGoto Preset $preset";
                                      $self->sendCmd( $cmd );
                      }
                   my $cmd = "setPTZSpeed%26speed%3D0";
                        $self->sendCmd( $cmd );
                        $cmd = "ptzGotoPresetPoint%26name%3D$preset";
                        $self->sendCmd( $cmd );
                       }
}

1;
__END__
# Below is stub documentation for your module. You'd better edit it!

=head1 FI9831W

ZoneMinder::Database - Perl extension for FOSCAM FI9831W

=head1 SYNOPSIS

Control script for Foscam HD cameras. Tested on 9831W but
should work on others too.

=head1 DESCRIPTION

Control script for Foscam HD cameras. Tested on 9831W but
should work on others too.
You need to set "usr=xxx&pwd=yyy" in the ControlDevice field
of the control tab for that monitor.
Auto TimeOut should be 1. Don't set it to less - processes
start crashing :)

=head2 EXPORT

None by default.



=head1 SEE ALSO

=head1 AUTHOR

Philip Coombes, E<lt>philip.coombes@zoneminder.comE<gt>

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2001-2008  Philip Coombes

This library is free software; you can redistribute it and/or modify
it under the same terms as Perl itself, either Perl version 5.8.3 or,
at your option, any later version of Perl 5 you may have available.


=cut

