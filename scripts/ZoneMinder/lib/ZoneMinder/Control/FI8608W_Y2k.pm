# V1.0 ====================================================================================
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
# V1.0 ====================================================================================
#
# This module FI8608W_Y2k.pm contains the implementation of API camera control
# For FOSCAM FI8608W PT Camera (This cam support only H264 streaming)
# V1.0 Le 13 AOUT 2013
# If you wan't to contact me i understand French and English, precise ZoneMinder in subject
# but i prefer via the ZoneMinder Forum
# My name is Christophe DAPREMONT my email is christophe_y2k@yahoo.fr
#
# V1.0 ====================================================================================
package ZoneMinder::Control::FI8608W_Y2k;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Control;

our @ISA = qw(ZoneMinder::Control);
# ===================================================================================================================================
#
# FI8608W FOSCAM PT H264 Control Protocol
# with Firmware version V3.2.1.1.1-20120815 (latest at 13/08/2013)
# based with the latest doc from FOSCAM ( http://foscam.us/forum/cgi-api-sdk-for-mjpeg-h-264-camera-t2986.html )
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
#  In round center equal to stop to move and switch of latest OSD
# -You can clic directly on the image that equal to click on arrow (for the left there is a bug in zoneminder speed is inverted)
# -Zoom Tele switch ON InfraRed LED and stay to manual IR MODE
# -Zoom Wide switch OFF InfraRed LED and stay to manual IR MODE
# -Button WAKE switch to AUTO ON/OFF IR LED
# -Button RESET to setup image at initial value
# -8 Preset PTZ are implemented and functionnal
# -This Script use for login "admin" this hardcoded and your password must setup in "Control Device" section
# -This script is compatible with the basic authentification method used by mostly new camera based with hi3510 chipset
# -AutoStop function is active and you must set up value (in sec example 0.7) under AutoStop section
#  or you can set up to 0 for disable it (in this case you need to click to the circle center for stop)
# -"White In" to control Brightness, "auto" for restore the original value of Brightness
# -"White Out" to control Contrast, "man" for restore the original value of Contrast
# -"Iris Open" to control Saturation , "auto" for restore the original value of Saturation
# -"Iris Close" to control Hue , "man" for restore the original value of Hue
# -I use the OSD function of this cam for printing the command with the value
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

sub reset
{
   my $self = shift;
   Debug( "Reset=Setup FI8608W" );
   if ( $osd eq "on" )
      {
                 my $cmd = "param.cgi?cmd=setoverlayattr&-region=1&-show=1&-name=Color RESET";
           $self->sendCmd( $cmd );
           }
   my $cmd = "param.cgi?cmd=setimageattr&-brightness=0";
   $self->sendCmd( $cmd );
   my $cmd = "param.cgi?cmd=setimageattr&-contrast=37";
   $self->sendCmd( $cmd );
   my $cmd = "param.cgi?cmd=setimageattr&-hue=255";
   $self->sendCmd( $cmd );
   my $cmd = "param.cgi?cmd=setimageattr&-saturation=94";
   $self->sendCmd( $cmd );
   my $cmd = "param.cgi?cmd=setinfra&-status=auto";
   $self->sendCmd( $cmd );
   my $cmd = "param.cgi?cmd=setimageattr&-scene=auto";
   $self->sendCmd( $cmd );
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
    if ( $tiltspeed > 100 ) {
              $tiltspeed = 128;
                  }
    if ( $tiltspeed < 10 ) {
             $tiltspeed = 1;
                 }
    Debug( "Move Up" );
    if ( $osd eq "on" )
   {
         my $cmd = "param.cgi?cmd=setoverlayattr&-region=1&-show=1&-name=Move Up $tiltspeed";
         $self->sendCmd( $cmd );
        }
    my $cmd = "ptzctrl.cgi?-step=0&-act=up";
    $self->sendCmd( $cmd );
    $self->autoStop( int(10000*$tiltspeed) );
}

sub moveConDown
{
    my $self = shift;
    my $params = shift;
    my $tiltspeed = $self->getParam( $params, 'tiltspeed' );
    # Standardization for incorrect possible value in the database, and for realise at low and high speed an more precise moving
    if ( $tiltspeed > 100 ) {
              $tiltspeed = 128;
                  }
    if ( $tiltspeed < 10 ) {
               $tiltspeed = 1;
                 }
    Debug( "Move Down" );
    if ( $osd eq "on" )
   {
         my $cmd = "param.cgi?cmd=setoverlayattr&-region=1&-show=1&-name=Move Down $tiltspeed";
         $self->sendCmd( $cmd );
        }
    my $cmd = "ptzctrl.cgi?-step=0&-act=down";
    $self->sendCmd( $cmd );
    $self->autoStop( int(10000*$tiltspeed) );
}

