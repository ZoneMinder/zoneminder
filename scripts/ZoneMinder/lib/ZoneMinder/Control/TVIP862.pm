# =========================================================================
#
# ZoneMinder Trendnet TV-IP862IC IP Control Protocol Module, $Date: $, $Revision: $
# Copyright (C) 2014 Vincent Giovannone
#
#
# ==========================================================================
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
#
# ==========================================================================
#
# This module contains the implementation of the Trendnet TV-IP672PI IP camera control
# protocol. Also works or TV-IP862IC
#
#  For Zoneminder 1.26+
#
#  Under control capability:
#
#   *  Main:  name it (suggest TVIP672PI), type is FFMPEG (or remote if you're using MJPEG), protocol is TVIP672PI
#   *  Main (more):  Can wake, can sleep, can reset
#   *  Move:  Can move, can move diagonally, can move mapped, can move relative
#   *  Pan:  Can pan
#   *  Tilt:  Can tilt
#   *  Presets:  Has presets, num presets 20, has home preset  (don't set presets via camera's web server, only set via ZM.)
#
#  Under control tab in the monitor itself:
#
#   *  Controllable
#   *  Control type is the name you gave it in control capability above
#   *  Control device is the password you use to authenticate to the camera  (see further below if you need to change the username from "admin")
#   *  Control address is the camera's ip address AND web port.  example:  192.168.1.1:80 
#
#
# If using with anything but a TV-IP672PI (ex:  TV-IP672WI), YOU MUST MATCH THE REALM TO MATCH YOUR CAMERA FURTHER DOWN!
#
#
#  Due to how the TVIP672 represents presets internally, you MUST define the presets in order...  i.e. 1,2,3,4...  not 1,10,3,4.
#   (see much further down for why, if you care...)
#


package ZoneMinder::Control::TVIP862;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Control;

our @ISA = qw(ZoneMinder::Control);

#
#  ********  YOU MUST CHANGE THE FOLLOWING LINES TO MATCH YOUR CAMERA!  **********
#  
#  I assume that "TV-IP672WI" would work for the TV-IP672WI, but can't test since I don't own one.
#  
#  TV-IP672PI works for the PI version, of course.
#
#  Finally, the username is the username you'd like to authenticate as.
#
our $REALM = 'TV-IP862IC';
our $USERNAME = 'admin';
our	$PASSWORD = '';
our $ADDRESS = '';

# ==========================================================================
#
# Trendnet TV-IP672PI Control Protocol
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

	my ( $protocol, $username, $password, $address ) = $self->{Monitor}->{ControlAddress} =~ /^(https?:\/\/)?([^:]+):([^\/@]+)@(.*)$/;
	if ( $username ) {
		$USERNAME = $username;
		$PASSWORD = $password;
		$ADDRESS = $address;
	} else {
		Error( "Failed to parse auth from address");
		$ADDRESS = $self->{Monitor}->{ControlAddress};
	}
	if ( ! $ADDRESS =~ /:/ ) {
		Error( "You generally need to also specify the port.  I will append :80" );
		$ADDRESS .= ':80';
	}

    use LWP::UserAgent;
    $self->{ua} = LWP::UserAgent->new;
    $self->{ua}->agent( "ZoneMinder Control Agent/".$ZoneMinder::Base::ZM_VERSION );
    $self->{state} = 'open';
#	credentials:  ("ip:port" (no prefix!), realm (string), username (string), password (string)
    Debug ( "sendCmd credentials control address:'".$ADDRESS."'  realm:'" . $REALM . "'  username:'" . $USERNAME . "'  password:'".$PASSWORD."'"); 
    $self->{ua}->credentials($ADDRESS,$REALM,$USERNAME,$PASSWORD);
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

#   This routine is used for all moving, which are all GET commands...

    my $self = shift;
    my $cmd = shift;

    my $result = undef;

	my $url = "http://".$ADDRESS."/cgi/ptdc.cgi?command=".$cmd;
    my $req = HTTP::Request->new( GET=>$url );

    Debug ("sendCmd command: " . $url );
    
    my $res = $self->{ua}->request($req);

    if ( $res->is_success ) {
        $result = !undef;
    } else {
		if ( $res->status_line() eq '401 Unauthorized' ) {
			Error( "Error check failed, trying again: USERNAME: $USERNAME realm: $REALM password: " . $PASSWORD );
			Error("Content was " . $res->content() );
			my $res = $self->{ua}->request($req);
			if ( $res->is_success ) {
				$result = !undef;
			} else {
				Error("Content was " . $res->content() );
			}
		} 
		if ( ! $result ) {
			Error( "Error check failed: '".$res->status_line()."' cmd:'".$cmd."'" );
		}
    }

    return( $result );
}



