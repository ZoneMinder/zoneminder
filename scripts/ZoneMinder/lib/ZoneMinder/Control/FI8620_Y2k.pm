# V1.1 ====================================================================================
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
# V1.1 ====================================================================================
#
# This module FI8620_Y2k.pm contains the implementation of API camera control
# For FOSCAM FI8620 Dome PTZ Camera (This cam support only H264 streaming)
# V0.1b Le 01 JUIN 2013
# V0.2b Le 11 JUILLET 2013
# V0.5b Le 24 JUILLET 2013
# V0.6b Le 01 AOUT 2013 -
# V1.0  Le 04 AOUT 2013 - production usable if you do not use preset ptz
# V1.1  Le 11 AOUT 2013 - put a cosmetic update source code
# If you wan't to contact me i understand French and English, precise ZoneMinder in subject
# My name is Christophe DAPREMONT my email is christophe_y2k@yahoo.fr
#
# V1.1 ====================================================================================
package ZoneMinder::Control::FI8620_Y2k;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Control;

our @ISA = qw(ZoneMinder::Control);
# ===================================================================================================================================
#
# FI8620 FOSCAM Dome PTZ H264 Control Protocol
# with Firmware version V3.2.2.2.1-20120815 (latest at 04/08/2013)
# based with the latest buggy CGI doc from FOSCAM ( http://foscam.us/forum/cgi-api-sdk-for-mjpeg-h-264-camera-t2986.html )
# This IPCAM work under ZoneMinder V1.25 from alternative source of code
# from this svn at https://svn.unixmedia.net/public/zum/trunk/zum/
# Many Thanks to "MASTERTHEKNIFE" for the excellent speed optimisation ( http://www.zoneminder.com/forums/viewtopic.php?f=9&t=17652 )
# And to "NEXTIME" for the recent source update and incredible plugins ( http://www.zoneminder.com/forums/viewtopic.php?f=9&t=20587 )
# And all people helping ZoneMinder dev.
#
# -FUNCTIONALITIES:
# -Move camera in 8 direction with arrow, the speed of movement is function
#  of the position of your mouse on the arrow.
#  Extremity of arrow equal to fastest speed of movement
#  Close the base of arrow to lowest speed of movement
#  for diagonaly you can click before the beginning of the arrow for low speed
#  In round center equal to stop to move
# -You can clic directly on the image that equal to click on arrow (for the left there is a bug in zoneminder speed is inverted)
# -Zoom Tele/Wide with time control to simulate speed because speed value do not work (buggy firmware or not implemented on this cam)
# -Focus Near/Far with time control to simulate speed because speed value do not work (buggy firmware or not implemented on this cam)
# -Autofocus is automatic when you move again or can be setting by autofocus button
# -8 Preset PTZ are implemented but the firmware is buggy and that do not work
#  You Need to configure ZoneMinder PANSPEED & TILTSEPPED & ZOOMSPEED 1 to 63 by 1 step
# -This Script use for login "admin" this hardcoded and your password must setup in "Control Device" section
# -This script is compatible with the basic authentification method used by mostly new camera
# -AutoStop function is active and you must set up value (in sec example 0.5) under AutoStop section
#  or you can set up to 0 for disable it but the camera never stop to move and trust me, she can move all the night...
#  (you need to click to the center arrow for stop)
# -"White In" to control Brightness, "auto" for restore the original value of Brightness
# -"White Out" to control Contrast, "man" for restore the original value of Contrast
# -"Iris Open" to control Saturation , "auto" for restore the original value of Saturation
# -"Iris Close" to control Hue , "man" for restore the original value of Hue
# -Another cool stuff i use the OSD function of this cam for printing the command with the value
#  The button of Focus "Man" is for enable or disable OSD but that do not work ( it's my bug... i'm very very new with perl )
# ===================================================================================================================================
use ZoneMinder::Logger qw(:all);
use ZoneMinder::Config qw(:all);
use Time::HiRes qw( usleep );

# Set $osd to "off" if you wan't disabled OSD i need to place this variable in another script because
# this script is reload at every command ,if i want the button on/off (Focus MAN) for OSD works...
my $osd = "on";