sub moveConLeft
{
    my $self = shift;
    my $params = shift;
    my $panspeed = $self->getParam( $params, 'panspeed' );
    # Standardization for incorrect possible value in the database, and for realise at low and high speed an more precise moving
    if ( $panspeed > 100 ) {
             $panspeed = 128;
                 }
    if ( $panspeed < 10 ) {
            $panspeed = 1;
                }
    # Algorithme pour inverser la table de valeur de la flèche gauche, (for invert the value) 63 ==> 1 et 1 ==> 63 ...
    $panspeed = abs($panspeed - 128) + 1;
    Debug( "Move Left" );
    if ( $osd eq "on" )
   {
         my $cmd = "param.cgi?cmd=setoverlayattr&-region=1&-show=1&-name=Move Left $panspeed";
         $self->sendCmd( $cmd );
        }
    my $cmd = "ptzctrl.cgi?-step=0&-act=left";
    $self->sendCmd( $cmd );
    $self->autoStop( int(10000*$panspeed) );
}

sub moveConRight
{
    my $self = shift;
    my $params = shift;
    my $panspeed = $self->getParam( $params, 'panspeed' );
    # Standardization for incorrect possible value in the database, and for realise at low and high speed an more precise moving
    if ( $panspeed > 100 ) {
             $panspeed = 128;
                 }
    if ( $panspeed < 10 ) {
            $panspeed = 1;
                }
    Debug( "Move Right" );
    if ( $osd eq "on" )
   {
         my $cmd = "param.cgi?cmd=setoverlayattr&-region=1&-show=1&-name=Move Right $panspeed";
         $self->sendCmd( $cmd );
        }
    my $cmd = "ptzctrl.cgi?-step=0&-act=right";
    $self->sendCmd( $cmd );
    $self->autoStop( int(10000*$panspeed) );
}

sub moveConUpLeft
{
    my $self = shift;
    my $params = shift;
    my $tiltspeed = $self->getParam( $params, 'tiltspeed' );
    # Standardization for incorrect possible value in the database, and for realise at low and high speed an more precise moving
    if ( $tiltspeed > 100 ) {
              $tiltspeed = 128;
                  }
    if ( $tiltspeed < 10 ) {
             $tiltspeed = 1;
                 }
    my $panspeed = $self->getParam( $params, 'panspeed' );
    # Standardization for incorrect value in the database
    if ( $panspeed > 100 ) {
             $panspeed = 128;
                 }
    if ( $panspeed < 10 ) {
            $panspeed = 1;
                }
    Debug( "Move Con Up Left" );
    if ( $osd eq "on" )
   {
         my $cmd = "param.cgi?cmd=setoverlayattr&-region=1&-show=1&-name=Up $tiltspeed Left $panspeed";
         $self->sendCmd( $cmd );
        }
    my $cmd = "ptzctrl.cgi?-step=0&-act=up";
    $self->sendCmd( $cmd );
    $self->autoStop( int(10000*$tiltspeed) );
    my $cmd = "ptzctrl.cgi?-step=0&-act=left";
    $self->sendCmd( $cmd );
    $self->autoStop( int(10000*$panspeed) );
}

sub moveConUpRight
{
    my $self = shift;
    my $params = shift;
    my $tiltspeed = $self->getParam( $params, 'tiltspeed' );
    # Standardization for incorrect possible value in the database, and for realise at low and high speed an more precise moving
    if ( $tiltspeed > 100 ) {
              $tiltspeed = 128;
                  }
    if ( $tiltspeed < 10 ) {
             $tiltspeed = 1;
                 }
    my $panspeed = $self->getParam( $params, 'panspeed' );
    # Standardization for incorrect possible value in the database, and for realise at low and high speed an more precise moving
    if ( $panspeed > 100 ) {
             $panspeed = 128;
                 }
    if ( $panspeed < 10 ) {
            $panspeed = 1;
                }
    Debug( "Move Con Up Right" );
    if ( $osd eq "on" )
   {
         my $cmd = "param.cgi?cmd=setoverlayattr&-region=1&-show=1&-name=Up $tiltspeed Right $panspeed";
         $self->sendCmd( $cmd );
        }
    my $cmd = "ptzctrl.cgi?-step=0&-act=up";
    $self->sendCmd( $cmd );
    $self->autoStop( int(10000*$tiltspeed) );
    my $cmd = "ptzctrl.cgi?-step=0&-act=right";
    $self->sendCmd( $cmd );
    $self->autoStop( int(10000*$panspeed) );
}

