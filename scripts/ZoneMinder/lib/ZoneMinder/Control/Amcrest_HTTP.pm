# ==========================================================================
#
# ZoneMinder Acrest HTTP API Control Protocol Module, 20180214, Rev 3.0
#
# Change Log
#
# Rev 3.0:
#  - Fixes incorrect method names
#  - Updates control sequences to Amcrest HTTP Protocol API v 2.12
#  - Extends control features
#  
# Rev 2.0:
#  - Fixed installation instructions text, no changes to functionality.
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

package ZoneMinder::Control::Amcrest_HTTP;

use 5.006;
use strict;
use warnings;

use Time::HiRes qw( usleep );

require ZoneMinder::Base;
require ZoneMinder::Control;

our @ISA = qw(ZoneMinder::Control);

# ==========================================================================
#
# Amcrest HTTP API Control Protocol
#
# ==========================================================================

use ZoneMinder::Logger qw(:all);
use ZoneMinder::Config qw(:all);

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
    Debug( "Received command: $name" );
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
    $self->{state} = 'open';
}

sub initUA
{
    my $self = shift;
    my $user = undef;
    my $password = undef;
    my $address = undef;

    if ( $self->{Monitor}->{ControlAddress} =~ /(.*):(.*)@(.*)/ )
    {
      $user = $1;
      $password = $2;
      $address = $3;
    }

    use LWP::UserAgent;
    $self->{ua} = LWP::UserAgent->new;
    $self->{ua}->credentials("$address", "Login to " . $self->{Monitor}->{ControlDevice}, "$user", "$password");
    $self->{ua}->agent( "ZoneMinder Control Agent/".ZoneMinder::Base::ZM_VERSION );
}

sub destroyUA
{
    my $self = shift;

    $self->{ua} = undef;
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

    destroyUA($self);
    initUA($self);

    my $user = undef;
    my $password = undef;
    my $address = undef;

    if ( $self->{Monitor}->{ControlAddress} =~ /(.*):(.*)@(.*)/ )
    {
      $user = $1;
      $password = $2;
      $address = $3;
    }

    printMsg( $cmd, "Tx" );

    my $req = HTTP::Request->new( GET=>"http://$address/$cmd" );
    my $res = $self->{ua}->request($req);

    if ( $res->is_success )
    {
        $result = !undef;
        # Command to camera appears successful, write Info item to log
        Info( "Camera control: '".$res->status_line()."' for URL ".$self->{Monitor}->{ControlAddress}."/$cmd" );
	# TODO: Add code to retrieve $res->message_decode or some such. Then we could do things like check the camera status.
    }
    else
    {
        Error( "Camera control command FAILED: '".$res->status_line()."' for URL ".$self->{Monitor}->{ControlAddress}."/$cmd" );
    }

    return( $result );
}

sub reset
{
    my $self = shift;
    # This reboots the camera effectively resetting it
    Debug( "Camera Reset" );
    $self->sendCmd( 'cgi-bin/magicBox.cgi?action=reboot' );
    ##FIXME: Exit is a bad idea as it appears to cause zmc to run away.
    #Exit (0);
}

# NOTE: I'm putting this in, but absolute camera movement does not seem to be well supported in the classic skin ATM.
# Reading www/skins/classic/include/control_functions.php seems to indicate a faulty implementation, unless I'm
# reading it wrong. I see nowhere where the user is able to specify the absolute location to move to. Rather,
# the call is passed back movement in increments of 1 unit. At least with the Amcrest/Duhua API this would result
# in the camera moving to the 1* or 0* etc. position.

sub moveAbs ## Up, Down, Left, Right, etc. ??? Doesn't make sense here...
{
    my $self = shift;
    my $pan_degrees = shift || 0;
    my $tilt_degrees = shift || 0;
    my $speed = shift || 1;
    Debug( "Move ABS" );
    $self->sendCmd( 'cgi-bin/ptz.cgi?action=start&code=PositionABS&channel=0&arg1='.$pan_degrees.'&arg2='.$tilt_degrees.'&arg3=0&arg4='.$speed );
}