sub sendCmdPost
{

#
#   This routine is used for setting/clearing presets and IR commands, which are POST commands...
#

    my $self = shift;
    my $url = shift;
    my $cmd = shift;

    my $result = undef;

    if ($url eq undef)
	{
		Error ("url passed to sendCmdPost is undefined.");
		return(-1);
	}

    Debug ("sendCmdPost url: " . $url . " cmd: " . $cmd);

    my $req = HTTP::Request->new(POST => "http://".$ADDRESS.$url);
    $req->content_type('application/x-www-form-urlencoded');
    $req->content($cmd);

    Debug ( "sendCmdPost credentials control address:'".$ADDRESS."'  realm:'" . $REALM . "'  username:'" . $USERNAME . "' password:'".$PASSWORD."'");
    
    my $res = $self->{ua}->request($req);

    if ( $res->is_success )
    {
        $result = !undef;
    }
    else
    {
        Error( "sendCmdPost Error check failed: '".$res->status_line()."' cmd:'".$cmd."'" );
		if ( $res->status_line() eq '401 Unauthorized' ) {
			Error( "sendCmdPost Error check failed: USERNAME: $USERNAME realm: $REALM password: " . $PASSWORD );
		} else {
			Error( "sendCmdPost Error check failed: USERNAME: $USERNAME realm: $REALM password: " . $PASSWORD );
		} # endif
    }

    return( $result );
}



sub move
{
    my $self = shift;
    my $panSteps = shift;
    my $tiltSteps = shift;

    my $cmd = "set_relative_pos&posX=$panSteps&posY=$tiltSteps";
    $self->sendCmd( $cmd );
}

sub moveRelUpLeft
{
    my $self = shift;
    Debug( "Move Up Left" );
    $self->move(-3, 3);
}

sub moveRelUp
{
    my $self = shift;
    Debug( "Move Up" );
    $self->move(0, 3);
}

sub moveRelUpRight
{
    my $self = shift;
    Debug( "Move Up Right" );
    $self->move(3, 3);
}

sub moveRelLeft
{
    my $self = shift;
    Debug( "Move Left" );
    $self->move(-3, 0);
}

sub moveRelRight
{
    my $self = shift;
    Debug( "Move Right" );
    $self->move(3, 0);
}

sub moveRelDownLeft
{
    my $self = shift;
    Debug( "Move Down Left" );
    $self->move(-3, -3);
}

sub moveRelDown
{
    my $self = shift;
    Debug( "Move Down" );
    $self->move(0, -3);
}

sub moveRelDownRight
{
    my $self = shift;
    Debug( "Move Down Right" );
    $self->move(3, -3);
}


# moves the camera to center on the point that the user clicked on in the video image. 
# This isn't mega accurate but good enough for most purposes 

sub moveMap
{

    # If the camera moves too much, increase hscale and vscale. (...if it doesn't move enough, try decreasing!)
    # They scale the movement and are here to compensate for manufacturing variation.
    # It's never going to be perfect, so just get somewhere in the ballpark and call it a day.
    #  (Don't forget to kill the zmcontrol process while tweaking!)

    #  1280x800
    my $hscale = 31;
    my $vscale = 25;

    #  1280x800 with fisheye
    #my $hscale = 15;
    #my $vscale = 15;

    #  640x400
    #my $hscale = 14;
    #my $vscale = 12;


    my $self = shift;
    my $params = shift;
    my $xcoord = $self->getParam( $params, 'xcoord' );
    my $ycoord = $self->getParam( $params, 'ycoord' );

    my $hor = ($xcoord - ($self->{Monitor}->{Width} / 2))/$hscale;
    my $ver = ($ycoord - ($self->{Monitor}->{Height} / 2))/$vscale;

    $hor = int($hor);
    $ver = -1 * int($ver);
   
    Debug( "Move Map to $xcoord,$ycoord, hor=$hor, ver=$ver" );
    $self->move( $hor, $ver );
}