sub open
{
    my $self = shift;
    $self->loadMonitor();
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
    my $result = undef;
    printMsg( $cmd, "Tx" );
    # I solve the authentification problem with recent Foscam
    # I use perl Basic Authentification method
    my $ua = LWP::UserAgent->new();
    my $req = HTTP::Request->new( GET =>"http://".$self->{Monitor}->{ControlAddress}."/web/cgi-bin/hi3510/".$cmd );
    $req->authorization_basic('admin', $self->{Monitor}->{ControlDevice} );
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

sub moveStop
{
   my $self = shift;
   Debug( "Move Stop" );
   my $cmd = "ptzctrl.cgi?-step=0&-act=stop";
   $self->sendCmd( $cmd );
        my $cmd = "param.cgi?cmd=setoverlayattr&-region=1&-show=0&-name=.";
        $self->sendCmd( $cmd );
}

sub autoStop
{
    my $self = shift;
    my $autostop = shift;
    if( $autostop )
    {
       Debug( "Auto Stop" );
       usleep( $autostop );
       my $cmd = "ptzctrl.cgi?-step=0&-act=stop";
       $self->sendCmd( $cmd );
       my $cmd = "param.cgi?cmd=setoverlayattr&-region=1&-show=0&-name=.";
       $self->sendCmd( $cmd );
    }
}

sub moveConUp
{
    my $self = shift;
    my $params = shift;
    my $tiltspeed = $self->getParam( $params, 'tiltspeed' );
    # Standardization for incorrect possible value in the database, and for realise at low and high speed an more precise moving
    if ( $tiltspeed > 59 ) {
             $tiltspeed = 63;
                 }
    if ( $tiltspeed < 6 ) {
            $tiltspeed = 1;
                }
    Debug( "Move Up" );   
    if ( $osd eq "on" )
   {
         my $cmd = "param.cgi?cmd=setoverlayattr&-region=1&-show=1&-name=Move Up $tiltspeed";
         $self->sendCmd( $cmd );
        }
    my $cmd = "ptzctrl.cgi?-step=0&-act=up&-speed=$tiltspeed";
    $self->sendCmd( $cmd );
    $self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}

sub moveConDown
{
    my $self = shift;
    my $params = shift;
    my $tiltspeed = $self->getParam( $params, 'tiltspeed' );
    # Standardization for incorrect possible value in the database, and for realise at low and high speed an more precise moving
    if ( $tiltspeed > 59 ) {
             $tiltspeed = 63;
                 }
    if ( $tiltspeed < 6 ) {
              $tiltspeed = 1;
                }
    Debug( "Move Down" );
    if ( $osd eq "on" )
   {
         my $cmd = "param.cgi?cmd=setoverlayattr&-region=1&-show=1&-name=Move Down $tiltspeed";
         $self->sendCmd( $cmd );
        }
    my $cmd = "ptzctrl.cgi?-step=0&-act=down&-speed=$tiltspeed";
    $self->sendCmd( $cmd );
    $self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}

sub moveConLeft
{
    my $self = shift;
    my $params = shift;
    my $panspeed = $self->getParam( $params, 'panspeed' );
    # Standardization for incorrect possible value in the database, and for realise at low and high speed an more precise moving
    if ( $panspeed > 59 ) {
            $panspeed = 63;
                }
    if ( $panspeed < 6 ) {
           $panspeed = 1;
               }
    # Algorithme pour inverser la table de valeur de la flèche gauche, (for invert the value) 63 ==> 1 et 1 ==> 63 ...
    $panspeed = abs($panspeed - 63) + 1;
    Debug( "Move Left" );
    if ( $osd eq "on" )
   {
         my $cmd = "param.cgi?cmd=setoverlayattr&-region=1&-show=1&-name=Move Left $panspeed";
         $self->sendCmd( $cmd );
        }
    my $cmd = "ptzctrl.cgi?-step=0&-act=left&-speed=$panspeed";
    $self->sendCmd( $cmd );
    $self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}

sub moveConRight
{
    my $self = shift;
    my $params = shift;
    my $panspeed = $self->getParam( $params, 'panspeed' );
    # Standardization for incorrect possible value in the database, and for realise at low and high speed an more precise moving
    if ( $panspeed > 59 ) {
            $panspeed = 63;
                }
    if ( $panspeed < 6 ) {
           $panspeed = 1;
               }
    Debug( "Move Right" );
    if ( $osd eq "on" )
   {
         my $cmd = "param.cgi?cmd=setoverlayattr&-region=1&-show=1&-name=Move Right $panspeed";
         $self->sendCmd( $cmd );
        }
    my $cmd = "ptzctrl.cgi?-step=0&-act=right&-speed=$panspeed";
    $self->sendCmd( $cmd );
    $self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}

sub moveConUpLeft
{
    my $self = shift;
    my $params = shift;
    my $tiltspeed = $self->getParam( $params, 'tiltspeed' );
    # Standardization for incorrect possible value in the database, and for realise at low and high speed an more precise moving
    if ( $tiltspeed > 59 ) {
             $tiltspeed = 63;
                 }
    if ( $tiltspeed < 6 ) {
            $tiltspeed = 1;
                }
    my $panspeed = $self->getParam( $params, 'panspeed' );
    # Standardization for incorrect value in the database
    if ( $panspeed > 59 ) {
            $panspeed = 63;
                }
    if ( $panspeed < 6 ) {
           $panspeed = 1;
               }
    Debug( "Move Con Up Left" );
    if ( $osd eq "on" )
   {
         my $cmd = "param.cgi?cmd=setoverlayattr&-region=1&-show=1&-name=Up $tiltspeed Left $panspeed";
         $self->sendCmd( $cmd );
        }
    my $cmd = "ptzctrl.cgi?-step=0&-act=up&-speed=$tiltspeed";
    $self->sendCmd( $cmd );
    my $cmd = "ptzctrl.cgi?-step=0&-act=left&-speed=$panspeed";
    $self->sendCmd( $cmd );
    $self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}

sub moveConUpRight
{
    my $self = shift;
    my $params = shift;
    my $tiltspeed = $self->getParam( $params, 'tiltspeed' );
    # Standardization for incorrect possible value in the database, and for realise at low and high speed an more precise moving
    if ( $tiltspeed > 59 ) {
             $tiltspeed = 63;
                 }
    if ( $tiltspeed < 6 ) {
            $tiltspeed = 1;
                }
    my $panspeed = $self->getParam( $params, 'panspeed' );
    # Standardization for incorrect possible value in the database, and for realise at low and high speed an more precise moving
    if ( $panspeed > 59 ) {
            $panspeed = 63;
                }
    if ( $panspeed < 6 ) {
           $panspeed = 1;
               }
    Debug( "Move Con Up Right" );
    if ( $osd eq "on" )
   {
         my $cmd = "param.cgi?cmd=setoverlayattr&-region=1&-show=1&-name=Up $tiltspeed Right $panspeed";
         $self->sendCmd( $cmd );
        }
    my $cmd = "ptzctrl.cgi?-step=0&-act=up&-speed=$tiltspeed";
    $self->sendCmd( $cmd );
    my $cmd = "ptzctrl.cgi?-step=0&-act=right&-speed=$panspeed";
    $self->sendCmd( $cmd );
    $self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}

sub moveConDownLeft
{
    my $self = shift;
    my $params = shift;
    my $tiltspeed = $self->getParam( $params, 'tiltspeed' );
    # Standardization for incorrect possible value in the database, and for realise at low and high speed an more precise moving
    if ( $tiltspeed > 59 ) {
              $tiltspeed = 63;
                 }
    if ( $tiltspeed < 6 ) {
            $tiltspeed = 1;
                }
    my $panspeed = $self->getParam( $params, 'panspeed' );
    # Standardization for incorrect possible value in the database, and for realise at low and high speed an more precise moving
    if ( $panspeed > 59 ) {
            $panspeed = 63;
                }
    if ( $panspeed < 6 ) {
           $panspeed = 1;
               }
    Debug( "Move Con Down Left" );
    if ( $osd eq "on" )
   {
         my $cmd = "param.cgi?cmd=setoverlayattr&-region=1&-show=1&-name=Down $tiltspeed Left $panspeed";
         $self->sendCmd( $cmd );
        }
    my $cmd = "ptzctrl.cgi?-step=0&-act=down&-speed=$tiltspeed";
    $self->sendCmd( $cmd );
    my $cmd = "ptzctrl.cgi?-step=0&-act=left&-speed=$panspeed";
    $self->sendCmd( $cmd );
    $self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}

sub moveConDownRight
{
    my $self = shift;
    my $params = shift;
    my $tiltspeed = $self->getParam( $params, 'tiltspeed' );
    # Standardization for incorrect possible value in the database, and for realise at low and high speed an more precise moving
    if ( $tiltspeed > 59 ) {
              $tiltspeed = 63;
                 }
    if ( $tiltspeed < 6 ) {
            $tiltspeed = 1;
                }
    my $panspeed = $self->getParam( $params, 'panspeed' );
    # Standardization for incorrect possible value in the database, and for realise at low and high speed an more precise moving
    if ( $panspeed > 59 ) {
            $panspeed = 63;
                }
    if ( $panspeed < 6 ) {
           $panspeed = 1;
               }
    Debug( "Move Con Down Right" );
    if ( $osd eq "on" )
   {
         my $cmd = "param.cgi?cmd=setoverlayattr&-region=1&-show=1&-name=Down $tiltspeed Right $panspeed";
         $self->sendCmd( $cmd );
        }
    my $cmd = "ptzctrl.cgi?-step=0&-act=down&-speed=$tiltspeed";
    $self->sendCmd( $cmd );
    my $cmd = "ptzctrl.cgi?-step=0&-act=right&-speed=$panspeed";
    $self->sendCmd( $cmd );
    $self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}

sub zoomConTele
{
    my $self = shift;
    my $params = shift;
    my $speed = $self->getParam( $params, 'speed' );
    # Standardization for incorrect possible value in the database, and for realise at low and high speed an more precise moving
    if ( $speed > 59 ) {
              $speed = 63;
             }
    if ( $speed < 6 ) {
             $speed = 1;
            }
    Debug( "Zoom-Tele" );
    # I use OSD Function to send the speed used for determining the time before stop the order
    if ( $osd eq "on" )
   {
         my $cmd = "param.cgi?cmd=setoverlayattr&-region=1&-show=1&-name=Zoom Tele $speed";
         $self->sendCmd( $cmd );
        }
    my $cmd = "ptzctrl.cgi?-step=0&-act=zoomin";
    $self->sendCmd( $cmd );
    # The variable speed does not work with zoom setting, so I used to set the duration of the order
    # the result is identical
    $self->autoStop( int(10000*$speed) );
}

sub zoomConWide
{
    my $self = shift;
    my $params = shift;
    my $speed = $self->getParam( $params, 'speed' );
    # Standardization for incorrect possible value in the database, and for realise at low and high speed an more precise moving
    if ( $speed > 59 ) {
              $speed = 63;
             }
    if ( $speed < 6 ) {
             $speed = 1;
            }
    Debug( "Zoom-Wide" );
    # I use the feature OSD (OnScreenDisplay). Variable speed as a basis for calculating the duration of the zoom order
    if ( $osd eq "on" )
   {
         my $cmd = "param.cgi?cmd=setoverlayattr&-region=1&-show=1&-name=Zoom Wide $speed";
         $self->sendCmd( $cmd );
        }
    my $cmd = "ptzctrl.cgi?-step=0&-act=zoomout";
    $self->sendCmd( $cmd );
    # The variable speed does not work with zoom setting, so I used to set the duration of the order
    # the result is identical
    $self->autoStop( int(10000*$speed) );
}

sub focusConNear
{
    my $self = shift;
    my $params = shift;
    my $speed = $self->getParam( $params, 'speed' );
    # Standardization for incorrect possible value in the database, and for realise at low and high speed an more precise moving
    if ( $speed > 59 ) {
              $speed = 63;
                 }
    if ( $speed < 6 ) {
             $speed = 1;
            }
    Debug( "Focus Near" );
    if ( $osd eq "on" )
   {
         my $cmd = "param.cgi?cmd=setoverlayattr&-region=1&-show=1&-name=Focus Near $speed";
         $self->sendCmd( $cmd );
        }
    my $cmd = "ptzctrl.cgi?-step=0&-act=focusout&-speed=$speed";
    $self->sendCmd( $cmd );
    # The variable speed does not work with focus setting, so I used to set the duration of the order
    # the result is identical
    $self->autoStop( int(10000*$speed) );
}

sub focusConFar
{
    my $self = shift;
    my $params = shift;
    my $speed = $self->getParam( $params, 'speed' );
    # Standardization for incorrect possible value in the database, and for realise at low and high speed an more precise moving
    if ( $speed > 59 ) {
              $speed = 63;
             }
    if ( $speed < 6 ) {
             $speed = 1;
            }
    Debug( "Focus Far" );
    if ( $osd eq "on" )
   {
         my $cmd = "param.cgi?cmd=setoverlayattr&-region=1&-show=1&-name=Focus Far $speed";
         $self->sendCmd( $cmd );
        }
    my $cmd = "ptzctrl.cgi?-step=0&-act=focusin&-speed=$speed";
    $self->sendCmd( $cmd );
    # The variable speed does not work with focus setting, so I used to set the duration of the order
    # the result is identical
    $self->autoStop( int(10000*$speed) );
}

sub focusAuto
{
    my $self = shift;
    Debug( "Focus Auto" );
    if ( $osd eq "on" )
   {
         my $cmd = "param.cgi?cmd=setoverlayattr&-region=1&-show=1&-name=Focus Auto";
         $self->sendCmd( $cmd );
        }
    my $cmd = "ptzctrl.cgi?-act=auto&-step=1";
    $self->sendCmd( $cmd );
}

sub focusMan
{
    my $self = shift;
    Debug( "Focus Manu=OSD ON OFF" );
    if ( $osd eq "on" )
    {
     $osd = "off";
          my $cmd = "param.cgi?cmd=setoverlayattr&-region=1&-show=1&-name=OSD $osd";
          $self->sendCmd( $cmd );
          $self->autoStop( int(1000000*0.5) );
    }
    if ( $osd eq "off" )
         {
          $osd = "on";
          my $cmd = "param.cgi?cmd=setoverlayattr&-region=1&-show=1&-name=OSD $osd";
          $self->sendCmd( $cmd );
          $self->autoStop( int(1000000*0.5) );
         }
}

sub whiteConIn
{
    my $self = shift;
    my $params = shift;
    my $speed = $self->getParam( $params, 'speed' );
    # Standardization for incorrect possible value in the database, and for realise at low and high speed an more precise moving
    if ( $speed > 255 ) {
               $speed = 255;
              }
    if ( $speed < 0 ) {
             $speed = 0;
            }
    Debug( "White ConIn=brightness" );
    if ( $osd eq "on" )
   {
         my $cmd = "param.cgi?cmd=setoverlayattr&-region=1&-show=1&-name=Brightness $speed";
         $self->sendCmd( $cmd );
        }
    my $cmd = "param.cgi?cmd=setimageattr&-brightness=$speed";
    $self->sendCmd( $cmd );
    $self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}

sub whiteConOut
{
    my $self = shift;
    my $params = shift;
    my $speed = $self->getParam( $params, 'speed' );
    # Standardization for incorrect possible value in the database, and for realise at low and high speed an more precise moving
    if ( $speed > 255 ) {
               $speed = 255;
              }
    if ( $speed < 0 ) {
             $speed = 0;
            }
    Debug( "White ConOut=Contrast" );
    if ( $osd eq "on" )
   {
         my $cmd = "param.cgi?cmd=setoverlayattr&-region=1&-show=1&-name=Contrast $speed";
         $self->sendCmd( $cmd );
        }
    my $cmd = "param.cgi?cmd=setimageattr&-contrast=$speed";
    $self->sendCmd( $cmd );
    $self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}

sub whiteAuto
{
    my $self = shift;
    Debug( "White Auto=Brightness Reset" );
    if ( $osd eq "on" )
   {
         my $cmd = "param.cgi?cmd=setoverlayattr&-region=1&-show=1&-name=Brightness Reset";
         $self->sendCmd( $cmd );
        }
    my $cmd = "param.cgi?cmd=setimageattr&-brightness=120";
    $self->sendCmd( $cmd );
    $self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}

sub whiteMan
{
    my $self = shift;
    Debug( "White Manuel=Contrast Reset" );
    if ( $osd eq "on" )
   {
         my $cmd = "param.cgi?cmd=setoverlayattr&-region=1&-show=1&-name=Contrast Reset";
         $self->sendCmd( $cmd );
        }
    my $cmd = "param.cgi?cmd=setimageattr&-contrast=140";
    $self->sendCmd( $cmd );
    $self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}

sub irisConOpen
{
    my $self = shift;
    my $params = shift;
    my $speed = $self->getParam( $params, 'speed' );
    # Standardization for incorrect value in the database
    if ( $speed > 255 ) {
               $speed = 255;
              }
    if ( $speed < 0 ) {
             $speed = 0;
            }
    Debug( "Iris ConOpen=Saturation" );
    if ( $osd eq "on" )
   {
         my $cmd = "param.cgi?cmd=setoverlayattr&-region=1&-show=1&-name=Saturation $speed";
         $self->sendCmd( $cmd );
        }
    my $cmd = "param.cgi?cmd=setimageattr&-saturation=$speed";
    $self->sendCmd( $cmd );
    $self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}

sub irisConClose
{
    my $self = shift;
    my $params = shift;
    my $speed = $self->getParam( $params, 'speed' );
    # Standardization for incorrect value in the database
    if ( $speed > 255 ) {
               $speed = 255;
              }
    if ( $speed < 0 ) {
             $speed = 0;
            }
    Debug( "Iris ConClose=Hue" );
    if ( $osd eq "on" )
   {
         my $cmd = "param.cgi?cmd=setoverlayattr&-region=1&-show=1&-name=Hue $speed";
         $self->sendCmd( $cmd );
        }
    my $cmd = "param.cgi?cmd=setimageattr&-hue=$speed";
    $self->sendCmd( $cmd );
    $self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}

sub irisAuto
{
    my $self = shift;
    Debug( "Iris Auto=Saturation Reset" );
    if ( $osd eq "on" )
   {
         my $cmd = "param.cgi?cmd=setoverlayattr&-region=1&-show=1&-name=Saturation Reset";
         $self->sendCmd( $cmd );
        }
    my $cmd = "param.cgi?cmd=setimageattr&-saturation=150";
    $self->sendCmd( $cmd );
    $self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}

sub irisMan
{
    my $self = shift;
    Debug( "Iris Manuel=Hue Reset" );
    if ( $osd eq "on" )
   {
         my $cmd = "param.cgi?cmd=setoverlayattr&-region=1&-show=1&-name=Hue Reset";
         $self->sendCmd( $cmd );
        }
    my $cmd = "param.cgi?cmd=setimageattr&-hue=255";
    $self->sendCmd( $cmd );
    $self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}

sub presetSet
{
    my $self = shift;
    my $params = shift;
    my $preset = $self->getParam( $params, 'preset' );
    if ( ( $preset >= 1 ) && ( $preset <= 8 ) ) {
                       Debug( "Clear Preset $preset" );
                       my $cmd = "preset.cgi?-act=set&-status=0&-number=$preset";
                              $self->sendCmd( $cmd );
                                                 Debug( "Set Preset $preset" );
                        if ( $osd eq "on" )
                     {
                                   my $cmd = "param.cgi?cmd=setoverlayattr&-region=1&-show=1&-name=PresetSet $preset";
                                      $self->sendCmd( $cmd );
                     }
                                                 my $cmd = "preset.cgi?-act=set&-status=1&-number=$preset";
                                                 $self->sendCmd( $cmd );
                   $self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
                       }
}

sub presetGoto
{
    my $self = shift;
    my $params = shift;
    my $preset = $self->getParam( $params, 'preset' );
    if ( ( $preset >= 1 ) && ( $preset <= 8 ) ) {
                        Debug( "Goto Preset $preset" );
                        if ( $osd eq "on" )
                     {
                             my $cmd = "param.cgi?cmd=setoverlayattr&-region=1&-show=1&-name=PresetGoto $preset";
                                      $self->sendCmd( $cmd );
                     }
                        my $cmd = "preset.cgi?-act=goto&-number=$preset";
                        $self->sendCmd( $cmd );
                   $self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
                       }
}

1;
__END__
# Below is stub documentation for your module. You'd better edit it!

=head1 NAME

ZoneMinder::Control::FI8620 - Perl extension for FOSCAM FI8620

=head1 SYNOPSIS

  use ZoneMinder::Database;
  blah blah blah

=head1 DESCRIPTION

Stub documentation for ZoneMinder, created by h2xs. It looks like the
author of the extension was negligent enough to leave the stub
unedited.

Blah blah blah.

=head2 EXPORT

None by default.



=head1 SEE ALSO

Mention other useful documentation such as the documentation of
related modules or operating system documentation (such as man pages
in UNIX), or any relevant external documentation such as RFCs or
standards.

If you have a mailing list set up for your module, mention it here.

If you have a web site set up for your module, mention it here.

=head1 AUTHOR

Philip Coombes, E<lt>philip.coombes@zoneminder.comE<gt>

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2001-2008  Philip Coombes

This library is free software; you can redistribute it and/or modify
it under the same terms as Perl itself, either Perl version 5.8.3 or,
at your option, any later version of Perl 5 you may have available.


=cut