sub moveConUp
{
    my $self = shift;
    Debug( "Move Up" );
    $self->sendCmd( 'cgi-bin/ptz.cgi?action=start&code=Up&channel=0&arg1=0&arg2=1&arg3=0' );
    usleep (500); ##XXX Should this be passed in as a "speed" parameter?
    $self->sendCmd( 'cgi-bin/ptz.cgi?action=stop&code=Up&channel=0&arg1=0&arg2=1&arg3=0' );
}

sub moveConDown
{
    my $self = shift;
    Debug( "Move Down" );
    $self->sendCmd( 'cgi-bin/ptz.cgi?action=start&code=Down&channel=0&arg1=0&arg2=1&arg3=0' );
    usleep (500);
    $self->sendCmd( 'cgi-bin/ptz.cgi?action=stop&code=Down&channel=0&arg1=0&arg2=1&arg3=0' );
}

sub moveConLeft
{
    my $self = shift;
    Debug( "Move Left" );
    $self->sendCmd( 'cgi-bin/ptz.cgi?action=start&code=Left&channel=0&arg1=0&arg2=1&arg3=0' );
    usleep (500);
    $self->sendCmd( 'cgi-bin/ptz.cgi?action=stop&code=Left&channel=0&arg1=0&arg2=1&arg3=0' );
}

sub moveConRight
{
    my $self = shift;
    Debug( "Move Right" );
#    $self->sendCmd( 'cgi-bin/ptz.cgi?action=start&code=PositionABS&channel=0&arg1=270&arg2=5&arg3=0' );
    $self->sendCmd( 'cgi-bin/ptz.cgi?action=start&code=Right&channel=0&arg1=0&arg2=1&arg3=0' );
    usleep (500);
    Debug( "Move Right Stop" );
    $self->sendCmd( 'cgi-bin/ptz.cgi?action=stop&code=Right&channel=0&arg1=0&arg2=1&arg3=0' );
}

sub moveConUpRight
{
    my $self = shift;
    Debug( "Move Diagonally Up Right" );
    $self->sendCmd( 'cgi-bin/ptz.cgi?action=start&code=RightUp&channel=0&arg1=1&arg2=1&arg3=0' );
    usleep (500);
    $self->sendCmd( 'cgi-bin/ptz.cgi?action=stop&code=RightUp&channel=0&arg1=0&arg2=1&arg3=0' );    
}

sub moveConDownRight
{
    my $self = shift;
    Debug( "Move Diagonally Down Right" );
    $self->sendCmd( 'cgi-bin/ptz.cgi?action=start&code=RightDown&channel=0&arg1=1&arg2=1&arg3=0' );
    usleep (500);
    $self->sendCmd( 'cgi-bin/ptz.cgi?action=stop&code=RightDown&channel=0&arg1=0&arg2=1&arg3=0' );  
}

sub moveConUpLeft
{
    my $self = shift;
    Debug( "Move Diagonally Up Left" );
    $self->sendCmd( 'cgi-bin/ptz.cgi?action=start&code=LeftUp&channel=0&arg1=1&arg2=1&arg3=0' );
    usleep (500);
    $self->sendCmd( 'cgi-bin/ptz.cgi?action=stop&code=LeftUp&channel=0&arg1=0&arg2=1&arg3=0' ); 
}

sub moveConDownLeft
{
    my $self = shift;
    Debug( "Move Diagonally Down Left" );
    $self->sendCmd( 'cgi-bin/ptz.cgi?action=start&code=LeftDown&channel=0&arg1=1&arg2=1&arg3=0' );
    usleep (500);
    $self->sendCmd( 'cgi-bin/ptz.cgi?action=stop&code=LeftDown&channel=0&arg1=0&arg2=1&arg3=0' ); 
}

# Stop is not "correctly" implemented as control_functions.php translates this to "Center"
# So we'll just send the camera to 0* Horz, 0* Vert, zoom out; Also, Amcrest does not seem to
# support a generic stop-all-current-action command.

sub moveStop
{
    my $self = shift;
    Debug( "Move Stop/Center" );
    $self->sendCmd( 'cgi-bin/ptz.cgi?action=start&code=PositionABS&channel=0&arg1=0&arg2=0&arg3=0&arg4=1' );
}

# Move Camera to Home Position
# The current API does not support a Home per se, so we'll just send the camera to preset #1
# NOTE: It goes without saying that the user must have set up preset #1 for this to work.

