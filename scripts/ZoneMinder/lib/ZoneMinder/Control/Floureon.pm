# Modified on 2017-11-17 by Ognyan Bankov 

# ==========================================================================
#
# ZoneMinder Floureon 1080p IP Control Protocol Module, $Date: 2017-11-17 09:20:00 +0000 $, $Revision: 0001 $
# Copyright (C) 2017 Ognyan Bankov
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
# This module contains the implementation of the Floureon 1080p 18x (Model: BT-HD54F)  IP camera control
# protocol. It should work with other Floureon cameras too.
#
# 

package MyAgent;

use base 'LWP::UserAgent';


package ZoneMinder::Control::Floureon;
 
use 5.006;
use strict;
use warnings;
 
require ZoneMinder::Base;
require ZoneMinder::Control;
 
our @ISA = qw(ZoneMinder::Control);
 
our $VERSION = $ZoneMinder::Base::VERSION;
 
# ==========================================================================
#
# Floureon IP Control Protocol
#
# ==========================================================================
 
use ZoneMinder::Logger qw(:all);
use ZoneMinder::Config qw(:all);
 
use Time::HiRes qw( usleep );
 
our $stop_command;
 
sub open
{
	my $self = shift;
 
	$self->loadMonitor();
 
	$self->{ua} = MyAgent->new;
	$self->{ua}->agent( "ZoneMinder Control Agent/" );
 
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
 
	my $req = HTTP::Request->new( GET=>"http://".$self->{Monitor}->{ControlAddress}."/$cmd"."&".$self->{Monitor}->{ControlDevice});
	print ("Sending $req\n");
	my $res = $self->{ua}->request($req);
	if ( $res->is_success )
	{
		$result = !undef;
	}
	else
	{
		Error( "Error REALLY check failed:'".$res->status_line()."'" );
		Error ("Cmd:".$req);
	}
 
	return( $result );
}
 
sub reset
{
	my $self = shift;
	Debug( "Camera Reset" );
	my $cmd = "reboot.cgi?";
	$self->sendCmd( $cmd );
}

# PP - in all move operations, added auto stop after timeout

#Up Arrow
sub moveConUp
{
	my $self = shift;
	my $params = shift;
	my $tiltspeed = $self->getParam( $params, 'tiltspeed' );
	Debug( "Move Up" );
	my $cmd = "cgi/ptz_set?Channel=1&Group=PTZCtrlInfo&Direction=1&PanSpeed=6&TiltSpeed=$tiltspeed";
	$self->sendCmd( $cmd );
	$self->autoStop($tiltspeed);
}
 
#Down Arrow
sub moveConDown
{
	my $self = shift;
	my $params = shift;
	my $tiltspeed = $self->getParam( $params, 'tiltspeed' );
	Debug( "Move Down" );
	my $cmd = "cgi/ptz_set?Channel=1&Group=PTZCtrlInfo&Direction=2&PanSpeed=6&TiltSpeed=$tiltspeed";
	$self->sendCmd( $cmd );
	$self->autoStop($tiltspeed);
}
 
#Left Arrow
sub moveConLeft
{
	my $self = shift;
	my $params = shift;
	my $panspeed = $self->getParam( $params, 'panspeed' );
	
	Debug( "Move Left" );
	my $cmd = "cgi/ptz_set?Channel=1&Group=PTZCtrlInfo&Direction=3&PanSpeed=$panspeed&TiltSpeed=6";
	$self->sendCmd( $cmd );
	$self->autoStop($panspeed);
}
 
#Right Arrow
sub moveConRight
{
	my $self = shift;
	my $params = shift;
	my $panspeed = $self->getParam( $params, 'panspeed' );
	Debug( "Move Right" );
	my $cmd = "cgi/ptz_set?Channel=1&Group=PTZCtrlInfo&Direction=4&PanSpeed=$panspeed&TiltSpeed=6";
	$self->sendCmd( $cmd );
	$self->autoStop($panspeed);
}
 
#Diagonally Up Right Arrow
sub moveConUpRight
{
	my $self = shift;
	my $params = shift;
	my $panspeed = $self->getParam( $params, 'panspeed' );
	my $tiltspeed = $self->getParam( $params, 'tiltspeed' );
	Debug( "Move Diagonally Up Right" );
	my $cmd = "cgi/ptz_set?Channel=1&Group=PTZCtrlInfo&Direction=7&PanSpeed=$panspeed&TiltSpeed=$tiltspeed";
	$self->sendCmd( $cmd );
	$self->autoStop($tiltspeed);

}
 
#Diagonally Down Right Arrow
sub moveConDownRight
{
	my $self = shift;
	my $params = shift;
	my $panspeed = $self->getParam( $params, 'panspeed' );
	my $tiltspeed = $self->getParam( $params, 'tiltspeed' );
	Debug( "Move Diagonally Down Right" );
	my $cmd = "cgi/ptz_set?Channel=1&Group=PTZCtrlInfo&Direction=8&PanSpeed=$panspeed&TiltSpeed=$tiltspeed";
	$self->sendCmd( $cmd );
	$self->autoStop($tiltspeed);
}
 