sub moveConDownLeft
{
    my $self = shift;
    my $params = shift;
    my $tiltspeed = $self->getParam( $params, 'tiltspeed' );
    # Standardization for incorrect possible value in the database, and for realise at low and high speed an more precise moving
    if ( $tiltspeed > 100 ) {
               $tiltspeed = 128;
                  }
    if ( $tiltspeed < 10 ) {
            $tiltspeed = 1;
                }
    my $panspeed = $self->getParam( $params, 'panspeed' );
    # Standardization for incorrect possible value in the database, and for realise at low and high speed an more precise moving
    if ( $panspeed > 100 ) {
             $panspeed = 128;
                 }
    if ( $panspeed < 10 ) {
            $panspeed = 1;
                }
    Debug( "Move Con Down Left" );
    if ( $osd eq "on" )
   {
         my $cmd = "param.cgi?cmd=setoverlayattr&-region=1&-show=1&-name=Down $tiltspeed Left $panspeed";
         $self->sendCmd( $cmd );
        }
    my $cmd = "ptzctrl.cgi?-step=0&-act=down";
    $self->sendCmd( $cmd );
    $self->autoStop( int(10000*$tiltspeed) );
    my $cmd = "ptzctrl.cgi?-step=0&-act=left";
    $self->sendCmd( $cmd );
    $self->autoStop( int(10000*$panspeed) );
}

sub moveConDownRight
{
    my $self = shift;
    my $params = shift;
    my $tiltspeed = $self->getParam( $params, 'tiltspeed' );
    # Standardization for incorrect possible value in the database, and for realise at low and high speed an more precise moving
    if ( $tiltspeed > 100 ) {
               $tiltspeed = 128;
                  }
    if ( $tiltspeed < 10 ) {
             $tiltspeed = 1;
                 }
    my $panspeed = $self->getParam( $params, 'panspeed' );
    # Standardization for incorrect possible value in the database, and for realise at low and high speed an more precise moving
    if ( $panspeed > 100 ) {
             $panspeed = 128;
                 }
    if ( $panspeed < 10 ) {
            $panspeed = 1;
                }
    Debug( "Move Con Down Right" );
    if ( $osd eq "on" )
   {
         my $cmd = "param.cgi?cmd=setoverlayattr&-region=1&-show=1&-name=Down $tiltspeed Right $panspeed";
         $self->sendCmd( $cmd );
        }
    my $cmd = "ptzctrl.cgi?-step=0&-act=down";
    $self->sendCmd( $cmd );
    $self->autoStop( int(10000*$tiltspeed) );
    my $cmd = "ptzctrl.cgi?-step=0&-act=right";
    $self->sendCmd( $cmd );
    $self->autoStop( int(10000*$panspeed) );
}

sub zoomConTele
{
    my $self = shift;
    Debug( "Zoom-Tele=MANU IR LED ON" );
    if ( $osd eq "on" )
   {
         my $cmd = "param.cgi?cmd=setoverlayattr&-region=1&-show=1&-name=Manual IR LED ON";
         $self->sendCmd( $cmd );
        }
    my $cmd = "param.cgi?cmd=setinfra&-status=open";
    $self->sendCmd( $cmd );
}

sub zoomConWide
{
    my $self = shift;
    Debug( "Zoom-Wide=MANU IR LED OFF" );
    if ( $osd eq "on" )
   {
         my $cmd = "param.cgi?cmd=setoverlayattr&-region=1&-show=1&-name=Manual IR LED OFF";
         $self->sendCmd( $cmd );
        }
    my $cmd = "param.cgi?cmd=setinfra&-status=close";
    $self->sendCmd( $cmd );
}

sub wake
{
    my $self = shift;
    Debug( "Wake=AUTO IR LED" );
    if ( $osd eq "on" )
   {
         my $cmd = "param.cgi?cmd=setoverlayattr&-region=1&-show=1&-name=Auto IR LED Mode";
         $self->sendCmd( $cmd );
        }
    my $cmd = "param.cgi?cmd=setinfra&-status=auto";
    $self->sendCmd( $cmd );       
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
    my $cmd = "param.cgi?cmd=setimageattr&-brightness=0";
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
    my $cmd = "param.cgi?cmd=setimageattr&-contrast=37";
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
    my $cmd = "param.cgi?cmd=setimageattr&-saturation=94";
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
                                                 Debug( "Set Preset $preset" );
                        if ( $osd eq "on" )
                     {
                                   my $cmd = "param.cgi?cmd=setoverlayattr&-region=1&-show=1&-name=PresetSet $preset";
                                      $self->sendCmd( $cmd );
                     }
                   my $preset = $preset - 1;
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
                   my $preset = $preset - 1;
                        my $cmd = "preset.cgi?-act=goto&-number=$preset";
                        $self->sendCmd( $cmd );
                   $self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
                       }
}

1;
__END__
# Below is stub documentation for your module. You'd better edit it!

=head1 NAME

ZoneMinder::Control::FI-8608W - Perl extension for FOSCAM FI-8608W by Christophe_Y2k

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