#  ****  PRESETS  ****
#
#  OK, presets work a little funky but they DO work, provided you define them in order and don't skip any. 
#  
#  The problem is that when you load the web page for this camera, it gives a list of preset names tied to index numbers.
#  So let's say you have four presets...  A, B, C, and D, and defined them in that order.
#  So A is index 0, B is index 1, C is index 2, D is index 3.  When you tell the camera to go to a preset, you actually tell it by number, not by name. 
#  (So "Go to D" is really "go to index 3".)
#
#  Now let's say somebody deletes C via the camera's web GUI.  The camera re-numbers the existing presets A=0, B=1, D=2.  
#  There's really no easy way for ZM to discover this re-numbering, so zoneminder would still send "go to preset 3" thinking
#  it's telling the camera to go to point D.  In actuality it's telling the camera to go to a preset that no longer exists.
#  
#  As long as you define your presets in order (i.e. define preset 1, then preset 2, then preset 3, etc.) everything will work just 
#  fine in ZoneMinder.
#
#  (Home preset needs to be set via the camera's web gui, and is unaffected by any of this.)
#
#  So that's the limitation:  DEFINE YOUR PRESETS IN ORDER THROUGH (and only through!) ZM AND DON'T SKIP ANY.
#


sub presetClear
{
    my $self = shift;
    my $params = shift;
    my $preset = $self->getParam( $params, 'preset' );
    my $cmd = "presetName=$preset&command=del";
    my $url = "/eng/admin/cam_control.cgi";
    Debug ("presetClear: " . $preset . " cmd: " . $cmd);
    $self->sendCmdPost($url,$cmd);
}


sub presetSet
{
    my $self = shift;
    my $params = shift;
    my $preset = $self->getParam( $params, 'preset' );
    my $cmd = "presetName=$preset&command=add";
    my $url = "/eng/admin/cam_control.cgi";
    Debug ("presetSet " . $preset . " cmd: " . $cmd);
    $self->sendCmdPost ($url,$cmd);
}

sub presetGoto
{
    my $self = shift;
    my $params = shift;
    my $preset = $self->getParam( $params, 'preset' );
    $preset = $preset - 1;
    Debug( "Goto Preset $preset" );
    my $cmd = "goto_preset_position&index=$preset";
    $self->sendCmd( $cmd );
}

sub presetHome
{
    my $self = shift;
    Debug( "Home Preset" );
    my $cmd = "go_home";
    $self->sendCmd( $cmd );
}


#
#  ****  IR CONTROLS  ****
#
#
#   Wake:  Force IR on, always.  (always night mode)
#
#   Sleep:  Force IR off, always.  (always day mode)
#
#   Reset:  Automatic IR mode.  (day/night mode determined by camera)
#


sub wake
{
    #  force IR on  ("always night mode")

    my $self = shift;
    my $url = "/eng/admin/adv_audiovideo.cgi";
    my $cmd = "irMode=3";

    Debug("Wake -- IR on");

    $self->sendCmdPost ($url,$cmd);
}

sub sleep
{
    #  force IR off ("always day mode")

    my $self=shift;
    my $url = "/eng/admin/adv_audiovideo.cgi";
    my $cmd = "irMode=2";

    Debug("Sleep -- IR off");

    $self->sendCmdPost ($url,$cmd);
}

sub reset
{
    #  IR auto

    my $self=shift;
    my $url = "/eng/admin/adv_audiovideo.cgi";
    my $cmd = "irMode=0";

    Debug("Reset -- IR auto");

    $self->sendCmdPost ($url,$cmd);
}


1;
__END__
# Below is stub documentation for your module. You'd better edit it!

=head1 NAME

ZoneMinder::Database - Perl extension for Trendnet TVIP672

=head1 SYNOPSIS

  use ZoneMinder::Database;
  stuff this in /usr/share/perl5/ZoneMinder/Control , then eat a sandwich

=head1 DESCRIPTION

Stub documentation for Trendnet TVIP672, created by Vince. 

=head2 EXPORT

None by default.



=head1 SEE ALSO

Read the comments at the beginning of this file to see the usage for zoneminder 1.25.0


=head1 AUTHOR

Vincent Giovannone, I'd rather you not email me.

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2014 by Vincent Giovannone

This library is free software; you can redistribute it and/or modify
it under the same terms as Perl itself, either Perl version 5.8.3 or,
at your option, any later version of Perl 5 you may have available.


=cut