#Diagonally Up Left Arrow
sub moveConUpLeft
{
	my $self = shift;
	my $params = shift;
	my $panspeed = $self->getParam( $params, 'panspeed' );
	my $tiltspeed = $self->getParam( $params, 'tiltspeed' );
	Debug( "Move Diagonally Up Left" );
	my $cmd = "cgi/ptz_set?Channel=1&Group=PTZCtrlInfo&Direction=5&PanSpeed=$panspeed&TiltSpeed=$tiltspeed";
	$self->sendCmd( $cmd );
	$self->autoStop($tiltspeed);
}
 
#Diagonally Down Left Arrow
sub moveConDownLeft
{
	my $self = shift;
	my $params = shift;
	my $panspeed = $self->getParam( $params, 'panspeed' );
	my $tiltspeed = $self->getParam( $params, 'tiltspeed' );
	Debug( "Move Diagonally Down Left" );
	my $cmd = "cgi/ptz_set?Channel=1&Group=PTZCtrlInfo&Direction=6&PanSpeed=$panspeed&TiltSpeed=$tiltspeed";
	$self->sendCmd( $cmd );
	$self->autoStop($tiltspeed);
}
 
#Stop
sub moveStop
{
	my $self = shift;
	Debug( "Move Stop" );
	my $cmd = "cgi/ptz_set?Channel=1&Group=PTZCtrlInfo&Stop=0";
	$self->sendCmd( $cmd );
}

 
sub zoomConTele
{
	my $self = shift;
        my $cmd = "cgi/ptz_set?Channel=1&Group=PTZCtrlInfo&Zoom=1";
        $self->sendCmd( $cmd );
        $self->autoStop();        
}


sub zoomConWide
{
	my $self = shift;
        my $cmd = "cgi/ptz_set?Channel=1&Group=PTZCtrlInfo&Zoom=0";
        $self->sendCmd( $cmd );
        $self->autoStop();
}
 

sub focusConNear
{
	my $self = shift;
        my $cmd = "cgi/ptz_set?Channel=1&Group=PTZCtrlInfo&Focus=1";
        $self->sendCmd( $cmd );
        $self->autoStop();
} 


sub focusConFar
{
	my $self = shift;
        my $cmd = "cgi/ptz_set?Channel=1&Group=PTZCtrlInfo&Focus=0";
        $self->sendCmd( $cmd );
        $self->autoStop();
}


sub irisConOpen
{
	my $self = shift;
        my $cmd = "cgi/ptz_set?Channel=1&Group=PTZCtrlInfo&Iris=1";
        $self->sendCmd( $cmd );
        $self->autoStop();
} 


sub irisConClose
{
	my $self = shift;
        my $cmd = "cgi/ptz_set?Channel=1&Group=PTZCtrlInfo&Iris=0";
        $self->sendCmd( $cmd );
        $self->autoStop();
} 


#Set preset
sub presetSet
{
    my $self = shift;
    my $params = shift;
    my $preset = $self->getParam( $params, 'preset' );
    my $cmd = "cgi/ptz_set?Channel=1&Group=PTZCtrlInfo&PresetNumber=1&Preset=0";
    $self->sendCmd( $cmd );
}

 
#Goto preset
sub presetGoto
{
    my $self = shift;
    my $params = shift;
    my $preset = $self->getParam( $params, 'preset' );
    my $cmd = "cgi/ptz_set?Channel=1&Group=PTZCtrlInfo&PresetNumber=1&Preset=1";
    $self->sendCmd( $cmd );
}


sub autoStop
{
	my $self = shift;
	my $timeout = shift;

	if ($timeout)
	{
		if ($timeout > 1) {
			usleep(100000*$timeout);
		}
	}
        Debug( "Auto Stop" );
        my $cmd = "cgi/ptz_set?Channel=1&Group=PTZCtrlInfo&Stop=0";
        $self->sendCmd( $cmd );
}
 
1;
__END__

=head1 Floureon

ZoneMinder::Database - Perl extension for Floureon 1080P

=head1 SYNOPSIS

Control script for Floureon 1080P IP camera

=head1 DESCRIPTION

When setuping you monitor in the "Control" tab:

1. Select "Control type": Floureon 1080P
2. Leave "Control device" empty
3. Fill "Control Address" like username:password@ip/domain. Example: admin:admin123@192.168.1.110

=head2 EXPORT

None by default.



=head1 SEE ALSO

=head1 AUTHOR

Ognyan Bankov, E<lt>ogibankov@gmail.comE<gt>

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2017  Ognyan Bankov

This library is free software; you can redistribute it and/or modify
it under the same terms as Perl itself, either Perl version 5.8.3 or,
at your option, any later version of Perl 5 you may have available.


=cut