sub presetHome
{
    my $self = shift;
    Debug( "Home Preset" );
    $self->sendCmd( 'cgi-bin/ptz.cgi?action=start&channel=0&code=GotoPreset&&arg1=0&arg2=1&arg3=0&arg4=0' );
}

sub presetGoto
{
    my $self = shift;
    my $params = shift;
    my $preset = $self->getParam( $params, 'preset' );    
    Debug( "Go To Preset $preset" );
    $self->sendCmd( 'cgi-bin/ptz.cgi?action=start&channel=0&code=GotoPreset&&arg1=0&arg2='.$preset.'&arg3=0&arg4=0' );
}

sub presetSet
{
    my $self = shift;
    my $params = shift;
    my $preset = $self->getParam( $params, 'preset' );
    Debug( "Set Preset" );
    $self->sendCmd( 'cgi-bin/ptz.cgi?action=start&channel=0&code=SetPreset&arg1=0&arg2='.$preset.'&arg3=0&arg4=0' );
}

# NOTE: This does not appear to be implemented in the classic skin. But we'll leave it here for later.

sub moveMap 
{
    my $self = shift;
    my $params = shift;

    my $xcoord = $self->getParam( $params, 'xcoord', $self->{Monitor}{Width}/2 );
    my $ycoord = $self->getParam( $params, 'ycoord', $self->{Monitor}{Height}/2 );
    # if the camera is mounted upside down, you may have to inverse these coordinates
    # just use 360 minus pan instead of pan, 90 minus tilt instead of tilt
    # Convert xcoord into pan position 0 to 359
    my $pan = int(360 * $xcoord / $self->{Monitor}{Width});
    # Convert ycoord into tilt position 0 to 89
    my $tilt = 90 - int(90 * $ycoord / $self->{Monitor}{Height});
    # Now get the following url:
    $self->sendCmd( 'cgi-bin/ptz.cgi?action=start&code=PositionABS&channel=0&arg1='.$pan.'&arg2='.$tilt.'&arg3=1&arg4=1');
}

sub zoomConTele
{
    my $self = shift;
    Debug( "Zoom continuous tele" );
    $self->sendCmd( 'cgi-bin/ptz.cgi?action=start&channel=0&code=ZoomTele&arg1=0&arg2=0&arg3=0&arg4=0' );
    usleep (100000);
    $self->sendCmd( 'cgi-bin/ptz.cgi?action=stop&channel=0&code=ZoomTele&arg1=0&arg2=0&arg3=0&arg4=0' );
}

sub zoomConWide
{
    my $self = shift;
    Debug( "Zoom continuous wide" );
    $self->sendCmd( 'cgi-bin/ptz.cgi?action=start&channel=0&code=ZoomWide&arg1=0&arg2=0&arg3=0&arg4=0' );
    usleep (100000);
    $self->sendCmd( 'cgi-bin/ptz.cgi?action=stop&channel=0&code=ZoomWide&arg1=0&arg2=0&arg3=0&arg4=0' );
}

1;

__END__

=pod

=head1 NAME

ZoneMinder::Control::Amcrest_HTTP - Amcrest camera control

=head1 DESCRIPTION

This module contains the implementation of the Amcrest Camera 
controllable SDK API.

NOTE: This module implements interaction with the camera in clear text.

The login and password are transmitted from ZM to the camera in clear text,
and as such, this module should be used ONLY on a blind LAN implementation
where interception of the packets is very low risk.

The "usleep (X);" lines throughout the script may need adjustments for your
situation.  This is the time that the script waits between sending a "start"
and a "stop" signal to the camera.  For example, the pan left arrow would
result in the camera panning full to its leftmost position if there were no
stop signal.  So the usleep time sets how long the script waits to allow the
camera to start moving before issuing a stop.  The X value of usleep is in
microseconds, so "usleep (100000);" is equivalent to wait one second.

=head1 SEE ALSO

https://s3.amazonaws.com/amcrest-files/Amcrest+HTTP+API+3.2017.pdf

=head1 AUTHORS

Herndon Elliott alabamatoy at gmail dot com
Chris Nighswonger chris dot nighswonger at gmail dot com

=head1 COPYRIGHT AND LICENSE

(C) 2016 Herndon Elliott alabamatoy at gmail dot com
(C) 2018 Chris Nighswonger chris dot nighswonger at gmail dot com

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
